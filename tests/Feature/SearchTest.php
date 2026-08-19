<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\MessageSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-17 検索（SC-09）。
 *
 * 受け入れ条件 AC-6-1〜AC-6-3、テスト観点 TP-6-01〜TP-6-05（docs/design/acceptance.md）。
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function seedInitialData(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
        $this->seed(MessageSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** AC-6-1 */
    public function test_開発チャンネルのメッセージをキーワードで検索するとチャンネル名投稿者日時付きで出る(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/search?q=リリース')
            ->assertOk()
            ->assertSee('# 開発', false)
            ->assertSee('鈴木 花子')
            ->assertSee('2026/08/17 09:12');
    }

    /** AC-6-2 */
    public function test_一致しないキーワードで0件メッセージが出る(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/search?q=ゾウガメ')
            ->assertOk()
            ->assertSee('「ゾウガメ」に一致するメッセージはありません')
            ->assertSee('別のキーワードで試すか、キーワードを短くしてください。');
    }

    /** AC-6-3 */
    public function test_採用プロジェクトのメンバーでない高橋健が検索しても結果に出ない(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('takahashi@example.com'))
            ->get('/search?q=募集要項')
            ->assertOk()
            ->assertSee('「募集要項」に一致するメッセージはありません');
    }

    /** TP-6-01 */
    public function test_キーワードが空のままでは検索ボタンが押せない(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/search')
            ->assertOk()
            ->assertSee('id="search-submit" disabled', false);
    }

    /** TP-6-02 */
    public function test_検索結果にキーワードの一致箇所がハイライト表示される(): void
    {
        $this->seedInitialData();

        $this->actingAs($this->user('sato@example.com'))
            ->get('/search?q=リリース')
            ->assertOk()
            ->assertSee('<mark>リリース</mark>', false);
    }

    /** TP-6-03 */
    public function test_削除済みメッセージの本文は検索結果に出ない(): void
    {
        $channel = Channel::factory()->create(['type' => 'public']);
        $message = Message::factory()->deleted()->create([
            'channel_id' => $channel->id,
            'body' => '検索対象になってはいけない本文キーワード',
        ]);

        $this->actingAs($message->user)
            ->get('/search?q=キーワード')
            ->assertOk()
            ->assertDontSee('検索対象になってはいけない本文キーワード')
            ->assertSee('「キーワード」に一致するメッセージはありません');
    }

    /** TP-6-04 */
    public function test_チャンネル名や投稿者名だけが一致してもヒットしない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        // チャンネル名「開発」・投稿者名「鈴木 花子」のどちらも検索対象外（本文のみが対象）
        $this->actingAs($this->user('sato@example.com'))
            ->get('/search?q='.urlencode($channel->name))
            ->assertOk()
            ->assertSee('「開発」に一致するメッセージはありません');
    }

    /** TP-6-05 */
    public function test_未ログインで検索画面に直接アクセスするとログイン画面へ送られる(): void
    {
        $this->get('/search')->assertRedirect('/login');
    }
}
