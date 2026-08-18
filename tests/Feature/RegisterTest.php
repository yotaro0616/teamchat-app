<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-01 新規登録（SC-02）。
 *
 * 受け入れ条件 AC-1-1、テスト観点 TP-1-01〜TP-1-08（docs/design/acceptance.md）。
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => '田中 一郎',
            'email' => 'tanaka@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_新規登録の画面が開く(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('新規登録')
            ->assertSee('メッセージに表示される名前です。')
            ->assertSee('8文字以上で入力してください。');
    }

    /** AC-1-1 */
    public function test_登録するとアカウントが作られてログイン画面に移り自動ログインはしない(): void
    {
        $response = $this->post('/register', $this->validInput());

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'name' => '田中 一郎',
            'email' => 'tanaka@example.com',
        ]);
        // permissions-api.md 2章「自動ログインにしない」
        $this->assertGuest();
    }

    /** TP-1-01 */
    public function test_表示名30文字ちょうどで登録できる(): void
    {
        $this->post('/register', $this->validInput(['name' => str_repeat('あ', 30)]))
            ->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['name' => str_repeat('あ', 30)]);
    }

    /** TP-1-02 */
    public function test_表示名31文字は登録できない(): void
    {
        $this->post('/register', $this->validInput(['name' => str_repeat('あ', 31)]))
            ->assertSessionHasErrors(['name' => '表示名は30文字以内で入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /** TP-1-03 */
    public function test_表示名が空だと登録できない(): void
    {
        $this->post('/register', $this->validInput(['name' => '']))
            ->assertSessionHasErrors(['name' => '表示名を入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /** TP-1-04 */
    public function test_パスワード8文字ちょうどで登録できる(): void
    {
        $this->post('/register', $this->validInput([
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]))->assertRedirect('/login');

        $this->assertDatabaseCount('users', 1);
    }

    /** TP-1-05 */
    public function test_パスワード7文字は登録できない(): void
    {
        $this->post('/register', $this->validInput([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]))->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);

        $this->assertDatabaseCount('users', 0);
    }

    /** TP-1-06 */
    public function test_パスワードと確認用が一致しないと登録できない(): void
    {
        $this->post('/register', $this->validInput(['password_confirmation' => 'password2']))
            ->assertSessionHasErrors(['password_confirmation' => 'パスワードが一致しません']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_確認用が空だと登録できない(): void
    {
        $this->post('/register', $this->validInput(['password_confirmation' => '']))
            ->assertSessionHasErrors(['password_confirmation' => 'パスワード（確認用）を入力してください']);
    }

    public function test_パスワードが空だと登録できない(): void
    {
        $this->post('/register', $this->validInput([
            'password' => '',
            'password_confirmation' => '',
        ]))->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** TP-1-07 */
    public function test_登録済みのメールアドレスでは登録できない(): void
    {
        User::factory()->create(['email' => 'tanaka@example.com']);

        $this->post('/register', $this->validInput())
            ->assertSessionHasErrors(['email' => 'このメールアドレスはすでに登録されています']);

        $this->assertDatabaseCount('users', 1);
    }

    /** TP-1-08 */
    public function test_メールアドレスの形式が不正だと登録できない(): void
    {
        $this->post('/register', $this->validInput(['email' => 'tanaka']))
            ->assertSessionHasErrors(['email' => 'メールアドレスの形式が正しくありません']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_メールアドレスが空だと登録できない(): void
    {
        $this->post('/register', $this->validInput(['email' => '']))
            ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** TP-1-09 */
    public function test_ログイン済みだと新規登録の画面は開けずチャンネル一覧へ送られる(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect('/channels');
    }
}
