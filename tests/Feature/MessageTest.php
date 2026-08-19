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
 * F-06 チャンネル表示（SC-05）のメッセージ一覧。
 *
 * 受け入れ条件 AC-4-4、テスト観点 TP-4-07（docs/design/acceptance.md）。
 */
class MessageTest extends TestCase
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

    public function test_チャンネルを開くとメッセージが投稿者名と日時付きで並ぶ(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertOk()
            ->assertSee('おはようございます。9月リリースの日程を今週中に確定させたいです。金曜の夕方までに、残っているタスクを各自共有してもらえますか。')
            ->assertSee('鈴木 花子')
            ->assertSee('2026/08/17 09:12');
    }

    /** TP-4-07 */
    public function test_メッセージは投稿日時の古い順から新しい順に並ぶ(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSeeInOrder([
                '2026/08/17 09:12',
                '2026/08/17 09:20',
                '2026/08/17 09:31',
                '2026/08/17 14:32',
                '2026/08/18 09:35',
            ]);
    }

    public function test_日付の変わり目に日付区切りが出る(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        // モックアップ実例の書式（screens.md 3-5）。2026年8月17日は月曜、18日は火曜
        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('2026年8月17日（月）')
            ->assertSee('2026年8月18日（火）');
    }

    public function test_編集済みのメッセージには編集済みの印が付く(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('承知しました。検索まわりの実装は木曜に終わる見込みです。')
            ->assertSee('編集済み');
    }

    public function test_削除済みのメッセージは本文の代わりにプレースホルダになる(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        // 削除済みでも行は残り、投稿者名と日時はそのまま（design-guide.md §4）。
        // body は消していないが、画面には出さない（data.md 0章）
        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('このメッセージは削除されました')
            ->assertSee('2026/08/17 10:05')
            ->assertDontSee('ステージングのログインが通らないのですが、他の方も同じでしょうか。');
    }

    public function test_スレッドの返信は本流に混ざらない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        // 返信は parent_message_id が入っている行で、チャンネルの本流には出ない（data.md 3章 F-06行）
        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertDontSee('承知しました。今日中にこちらの残タスクをまとめて共有します。');
    }

    /** AC-4-4 */
    public function test_他の人が投稿したメッセージには編集と削除の操作アイコンが出ない(): void
    {
        $channel = Channel::factory()->create();
        $mine = Message::factory()->create(['channel_id' => $channel->id]);
        $others = Message::factory()->create(['channel_id' => $channel->id]);

        $response = $this->actingAs($mine->user)->get("/channels/{$channel->id}");

        $response->assertOk()
            ->assertSee(route('messages.edit', [$channel, $mine]), false)
            ->assertDontSee(route('messages.edit', [$channel, $others]), false)
            ->assertDontSee(route('messages.destroy', [$channel, $others]), false);
    }

    public function test_自分の削除済みメッセージには編集と削除の操作アイコンが出ない(): void
    {
        $channel = Channel::factory()->create();
        $message = Message::factory()->deleted()->create(['channel_id' => $channel->id]);

        // 「削除済み」は終端状態（behavior.md 1章 / screens.md 3-5）
        $this->actingAs($message->user)
            ->get("/channels/{$channel->id}")
            ->assertDontSee(route('messages.edit', [$channel, $message]), false);
    }

    public function test_見られないプライベートチャンネルのメッセージは読めない(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();

        // 高橋 健はメンバーではない。403ではなく404（behavior.md 3章）
        $this->actingAs($this->user('takahashi@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertNotFound();
    }

    public function test_投稿欄のプレースホルダにチャンネル名が入る(): void
    {
        $this->seedInitialData();

        $channel = Channel::where('name', '開発')->firstOrFail();

        $this->actingAs($this->user('sato@example.com'))
            ->get("/channels/{$channel->id}")
            ->assertSee('#開発 にメッセージを送る')
            ->assertSee('0 / 1,000 文字');
    }
}
