<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-05 チャンネル作成（SC-04）。
 *
 * 受け入れ条件 AC-2-1、テスト観点 TP-2-01〜TP-2-07（docs/design/acceptance.md）。
 */
class ChannelCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => '新しいチャンネル',
            'description' => '新しく作った場所',
            'type' => 'public',
        ], $overrides);
    }

    public function test_チャンネルを作る画面が開く(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/channels/create')
            ->assertOk()
            ->assertSee('チャンネルを作る')
            ->assertSee('何について話す場所かを短く書きます。')
            ->assertSee('ログインしている社員なら誰でも読み書きできます。')
            ->assertSee('追加された人だけが読み書きできます。作成者がメンバーを管理します。');
    }

    /** AC-2-1 */
    public function test_チャンネルを作ると一覧に出て作ったチャンネルの画面が開く(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/channels', $this->validInput());

        $channel = Channel::where('name', '新しいチャンネル')->firstOrFail();

        // 作成したそのチャンネルの画面へ（screens.md 2章の※設計判断）
        $response->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('channels', [
            'name' => '新しいチャンネル',
            'description' => '新しく作った場所',
            'type' => 'public',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get('/channels')->assertSee('新しいチャンネル');
    }

    public function test_作成者はそのチャンネルのメンバーになる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/channels', $this->validInput(['type' => 'private']));

        $channel = Channel::where('name', '新しいチャンネル')->firstOrFail();

        // spec §3-2「チャンネルを作った人は、そのチャンネルのメンバーになります」
        $this->assertDatabaseHas('channel_user', [
            'channel_id' => $channel->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_作ったプライベートチャンネルは作成者には見える(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/channels', $this->validInput(['type' => 'private']));

        $this->actingAs($user)->get('/channels')->assertSee('新しいチャンネル');
    }

    /** TP-2-01 */
    public function test_チャンネル名50文字ちょうどで作成できる(): void
    {
        $name = str_repeat('あ', 50);

        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['name' => $name]));

        $this->assertDatabaseHas('channels', ['name' => $name]);
    }

    /** TP-2-02 */
    public function test_チャンネル名51文字では作成できない(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['name' => str_repeat('あ', 51)]))
            ->assertSessionHasErrors(['name' => 'チャンネル名は50文字以内で入力してください']);

        $this->assertDatabaseCount('channels', 0);
    }

    /** TP-2-03 */
    public function test_チャンネル名が空では作成できない(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['name' => '']))
            ->assertSessionHasErrors(['name' => 'チャンネル名を入力してください']);

        $this->assertDatabaseCount('channels', 0);
    }

    /** TP-2-04 */
    public function test_同じ名前のチャンネルは作成できない(): void
    {
        Channel::factory()->create(['name' => '開発']);

        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['name' => '開発']))
            ->assertSessionHasErrors(['name' => '同じ名前のチャンネルがすでにあります']);

        $this->assertDatabaseCount('channels', 1);
    }

    /** TP-2-05 */
    public function test_説明を入力せずに作成できる(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['description' => '']));

        $this->assertDatabaseHas('channels', ['name' => '新しいチャンネル', 'description' => null]);
    }

    /** TP-2-06 */
    public function test_説明200文字ちょうどで作成できる(): void
    {
        $description = str_repeat('あ', 200);

        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['description' => $description]));

        $this->assertDatabaseHas('channels', ['description' => $description]);
    }

    /** TP-2-07 */
    public function test_説明201文字では作成できない(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['description' => str_repeat('あ', 201)]))
            ->assertSessionHasErrors(['description' => '説明は200文字以内で入力してください']);

        $this->assertDatabaseCount('channels', 0);
    }

    public function test_公開範囲が不正な値では作成できない(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/channels', $this->validInput(['type' => 'secret']))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('channels', 0);
    }

    public function test_未ログインではチャンネルを作れない(): void
    {
        $this->post('/channels', $this->validInput())->assertRedirect('/login');

        $this->assertDatabaseCount('channels', 0);
    }
}
