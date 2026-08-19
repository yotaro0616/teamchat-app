<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-11 メンバー削除（SC-07）。
 *
 * 受け入れ条件 AC-3-2・AC-3-3、テスト観点 TP-3-05〜TP-3-07（docs/design/acceptance.md）。
 */
class MemberRemoveTest extends TestCase
{
    use RefreshDatabase;

    private function seedInitialData(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function channel(string $name): Channel
    {
        return Channel::where('name', $name)->firstOrFail();
    }

    /** AC-3-2 */
    public function test_メンバーを外せる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $suzuki = $this->user('suzuki@example.com');

        $this->actingAs($this->user('sato@example.com'))
            ->delete("/channels/{$channel->id}/members/{$suzuki->id}")
            ->assertRedirect("/channels/{$channel->id}/members");

        $this->assertDatabaseMissing('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $suzuki->id,
        ]);

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSee('メンバー　1人')
            ->assertDontSee('suzuki@example.com');
    }

    public function test_外された人にはそのチャンネルが見えなくなる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $suzuki = $this->user('suzuki@example.com');

        $this->actingAs($this->user('sato@example.com'))
            ->delete("/channels/{$channel->id}/members/{$suzuki->id}");

        // 非メンバーになったので、一覧にも出ず直接アクセスは404（behavior.md 3章）
        $this->actingAs($suzuki)->get('/channels')->assertDontSee('採用プロジェクト');
        $this->actingAs($suzuki)->get("/channels/{$channel->id}")->assertNotFound();
    }

    /** questions.md Q-05 の暫定（data.md 2-3）。外しても過去のメッセージは消さない */
    public function test_外しても過去のメッセージは残る(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $suzuki = $this->user('suzuki@example.com');

        $message = Message::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $suzuki->id,
            'body' => '外される前に書いた本文',
        ]);

        $this->actingAs($this->user('sato@example.com'))
            ->delete("/channels/{$channel->id}/members/{$suzuki->id}");

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'body' => '外される前に書いた本文',
            'deleted_at' => null,
        ]);

        // 残ったメンバー（作成者）には投稿者名も本文も見え続ける
        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('外される前に書いた本文')
            ->assertSee('鈴木 花子');
    }

    /** AC-3-3 / TP-3-05 のサーバ側 */
    public function test_作成者を直接外そうとすると403(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $sato = $this->user('sato@example.com');

        // 画面ではボタンを押せない状態にしているが、UIの制御を判定の代わりにしない（behavior.md 3章）
        $this->actingAs($sato)
            ->delete("/channels/{$channel->id}/members/{$sato->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $sato->id,
        ]);
    }

    public function test_メンバーでない相手を外そうとしても壊れない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $takahashi = $this->user('takahashi@example.com');

        // 消す行が無いだけ。新しいエラー文は作らずそのまま戻す（permissions-api.md 2章の補足）
        $this->actingAs($this->user('sato@example.com'))
            ->delete("/channels/{$channel->id}/members/{$takahashi->id}")
            ->assertRedirect("/channels/{$channel->id}/members");

        $this->assertDatabaseCount('channel_user', 5);
    }

    public function test_存在しない利用者を外そうとすると404(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->delete("/channels/{$channel->id}/members/999999")
            ->assertNotFound();
    }

    /** TP-3-07 */
    public function test_メンバーだが作成者でない人は外せない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $sato = $this->user('sato@example.com');

        $this->actingAs($this->user('suzuki@example.com'))
            ->delete("/channels/{$channel->id}/members/{$sato->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('channel_user', 5);
    }

    /** TP-3-06 */
    public function test_メンバーでない人からの削除は404になる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $suzuki = $this->user('suzuki@example.com');

        // 403にすると存在が漏れる（behavior.md 3章「判定の順序」）
        $this->actingAs($this->user('takahashi@example.com'))
            ->delete("/channels/{$channel->id}/members/{$suzuki->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $suzuki->id,
        ]);
    }

    public function test_公開チャンネルでは作成者でも外せない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('開発');
        $sato = $this->user('sato@example.com');

        $this->actingAs($sato)
            ->delete("/channels/{$channel->id}/members/{$sato->id}")
            ->assertForbidden();
    }

    public function test_未ログインでは外せない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');
        $suzuki = $this->user('suzuki@example.com');

        $this->delete("/channels/{$channel->id}/members/{$suzuki->id}")
            ->assertRedirect('/login');
    }
}
