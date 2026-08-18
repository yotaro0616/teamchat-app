<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-02 ログイン（SC-01）。
 *
 * 受け入れ条件 AC-1-2・AC-1-4、テスト観点 TP-1-09・TP-1-11（docs/design/acceptance.md）。
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログインの画面が開く(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('ログイン')
            ->assertSee('アカウントをお持ちでない方は', false);
    }

    /** AC-1-2 */
    public function test_佐藤太郎のアカウントでログインするとチャンネル一覧へ移る(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->post('/login', [
            'email' => 'sato@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/channels');
        $this->assertAuthenticated();

        $this->get('/channels')
            ->assertOk()
            ->assertSee('チャンネル一覧')
            ->assertSee('佐藤 太郎');
    }

    /** AC-1-4 */
    public function test_パスワードが誤っているとログインできない(): void
    {
        User::factory()->create(['email' => 'sato@example.com', 'password' => 'password']);

        $this->post('/login', [
            'email' => 'sato@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['password' => 'メールアドレスまたはパスワードが正しくありません']);

        $this->assertGuest();
    }

    public function test_登録されていないメールアドレスでも同じ文言になる(): void
    {
        // screens.md 3-1: メールとパスワードのどちらが誤りかは教えない
        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors(['password' => 'メールアドレスまたはパスワードが正しくありません']);

        $this->assertGuest();
    }

    public function test_メールアドレスが空だとログインできない(): void
    {
        $this->post('/login', ['email' => '', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);

        $this->assertGuest();
    }

    public function test_パスワードが空だとログインできない(): void
    {
        $this->post('/login', ['email' => 'sato@example.com', 'password' => ''])
            ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);

        $this->assertGuest();
    }

    /** TP-1-09 */
    public function test_ログイン済みだとログインの画面は開けずチャンネル一覧へ送られる(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/channels');
    }

    /**
     * TP-1-11。behavior.md 3章のとおり、セッション切れは未ログインと同じ扱いになる
     * （Laravel標準の認証ミドルウェアに乗せている）ので、未ログインでの直接アクセスで確かめる。
     */
    public function test_未ログインでチャンネル一覧を開くとログイン画面へ送り返される(): void
    {
        $this->get('/channels')->assertRedirect('/login');
    }

    public function test_トップページはチャンネル一覧へ送る(): void
    {
        $this->get('/')->assertRedirect('/channels');
    }
}
