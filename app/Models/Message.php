<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * メッセージ（docs/design/data.md 2-4）。スレッド返信も同じこのモデル。
 *
 * 削除は論理削除だが、**Laravel の SoftDeletes トレイトは使わない**（data.md 2-4 の補足）。
 * トレイトのグローバルスコープは削除済みを既定でクエリから外すが、この設計では
 * F-06（チャンネル表示）・F-16（スレッド表示）が deleted_at を問わず全件取得するのが正で、
 * 既定を「全件」にしておかないと削除済みの枠ごと画面から消える。
 *
 * トレイトを付けていないので $message->delete() は**物理削除**になる。
 * 論理削除は markAsDeleted() だけを通す（body は変更しない）。
 */
class Message extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'channel_id',
        'user_id',
        'parent_message_id',
        'body',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 属するチャンネル。返信も親と同じ channel_id を持つ（data.md 2-4）。
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * 投稿者。編集・削除できるのは本人だけの判定に使う（spec §3-4）。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 返信元（このメッセージが返信のとき）。
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    /**
     * このメッセージへの返信。件数はキャッシュ列を持たず都度数える（data.md 2-4）。
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }

    /**
     * チャンネルの本流に流れるメッセージ（返信を除く）だけに絞る。
     *
     * deleted_at では絞らない。削除済みも「このメッセージは削除されました」の枠として
     * 画面に残り続ける（data.md 3章 F-06行）。
     */
    public function scopeThreadStarters(Builder $query): Builder
    {
        return $query->whereNull('parent_message_id');
    }

    /**
     * スレッドパネル（SC-08）に並べる返信（F-16）。
     *
     * deleted_at では絞らない。削除済みの返信も「このメッセージは削除されました」の枠として
     * 残り続ける（data.md 3章 F-16行）。削除済みを外すのは検索（F-17）と公開API（F-19）だけ。
     * 並びは古いものが上・新しいものが下（questions.md「どのQにも当たらなかった回答」）。
     *
     * @return Collection<int, Message>
     */
    public function repliesForDisplay(): Collection
    {
        return $this->replies()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function isReply(): bool
    {
        return $this->parent_message_id !== null;
    }

    /**
     * 「編集済み」の印を出すかどうか。updated_at では判定しない（data.md 2-4）。
     */
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isPostedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * 本文を書き換えて「編集済み」にする（F-13）。
     *
     * edited_at は $fillable に入れていない（利用者の入力から埋まる列ではないため）ので、
     * update() の配列ではなく代入で書く。
     */
    public function editBody(string $body): void
    {
        $this->body = $body;
        $this->edited_at = now();
        $this->save();
    }

    /**
     * 論理削除（F-14）。deleted_at を立てるだけで body は残す（data.md 0章・2-4）。
     *
     * SoftDeletes を使っていないため delete() は物理削除になる。削除はこの1本を通す。
     */
    public function markAsDeleted(): void
    {
        $this->deleted_at = now();
        $this->save();
    }

    /**
     * 検索（F-17）の対象範囲に絞る。
     *
     * 検索と公開API（F-19）だけは deleted_at IS NULL を明示的に条件へ入れる（data.md 0章・4章）。
     * ここを落とすと削除済みの本文が検索結果に漏れる。
     * 可視範囲は Channel::scopeVisibleTo() の1本に通す（自分が見られるチャンネルの範囲、spec §3-6）。
     */
    public function scopeSearchableBy(Builder $query, User $user): Builder
    {
        return $query->whereNull('deleted_at')
            ->whereHas('channel', fn (Builder $channels) => $channels->visibleTo($user));
    }

    /**
     * キーワードで本文に一致するものだけに絞る（F-17）。
     *
     * 対象は本文のみ（チャンネル名・投稿者名は対象外、questions.md「どのQにも当たらなかった回答」）。
     * 返信も対象に含める＝parent_message_id では絞らない（Q-08、回答なしのため暫定）。
     * LIKE のワイルドカード（%, _, \）はエスケープしてから渡す（設計書に記載は無い実装上の安全策）。
     */
    public function scopeMatchingKeyword(Builder $query, string $keyword): Builder
    {
        $escaped = addcslashes($keyword, '%_\\');

        return $query->where('body', 'like', '%'.$escaped.'%');
    }

    /**
     * 検索結果（SC-09）で、キーワードの一致箇所を <mark> で強調した本文を返す。
     *
     * isEdited()/isDeleted() と同じ「表示用ヘルパーをモデルに置く」流儀（data.md 2-4）。
     * body は e() でエスケープしたうえで、キーワードと大小無視・複数一致で <mark> 囲みに置換する。
     */
    public function highlightedBody(string $keyword): string
    {
        $escapedBody = e($this->body);
        $escapedKeyword = e($keyword);

        if ($escapedKeyword === '') {
            return $escapedBody;
        }

        return preg_replace(
            '/'.preg_quote($escapedKeyword, '/').'/iu',
            '<mark>$0</mark>',
            $escapedBody
        );
    }
}
