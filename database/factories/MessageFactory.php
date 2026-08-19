<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'parent_message_id' => null,
            'body' => 'テスト用のメッセージ本文',
            'edited_at' => null,
            'deleted_at' => null,
        ];
    }

    /**
     * 編集済み（本文末尾に「編集済み」の印が付く状態）。
     */
    public function edited(): static
    {
        return $this->state(fn () => ['edited_at' => now()]);
    }

    /**
     * 削除済み。body はクリアしない（data.md 0章）。
     */
    public function deleted(): static
    {
        return $this->state(fn () => ['deleted_at' => now()]);
    }

    /**
     * 指定したメッセージへの返信。channel_id は親と必ず一致させる（data.md 2-4）。
     */
    public function replyTo(Message $parent): static
    {
        return $this->state(fn () => [
            'parent_message_id' => $parent->id,
            'channel_id' => $parent->channel_id,
        ]);
    }
}
