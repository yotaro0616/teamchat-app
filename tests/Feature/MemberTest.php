<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-09 メンバー一覧表示（SC-07）と、そこへの導線（SC-03 の行内アクション）。
 *
 * 受け入れ条件 AC-3-3・AC-3-4、テスト観点 TP-3-05〜TP-3-07（docs/design/acceptance.md）。
 */
class MemberTest extends TestCase
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

    public function test_作成者はメンバー管理画面を開ける(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertOk()
            ->assertSee('採用プロジェクト')
            ->assertSee('採用まわりの進行　·　追加された人だけが読み書きできます。')
            ->assertSee('プライベート')
            ->assertSee('チャンネルを開く')
            ->assertSee('メンバーを追加する')
            ->assertSee('表示名またはメールアドレスで探す')
            ->assertSee('追加');
    }

    public function test_メンバーが人数つきで並ぶ(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            // 「メンバー」と人数の間は全角スペース（screens.md 3-7 / mockup/members.html）
            ->assertSee('メンバー　2人')
            ->assertSee('佐藤 太郎')
            ->assertSee('sato@example.com')
            ->assertSee('鈴木 花子')
            ->assertSee('suzuki@example.com')
            // 参加した順。作成者の行が先に入るので作成者が先頭に来る（screens.md 3-7 の追記）
            ->assertSeeInOrder(['佐藤 太郎', '鈴木 花子']);
    }

    /** AC-3-3 / TP-3-05 */
    public function test_作成者の外すボタンは押せない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSee('作成者は外せません')
            // 押せないボタンには危険色を付けない（mockup/members.html）
            ->assertSee('<button class="btn btn--sm" type="button" disabled>外す</button>', false)
            // 作成者の行には「外す」を送るフォームそのものが無い
            ->assertDontSee(route('members.destroy', [$channel, $this->user('sato@example.com')]), false);
    }

    public function test_作成者以外の行には外すフォームがある(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertSee(route('members.destroy', [$channel, $this->user('suzuki@example.com')]), false);
    }

    /** AC-3-4 / TP-3-07 */
    public function test_メンバーだが作成者でない人は403(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->actingAs($this->user('suzuki@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertForbidden();
    }

    /** TP-3-06 */
    public function test_メンバーでない人は404になる(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        // 403にすると「そのIDのチャンネルはある」と伝わって存在が漏れる（behavior.md 3章）
        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertNotFound();
    }

    public function test_公開チャンネルには作成者でもメンバー管理が無い(): void
    {
        $this->seedInitialData();

        // 「開発」の作成者は佐藤 太郎。公開チャンネルにメンバー管理そのものが無いので403
        // （behavior.md 3章の追記。誰にでも見えているチャンネルなので404にはしない）
        $channel = $this->channel('開発');

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}/members")
            ->assertForbidden();
    }

    public function test_存在しないチャンネルのメンバー管理は404(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/channels/999999/members')
            ->assertNotFound();
    }

    public function test_未ログインではメンバー管理を開けない(): void
    {
        $this->seedInitialData();

        $channel = $this->channel('採用プロジェクト');

        $this->get("/channels/{$channel->id}/members")->assertRedirect('/login');
    }

    public function test_チャンネル一覧の行にメンバーボタンが出るのは作成者だけ(): void
    {
        $this->seedInitialData();

        $private = $this->channel('採用プロジェクト');
        $public = $this->channel('開発');

        // 佐藤 太郎は採用プロジェクトの作成者。公開チャンネルの行には出ない（screens.md 3-3）
        $this->actingAs($this->user('sato@example.com'))
            ->get('/channels')
            ->assertSee('メンバー')
            ->assertSee(route('members.index', $private), false)
            ->assertDontSee(route('members.index', $public), false);

        // 鈴木 花子はメンバーだが作成者ではない
        $this->actingAs($this->user('suzuki@example.com'))
            ->get('/channels')
            ->assertDontSee(route('members.index', $private), false);
    }
}
