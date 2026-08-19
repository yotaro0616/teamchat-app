<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
}
