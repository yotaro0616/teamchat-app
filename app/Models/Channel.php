<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * チャンネル（docs/design/data.md 2-2）。
 *
 * 削除は物理削除。SoftDeletes は使わない（同 2-2。messages の deleted_at とは別物）。
 */
class Channel extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'created_by',
    ];

    /**
     * 作成者。名前・説明の編集とチャンネル削除ができるのはこの人だけ（spec §3-2）。
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * メンバー（channel_user）。
     *
     * data.md 2-3 のとおり channel_user は updated_at を持たないので withTimestamps() は使わない。
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('created_at');
    }

    /**
     * このチャンネルのメッセージ（返信を含む）。
     *
     * deleted_at では絞らない。削除済みも枠として画面に残る（data.md 3章 F-06行）。
     * 本流だけが欲しいときは ->threadStarters() を重ねる。
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * チャンネル画面（SC-05）に並べるメッセージ（F-06）。
     *
     * 返信は本流に出さない（parent_message_id IS NULL）が、**削除済みは外さない**。
     * 削除済みは「このメッセージは削除されました」の枠として残り続ける（data.md 3章 F-06行）。
     * 並びは古いものが上・新しいものが下（questions.md「どのQにも当たらなかった回答」）。
     *
     * 「返信N件」（F-16 への導線）は件数のキャッシュ列を持たない設計なので、
     * withCount('replies') で1本のクエリにまとめて数える（data.md 2-4）。
     *
     * @return Collection<int, Message>
     */
    public function messagesForDisplay(): Collection
    {
        return $this->messages()
            ->threadStarters()
            ->with('user')
            ->withCount('replies')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * 公開API（F-19）に返すメッセージ（permissions-api.md 3章 / data.md 3章 F-19行）。
     *
     * 削除済み（deleted_at）は明示的に除外する（questions.md Q-11の回答）。
     * 返信（parent_message_id）は questions.md Q-12 の暫定判断により含めない。
     */
    public function messagesForPublicApi(): Collection
    {
        return $this->messages()
            ->threadStarters()
            ->whereNull('deleted_at')
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * 削除確認カードの「メッセージ {n}件」（screens.md 3-6）。
     *
     * チャンネルの削除は物理削除で、削除済みメッセージの行も一緒に消えるため
     * deleted_at は問わずに数える。
     */
    public function messageCount(): int
    {
        return $this->messages()->threadStarters()->count();
    }

    /**
     * 削除確認カードの「返信 {m}件」（screens.md 3-6）。
     */
    public function replyCount(): int
    {
        return $this->messages()->whereNotNull('parent_message_id')->count();
    }

    /**
     * その利用者が見られるチャンネルだけに絞る。
     *
     * 出るのは公開チャンネル全部と、自分がメンバーのプライベートだけ（questions.md Q-04）。
     * 一覧・サイドバー・直接アクセスの可否を、すべてこの1本に通す。
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('type', 'public')
                ->orWhereHas('members', fn (Builder $members) => $members->whereKey($user->id));
        });
    }

    public function isPublic(): bool
    {
        return $this->type === 'public';
    }

    /**
     * 自分がメンバーでないプライベートチャンネルは、存在しないIDと同じ扱いにする（behavior.md 3章）。
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->isPublic() || $this->members()->whereKey($user->id)->exists();
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }
}
