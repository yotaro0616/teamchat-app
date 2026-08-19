<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-13 メッセージ編集（SC-05 の編集中の状態）。
 *
 * 受け入れ条件 AC-4-2、テスト観点 TP-4-05・TP-4-06・TP-4-08（docs/design/acceptance.md）。
 */
class MessageEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_本人は編集の画面を開ける(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '直す前の本文']);

        // 専用の画面は作らず、同じチャンネル画面をその1件だけ編集状態にして描き直す
        // （permissions-api.md 2章の※設計判断）
        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}/messages/{$message->id}/edit")
            ->assertOk()
            ->assertSee('直す前の本文')
            ->assertSee('保存')
            ->assertSee('やめる')
            ->assertSee('name="body"', false);
    }

    /** AC-4-2 */
    public function test_編集して保存すると本文が変わり編集済みの印が付く(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '直す前の本文']);

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '直したあとの本文'])
            ->assertRedirect("/channels/{$channel->id}");

        $message->refresh();
        $this->assertSame('直したあとの本文', $message->body);
        $this->assertNotNull($message->edited_at);

        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}")
            ->assertSee('直したあとの本文')
            ->assertSee('編集済み')
            ->assertDontSee('直す前の本文');
    }

    /** TP-4-08 */
    public function test_何度編集しても編集済みの印は1つのまま(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '1回目']);
        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '2回目']);

        // 編集回数・期限の制限は無い（questions.md「どのQにも当たらなかった回答」）
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => '2回目']);

        $response = $this->actingAs($message->user)->get("/channels/{$channel->id}");
        $this->assertSame(1, substr_count($response->getContent(), 'msg__edited'));
    }

    public function test_本文1000文字ちょうどで保存できる(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);
        $body = str_repeat('あ', 1000);

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => $body])
            ->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => $body]);
    }

    public function test_本文1001文字や空では保存できない(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '元の本文']);

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => str_repeat('あ', 1001)])
            ->assertSessionHasErrors('body');

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => '元の本文']);
    }

    /** TP-4-05 */
    public function test_他人が投稿したメッセージへ直接編集のリクエストを送っても拒否される(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '元の本文']);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("/channels/{$channel->id}/messages/{$message->id}/edit")
            ->assertForbidden();

        $this->actingAs($stranger)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => '元の本文']);
    }

    /** TP-4-05 */
    public function test_チャンネルの作成者でも他人のメッセージは編集できない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);
        $message = Message::factory()->create(['channel_id' => $channel->id, 'body' => '元の本文']);

        // 作成者という立場そのものに特権は無い（permissions-api.md 1章 注記[2]）
        $this->actingAs($owner)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => '元の本文']);
    }

    /** TP-4-06 */
    public function test_削除済みのメッセージへ編集のリクエストを送っても拒否される(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->deleted()->create(['channel_id' => $channel->id, 'body' => '元の本文']);

        // 「削除済み」は終端状態で、本人でも編集には戻せない（behavior.md 1章・3章）
        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}/messages/{$message->id}/edit")
            ->assertForbidden();

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => '元の本文']);
    }

    public function test_別のチャンネルのメッセージを指すurlは404になる(): void
    {
        $channel = Channel::factory()->create();
        $other = Channel::factory()->privateChannel()->create();
        $message = Message::factory()->create(['channel_id' => $other->id]);

        // 見えるチャンネルのURLの下に別チャンネルのメッセージIDを差し込んでも届かない
        // （permissions-api.md 2章の補足）。403にすると存在が漏れる
        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}/messages/{$message->id}/edit")
            ->assertNotFound();

        $this->actingAs($message->user)
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertNotFound();
    }

    public function test_見られないプライベートチャンネルのメッセージは編集できない(): void
    {
        $channel = Channel::factory()->privateChannel()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->actingAs(User::factory()->create())
            ->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertNotFound();
    }

    public function test_返信は暫定でチャンネル画面へ送り返す(): void
    {
        $channel = Channel::factory()->create();
        $parent = Message::factory()->create(['channel_id' => $channel->id]);
        $reply = Message::factory()->replyTo($parent)->create();

        // スレッド表示（SC-08）は実装単位(5)。それまでの暫定（permissions-api.md 2章の補足）
        $this->actingAs($reply->user)
            ->get("/channels/{$channel->id}/messages/{$reply->id}/edit")
            ->assertRedirect("/channels/{$channel->id}");

        $this->actingAs($reply->user)
            ->patch("/channels/{$channel->id}/messages/{$reply->id}", ['body' => '返信を直した'])
            ->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('messages', ['id' => $reply->id, 'body' => '返信を直した']);
    }

    public function test_未ログインでは編集できない(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->create(['channel_id' => $channel->id]);

        $this->patch("/channels/{$channel->id}/messages/{$message->id}", ['body' => '書き換え'])
            ->assertRedirect('/login');
    }
}
