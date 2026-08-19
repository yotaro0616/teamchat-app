<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-10 メンバー追加（SC-07）。
 *
 * 受け入れ条件 AC-3-1、テスト観点 TP-3-01〜TP-3-04（docs/design/acceptance.md）。
 */
class MemberAddTest extends TestCase
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

    /** AC-3-1 / TP-3-01 */
    public function test_表示名の完全一致で追加できる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $response = $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => '高橋 健']);

        // 成功したらメンバー管理画面へ戻す（PRG。permissions-api.md 2章）
        $response->assertRedirect("/channels/{$channel->id}/members");

        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user('takahashi@example.com')->id,
        ]);

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSee('メンバー　3人')
            ->assertSee('高橋 健');
    }

    /** TP-3-02 */
    public function test_メールアドレスの完全一致で追加できる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => 'takahashi@example.com'])
            ->assertRedirect("/channels/{$channel->id}/members");

        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user('takahashi@example.com')->id,
        ]);
    }

    public function test_追加した人は参加順で末尾に並ぶ(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => '高橋 健']);

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSeeInOrder(['佐藤 太郎', '鈴木 花子', '高橋 健']);
    }

    /** TP-3-03 */
    public function test_該当する社員がいないとエラーになる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => '田中 一郎'])
            ->assertSessionHasErrors(['key' => '該当する社員が見つかりません']);

        $this->assertDatabaseCount('channel_user', 5);
    }

    public function test_部分一致では追加できない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        // 完全一致だけを対象にする（screens.md 4章 / questions.md Q-06 は暫定）
        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => '高橋'])
            ->assertSessionHasErrors(['key' => '該当する社員が見つかりません']);
    }

    /** screens.md 4章の追記（2人以上に一致したときは該当なしと同じ扱い） */
    public function test_同じ表示名が2人いると該当なしと同じエラーになる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        // 表示名が重複している状況。1人に特定できないので新しい文言は作らず「該当なし」にする
        $duplicate = User::factory()->create(['name' => '高橋 健', 'email' => 'takahashi2@example.com']);

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => '高橋 健'])
            ->assertSessionHasErrors(['key' => '該当する社員が見つかりません']);

        $this->assertDatabaseMissing('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user('takahashi@example.com')->id,
        ]);
        $this->assertDatabaseMissing('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $duplicate->id,
        ]);
    }

    public function test_重複した表示名でもメールアドレスなら特定できる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        User::factory()->create(['name' => '高橋 健', 'email' => 'takahashi2@example.com']);

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => 'takahashi@example.com'])
            ->assertRedirect("/channels/{$channel->id}/members");

        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user('takahashi@example.com')->id,
        ]);
    }

    /** TP-3-04 */
    public function test_すでにメンバーの人は追加できない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => 'suzuki@example.com'])
            ->assertSessionHasErrors(['key' => 'すでにメンバーです']);

        $this->assertDatabaseCount('channel_user', 5);
    }

    public function test_作成者自身を追加しようとしてもすでにメンバーになる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => 'sato@example.com'])
            ->assertSessionHasErrors(['key' => 'すでにメンバーです']);
    }

    public function test_検索キーが空欄だとエラーになる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => ''])
            ->assertSessionHasErrors(['key' => '表示名またはメールアドレスを入力してください']);
    }

    public function test_エラーの文言が画面に出て入力が残る(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->from("/channels/{$channel->id}/members")
            ->post("/channels/{$channel->id}/members", ['key' => '田中 一郎']);

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSee('該当する社員が見つかりません')
            ->assertSee('value="田中 一郎"', false);
    }

    public function test_メンバーだが作成者でない人は追加できない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        // 空欄で送っても入力エラーではなく403。入力チェックより先に権限を見ている証拠
        // （permissions-api.md 2章の補足）
        $this->actingAs($this->user('suzuki@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => ''])
            ->assertForbidden();
    }

    public function test_メンバーでない人からの追加は404になる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        // 空欄で送っても入力エラーや403ではなく404。存在を漏らさない（behavior.md 3章）
        $this->actingAs($this->user('takahashi@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => ''])
            ->assertNotFound();
    }

    public function test_公開チャンネルには作成者でも追加できない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('開発');

        $this->actingAs($this->user('sato@example.com'))
            ->post("/channels/{$channel->id}/members", ['key' => 'takahashi@example.com'])
            ->assertForbidden();

        $this->assertDatabaseMissing('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $this->user('takahashi@example.com')->id,
        ]);
    }

    public function test_未ログインでは追加できない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->post("/channels/{$channel->id}/members", ['key' => 'takahashi@example.com'])
            ->assertRedirect('/login');
    }
}
