<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-08 チャンネル削除（SC-06 の削除確認）。
 *
 * 受け入れ条件 AC-2-3、テスト観点 TP-2-10〜TP-2-12（docs/design/acceptance.md）。
 *
 * TP-2-12 のうちメッセージ・返信の道連れは、messages テーブルが実装単位(4)で作られるため
 * この単位では確かめられない。ここではメンバー行（channel_user）が消えることまでを見る。
 */
class ChannelDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-2-3 */
    public function test_確認欄にチャンネル名を正しく入力すると削除できて一覧から消える(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発チーム', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->delete("/channels/{$channel->id}", ['name' => '開発チーム'])
            ->assertRedirect('/channels');

        $this->assertDatabaseMissing('channels', ['id' => $channel->id]);

        $this->actingAs($owner)->get('/channels')->assertDontSee('開発チーム');
    }

    /** TP-2-10 */
    public function test_削除確認のボタンは最初は押せない状態で描かれる(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        // 一致するまで押せない（design-guide.md §4「押せない」）。
        // JSを無効にしていても disabled のままなので、誤って消えることはない。
        $this->actingAs($owner)
            ->get("/channels/{$channel->id}/edit")
            ->assertSee('id="delete-submit" type="submit" disabled', false)
            ->assertSee('data-confirm-value="開発"', false)
            ->assertSee('「開発」を削除しますか？');
    }

    public function test_削除確認カードは編集画面に常に描かれている(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['created_by' => $owner->id]);

        // 確認カードは同じページ内に常に描き、上段の「削除する」はページ内アンカー
        // （permissions-api.md 2章。追加の画面やJSは使わない）
        $this->actingAs($owner)
            ->get("/channels/{$channel->id}/edit")
            ->assertSee('id="delete-confirm"', false)
            ->assertSee('href="#delete-confirm"', false)
            ->assertSee('メッセージ 0件と返信 0件も削除されます。この操作は取り消せません。確認のためチャンネル名を入力してください。');
    }

    /** TP-2-11 */
    public function test_確認の入力が一致しないリクエストを直接送っても削除されない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        // 一致の確認はサーバ側でも必ず再検証する（behavior.md 3章）。
        // この欄はエラー文を出さない決めなので、編集画面へ戻すだけ（permissions-api.md 2章の補足）。
        $this->actingAs($owner)
            ->delete("/channels/{$channel->id}", ['name' => '開発チーム'])
            ->assertRedirect("/channels/{$channel->id}/edit");

        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    /** TP-2-11 */
    public function test_確認の入力が空のリクエストでも削除されない(): void
    {
        $owner = User::factory()->create();
        $channel = Channel::factory()->create(['name' => '開発', 'created_by' => $owner->id]);

        $this->actingAs($owner)
            ->delete("/channels/{$channel->id}", [])
            ->assertRedirect("/channels/{$channel->id}/edit");

        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    /** TP-2-12 */
    public function test_チャンネルを削除するとメンバー行も消える(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $channel = Channel::factory()->privateChannel()->create([
            'name' => '採用プロジェクト',
            'created_by' => $owner->id,
        ]);
        $channel->members()->attach($member->id, ['created_at' => now()]);

        $this->assertDatabaseCount('channel_user', 2);

        $this->actingAs($owner)->delete("/channels/{$channel->id}", ['name' => '採用プロジェクト']);

        // channels の物理削除に channel_user が ON DELETE CASCADE で道連れになる（data.md 2-2）
        $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
        $this->assertDatabaseCount('channel_user', 0);
    }

    public function test_作成者でない社員は削除できない(): void
    {
        $channel = Channel::factory()->create([
            'name' => '開発',
            'created_by' => User::factory()->create()->id,
        ]);

        // 公開チャンネルは見えるので403（作成者に特権は無いのと同じく、作成者以外に削除の権限は無い）
        $this->actingAs(User::factory()->create())
            ->delete("/channels/{$channel->id}", ['name' => '開発'])
            ->assertForbidden();

        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    public function test_見えないプライベートチャンネルの削除は403ではなく404になる(): void
    {
        $channel = Channel::factory()->privateChannel()->create([
            'name' => '採用プロジェクト',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->delete("/channels/{$channel->id}", ['name' => '採用プロジェクト'])
            ->assertNotFound();

        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }

    public function test_未ログインでは削除できない(): void
    {
        $channel = Channel::factory()->create(['name' => '開発']);

        $this->delete("/channels/{$channel->id}", ['name' => '開発'])->assertRedirect('/login');

        $this->assertDatabaseHas('channels', ['id' => $channel->id]);
    }
}
