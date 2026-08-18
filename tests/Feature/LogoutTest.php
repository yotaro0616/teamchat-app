<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-03 ログアウト。
 *
 * 受け入れ条件 AC-1-3、テスト観点 TP-1-10（docs/design/acceptance.md）。
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** AC-1-3 */
    public function test_ログアウトするとログイン画面に戻りチャンネル一覧へは入れなくなる(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->get('/channels')->assertRedirect('/login');
    }

    /**
     * TP-1-10。ブラウザの「戻る」で前の画面が復元されないよう、
     * ログインが必要な画面には no-store を付けている（behavior.md 3章）。
     */
    public function test_ログインが必要な画面はブラウザに保存させない(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/channels');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
