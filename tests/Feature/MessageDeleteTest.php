<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-14 メッセージ削除（SC-05）。
 *
 * 受け入れ条件 AC-4-3、テスト観点 TP-4-05・TP-4-06（docs/design/acceptance.md）。
 */
class MessageDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-4-3 */
    public function test_自分のメッセージを削除するとその場所がプレースホルダに変わる(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '消したい本文']);

        $this->actingAs($message->user)
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertRedirect("/channels/{$channel->id}");

        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}")
            ->assertSee('このメッセージは削除されました')
            ->assertDontSee('消したい本文')
            // 投稿者名と日時は残す（design-guide.md §4）
            ->assertSee($message->user->name)
            ->assertSee($message->created_at->format('Y/m/d H:i'));
    }

    public function test_削除しても行と本文はデータベースに残る(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '消したい本文']);

        $this->actingAs($message->user)->delete("/channels/{$channel->id}/messages/{$message->id}");

        // 論理削除。deleted_at を立てるだけで body はクリアしない（data.md 0章・2-4）
        $message->refresh();
        $this->assertNotNull($message->deleted_at);
        $this->assertSame('消したい本文', $message->body);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_削除しても返信は残る(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $reply = Message::factory()->replyTo($parent)->create(['body' => '返信の本文']);

        $this->actingAs($parent->user)->delete("/channels/{$channel->id}/messages/{$parent->id}");

        // 返信には一切手を触れない（behavior.md 2章）
        $this->assertDatabaseHas('messages', [
            'id' => $reply->id,
            'body' => '返信の本文',
            'deleted_at' => null,
        ]);
    }

    /** TP-4-05 */
    public function test_他人が投稿したメッセージへ直接削除のリクエストを送っても拒否される(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs(User::factory()->create())
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }

    /** TP-4-05 */
    public function test_チャンネルの作成者でも他人のメッセージは削除できない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        // 作成者という立場そのものに特権は無い（permissions-api.md 1章 注記[2]）
        $this->actingAs($owner)
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }

    /** TP-4-06 */
    public function test_削除済みのメッセージへ削除のリクエストを送っても拒否される(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->deleted()->create(['channel_id' => $channel->id]);
        $deletedAt = $message->deleted_at;

        // 「削除済み」は終端状態（behavior.md 1章・3章）
        $this->actingAs($message->user)
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertForbidden();

        $message->refresh();
        $this->assertTrue($deletedAt->equalTo($message->deleted_at));
    }

    public function test_別のチャンネルのメッセージを指すurlは404になる(): void
    {
        $channel = Channel::factory()->create();
        $other = Channel::factory()->privateChannel()->create();
        $message = Message::factory()->create(['channel_id' => $other->id]);

        $this->actingAs($message->user)
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }

    public function test_見られないプライベートチャンネルのメッセージは削除できない(): void
    {
        $channel = Channel::factory()->privateChannel()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs(User::factory()->create())
            ->delete("/channels/{$channel->id}/messages/{$message->id}")
            ->assertNotFound();
    }

    public function test_未ログインでは削除できない(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->delete("/channels/{$channel->id}/messages/{$message->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }
}
