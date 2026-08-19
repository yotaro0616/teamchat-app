<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-18 公開チャンネル一覧・F-19 公開チャンネルのメッセージ一覧（認証不要、`routes/api.php`）。
 *
 * 受け入れ条件 AC-7-1〜3、テスト観点 TP-7-01〜05（docs/design/acceptance.md）。
 */
class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_公開チャンネル一覧が全体連絡_開発_雑談を返す(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);

        $response = $this->getJson('/api/channels')->assertOk();

        $response->assertJsonCount(3);
        $response->assertJsonFragment([
            'name' => '全体連絡',
            'description' => '全員に共有したいこと',
        ]);
        $response->assertJsonFragment([
            'name' => '開発',
            'description' => '開発に関する相談と報告',
        ]);
        $response->assertJsonFragment([
            'name' => '雑談',
            'description' => 'なんでもどうぞ',
        ]);
        $response->assertJsonMissing(['name' => '採用プロジェクト']);
    }

    public function test_公開チャンネルが1つも無いとき空配列が返る(): void
    {
        $this->getJson('/api/channels')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_開発チャンネルのメッセージ一覧がjsonで返る(): void
    {
        $this->seed();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $response = $this->getJson("/api/channels/{$channel->id}/messages")->assertOk();

        $response->assertJsonStructure([
            '*' => ['id', 'channel_id', 'body', 'author_name', 'created_at'],
        ]);
    }

    public function test_削除済みメッセージは一覧に含まれない(): void
    {
        $channel = Channel::factory()->publicChannel()->create();
        Message::factory()->for($channel)->deleted()->create(['body' => '削除された本文']);

        $this->getJson("/api/channels/{$channel->id}/messages")
            ->assertOk()
            ->assertJsonMissing(['body' => '削除された本文']);
    }

    public function test_返信メッセージは一覧に含まれない(): void
    {
        $channel = Channel::factory()->publicChannel()->create();
        $parent = Message::factory()->for($channel)->create(['body' => '親メッセージ']);
        Message::factory()->replyTo($parent)->create(['body' => '返信本文']);

        $this->getJson("/api/channels/{$channel->id}/messages")
            ->assertOk()
            ->assertJsonMissing(['body' => '返信本文'])
            ->assertJsonFragment(['body' => '親メッセージ']);
    }

    public function test_存在しないチャンネルidで404になる(): void
    {
        $this->getJson('/api/channels/99999/messages')
            ->assertStatus(404)
            ->assertExactJson(['message' => '指定されたチャンネルが見つかりません']);
    }

    public function test_プライベートチャンネルのidで404になりチャンネル名が漏れない(): void
    {
        $channel = Channel::factory()->privateChannel()->create(['name' => '採用プロジェクト']);

        $this->get("/api/channels/{$channel->id}/messages")
            ->assertStatus(404)
            ->assertExactJson(['message' => '指定されたチャンネルが見つかりません']);
    }
}
