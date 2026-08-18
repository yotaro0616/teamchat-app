<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-04 チャンネル一覧表示（SC-03）・F-06 チャンネル表示（SC-05）の見出しまで。
 *
 * 受け入れ条件 AC-2-4、テスト観点 TP-2-13（docs/design/acceptance.md）。
 */
class ChannelTest extends TestCase
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

    public function test_チャンネル一覧に見られるチャンネルが並ぶ(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/channels')
            ->assertOk()
            ->assertSee('チャンネル一覧')
            ->assertSee('全体連絡')
            ->assertSee('開発')
            ->assertSee('雑談')
            ->assertSee('採用プロジェクト')
            ->assertSee('開発に関する相談と報告')
            ->assertSee('作成者：鈴木 花子')
            ->assertSee('公開')
            ->assertSee('プライベート');
    }

    public function test_一覧は作成順に並ぶ(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/channels')
            ->assertSeeInOrder(['全体連絡', '開発', '雑談', '採用プロジェクト']);
    }

    /** AC-2-4 */
    public function test_高橋健には採用プロジェクトが一覧にもサイドバーにも出ない(): void
    {
        $this->seedInitialData();

        $response = $this->actingAs($this->user('takahashi@example.com'))->get('/channels');

        $response->assertOk()
            ->assertSee('全体連絡')
            ->assertSee('開発')
            ->assertSee('雑談')
            // 一覧にもサイドバーにも1回も出ない（questions.md Q-04「存在自体が分からない状態にしたい」）
            ->assertDontSee('採用プロジェクト');
    }

    public function test_メンバーならプライベートチャンネルがサイドバーに出る(): void
    {
        $this->seedInitialData();

        // 鈴木 花子は採用プロジェクトのメンバー（spec §5-4）
        $this->actingAs($this->user('suzuki@example.com'))
            ->get('/channels')
            ->assertSee('採用プロジェクト');
    }

    public function test_見られるチャンネルが1つも無いと空の表示になる(): void
    {
        $owner = User::factory()->create();
        Channel::factory()->privateChannel()->create(['created_by' => $owner->id]);

        $this->actingAs(User::factory()->create())
            ->get('/channels')
            ->assertOk()
            ->assertSee('見られるチャンネルがありません')
            ->assertSee('「チャンネルを作る」から、最初のチャンネルを作ってください。');
    }

    public function test_サイドバーにチャンネル一覧を見るのリンクがある(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/channels')
            ->assertSee('チャンネル一覧を見る');
    }

    public function test_公開チャンネルは誰でも開ける(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('開発')
            ->assertSee('開発に関する相談と報告')
            ->assertSee('公開');
    }

    public function test_作成者でなければチャンネル画面に編集と削除のボタンが出ない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        // 開発の作成者は佐藤 太郎。高橋 健には出ない（screens.md 3-5）
        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertDontSee('>編集<', false)
            ->assertDontSee('>削除<', false);
    }

    public function test_作成者にはチャンネル画面に編集と削除のボタンが出る(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('編集')
            ->assertSee('削除')
            ->assertSee(route('channels.edit', $channel), false);
    }

    public function test_メンバーならプライベートチャンネルを開ける(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();

        $this->actingAs($this->user('suzuki@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('採用プロジェクト');
    }

    /** TP-2-13 */
    public function test_メンバーでないプライベートチャンネルの_ur_lを直接叩くと404になる(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();

        // 403ではなく404。存在有無で応答を変えると存在自体が漏れる（behavior.md 3章）
        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertNotFound();
    }

    public function test_存在しないチャンネルも同じ404になる(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('takahashi@example.com'))
            ->get('/channels/999999')
            ->assertNotFound();
    }

    public function test_未ログインではチャンネルを開けない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->get("/channels/{$channel->id}")->assertRedirect('/login');
    }
}
