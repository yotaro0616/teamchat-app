<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\MessageSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-12 メッセージ投稿（SC-05）。
 *
 * 受け入れ条件 AC-4-1、テスト観点 TP-4-01〜TP-4-03（docs/design/acceptance.md）。
 * TP-4-02・TP-4-03 の「送信ボタンが押せない」はJSの挙動なので、ここで確かめられるのは
 * 最初に描かれる disabled の状態と、サーバ側の入力チェックが同じ上限ではじくことまで。
 */
class MessagePostTest extends TestCase
{
    use RefreshDatabase;

    /** AC-4-1 */
    public function test_開発チャンネルに投稿すると一覧の一番下に投稿者名と日時付きで出る(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
        $this->seed(MessageSeeder::class);

        $channel = Channel::where('name', '開発')->firstOrFail();
        $user = User::where('email', 'sato@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post("/channels/{$channel->id}/messages", ['body' => '会議の資料を共有しました'])
            ->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('messages', [
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'parent_message_id' => null,
            'body' => '会議の資料を共有しました',
            'edited_at' => null,
            'deleted_at' => null,
        ]);

        // 古い順に並ぶので、いま投稿したものが最後に出る
        $this->actingAs($user)
            ->get("/channels/{$channel->id}")
            ->assertSeeInOrder(['2026/08/18 09:35', '佐藤 太郎', '会議の資料を共有しました']);
    }

    /** TP-4-01 */
    public function test_本文1000文字ちょうどで投稿できる(): void
    {
        $channel = Channel::factory()->create();
        $body = str_repeat('あ', 1000);

        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages", ['body' => $body])
            ->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('messages', ['body' => $body]);
    }

    /** TP-4-02 */
    public function test_本文1001文字では投稿できない(): void
    {
        $channel = Channel::factory()->create();

        // 画面では送信ボタンが押せない状態にしてあるが、サーバ側でも必ずはじく（screens.md 4章）
        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages", ['body' => str_repeat('あ', 1001)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    /** TP-4-03 */
    public function test_本文が空では投稿できない(): void
    {
        $channel = Channel::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages", ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }

    /** TP-4-03 */
    public function test_投稿欄の送信ボタンは最初は押せない状態で描かれる(): void
    {
        $channel = Channel::factory()->create();

        // 空欄のあいだは押せない（screens.md 4章 / design-guide.md §4「押せない」）。
        // JSを無効にしていても、この初期状態のままなので空のまま送られることはない
        $this->actingAs(User::factory()->create())
            ->get("/channels/{$channel->id}")
            ->assertSee('id="composer-submit"', false)
            ->assertSee('id="composer-submit" disabled', false);
    }

    public function test_見られないプライベートチャンネルには投稿できない(): void
    {
        $channel = Channel::factory()->privateChannel()->create();

        // 403にすると存在が漏れる（behavior.md 3章）
        $this->actingAs(User::factory()->create())
            ->post("/channels/{$channel->id}/messages", ['body' => 'こんにちは'])
            ->assertNotFound();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_メンバーならプライベートチャンネルに投稿できる(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();
        $member = User::where('email', 'suzuki@example.com')->firstOrFail();

        $this->actingAs($member)
            ->post("/channels/{$channel->id}/messages", ['body' => '面接の日程を共有します'])
            ->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('messages', ['channel_id' => $channel->id, 'body' => '面接の日程を共有します']);
    }

    public function test_未ログインでは投稿できない(): void
    {
        $channel = Channel::factory()->create();

        $this->post("/channels/{$channel->id}/messages", ['body' => 'こんにちは'])->assertRedirect('/login');

        $this->assertDatabaseCount('messages', 0);
    }
}
