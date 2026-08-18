<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
