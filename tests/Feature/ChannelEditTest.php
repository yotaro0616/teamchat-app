<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-07 チャンネル編集（SC-06）。
 *
 * 受け入れ条件 AC-2-2、テスト観点 TP-2-08・TP-2-09（docs/design/acceptance.md）。
 */
class ChannelEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_作成者は編集画面を開ける(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->get("/channels/{$channel->id}/edit")
            ->assertOk()
            ->assertSee('チャンネルを編集する')
            ->assertSee('公開チャンネルです。公開範囲はあとから変更できません。')
            ->assertSee('チャンネルを削除する');
    }

    public function test_プライベートチャンネルの編集画面には別の補足が出る(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->privateChannel()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->get("/channels/{$channel->id}/edit")
            ->assertSee('プライベートチャンネルです。公開範囲はあとから変更できません。');
    }

    /** AC-2-2 */
    public function test_名前と説明を編集して保存するとチャンネル画面に反映される(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create([
            'name' => '開発',
            'description' => '開発に関する相談と報告',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->patch("/channels/{$channel->id}", [
            'name' => '開発チーム',
            'description' => '開発の相談と報告をする場所',
        ])->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('channels', [
            'id' => $channel->id,
            'name' => '開発チーム',
            'description' => '開発の相談と報告をする場所',
        ]);

        $this->actingAs($owner)
            ->get("/channels/{$channel->id}")
            ->assertSee('開発チーム')
            ->assertSee('開発の相談と報告をする場所');
    }

    public function test_名前を変えずに説明だけ直せる(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        // 自分自身は重複チェックの対象から外している（UpdateChannelRequest）
        $this->actingAs($owner)->patch("/channels/{$channel->id}", [
            'name' => '開発',
            'description' => '説明だけ直した',
        ])->assertRedirect("/channels/{$channel->id}");

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'description' => '説明だけ直した']);
    }

    /** TP-2-09 */
    public function test_編集画面に公開範囲の項目が出ない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);

        // 「公開範囲はあとから変更できません。」という補足は出るので、無いことを確かめるのは入力欄のほう。
        // ラジオボタン（name="type"）と、その説明文（SC-04 にしかない）が出ないことを見る。
        $this->actingAs($owner)
            ->get("/channels/{$channel->id}/edit")
            ->assertDontSee('name="type"', false)
            ->assertDontSee('ログインしている社員なら誰でも読み書きできます。')
            ->assertDontSee('追加された人だけが読み書きできます。作成者がメンバーを管理します。');
    }

    /** TP-2-09 */
    public function test_公開範囲を送っても変わらない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->patch("/channels/{$channel->id}", [
            'name' => '名前を変えた',
            'description' => '説明',
            'type' => 'private',
        ]);

        // UpdateChannelRequest は type を受け取らない（screens.md 3-6 / data.md 2-2）
        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'type' => 'public']);
    }

    public function test_名前が空では保存できない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->patch("/channels/{$channel->id}", ['name' => '', 'description' => '説明'])
            ->assertSessionHasErrors(['name' => 'チャンネル名を入力してください']);

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => '開発']);
    }

    public function test_名前51文字では保存できない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->patch("/channels/{$channel->id}", ['name' => str_repeat('あ', 51)])
            ->assertSessionHasErrors(['name' => 'チャンネル名は50文字以内で入力してください']);
    }

    public function test_ほかのチャンネルと同じ名前には変えられない(): void
    {
        $owner = User::factory()->create();
        Channel::factory()->create(['name' => '雑談']);
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->patch("/channels/{$channel->id}", ['name' => '雑談'])
            ->assertSessionHasErrors(['name' => '同じ名前のチャンネルがすでにあります']);

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => '開発']);
    }

    public function test_説明201文字では保存できない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)
            ->patch("/channels/{$channel->id}", [
                'name' => '開発',
                'description' => str_repeat('あ', 201),
            ])
            ->assertSessionHasErrors(['description' => '説明は200文字以内で入力してください']);
    }

    /** TP-2-08 */
    public function test_作成者でない社員が公開チャンネルの編集画面を直接開くと拒否される(): void
    {
        $channel = Channel::factory()->create(['created_by' => User::factory()->create()->id]);

        // 公開チャンネルは見えるので、ここは404ではなく403（permissions-api.md 1章 / behavior.md 3章）
        $this->actingAs(User::factory()->create())
            ->get("/channels/{$channel->id}/edit")
            ->assertForbidden();
    }

    /** TP-2-08 */
    public function test_作成者でない社員が保存のリクエストを直接送っても拒否される(): void
    {
        $channel = Channel::factory()->create([
            'name' => '開発',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch("/channels/{$channel->id}", ['name' => '乗っ取り'])
            ->assertForbidden();

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => '開発']);
    }

    public function test_見えないプライベートチャンネルの編集は403ではなく404になる(): void
    {
        $channel = Channel::factory()->privateChannel()->create([
            'created_by' => User::factory()->create()->id,
        ]);

        // 403を返すと「そのIDのチャンネルは存在する」と伝わってしまう（behavior.md 3章「判定の順序」）
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/channels/{$channel->id}/edit")->assertNotFound();
        $this->actingAs($stranger)->patch("/channels/{$channel->id}", ['name' => '乗っ取り'])->assertNotFound();
    }
}
