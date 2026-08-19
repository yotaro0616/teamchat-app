<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Message;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\MessageSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 初期データのメッセージ（docs/spec.md §5-4）。
 *
 * acceptance.md の受け入れ条件はこのデータが入っている前提で書かれているので、
 * 仕様書が挙げている条件を1つずつ確かめる。
 */
class MessageSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
        $this->seed(MessageSeeder::class);
    }

    private function channel(string $name): Channel
    {
        return Channel::where('name', $name)->firstOrFail();
    }

    public function test_公開チャンネルにはそれぞれ10件前後のメッセージが入る(): void
    {
        foreach (['全体連絡', '開発', '雑談'] as $name) {
            $count = $this->channel($name)->messageCount();

            $this->assertGreaterThanOrEqual(8, $count, "{$name} のメッセージが少ない");
            $this->assertLessThanOrEqual(12, $count, "{$name} のメッセージが多い");
        }
    }

    public function test_プライベートチャンネルにも数件入る(): void
    {
        $this->assertGreaterThanOrEqual(3, $this->channel('採用プロジェクト')->messageCount());
    }

    public function test_書いた人と日時がばらけている(): void
    {
        $messages = $this->channel('開発')->messages()->threadStarters()->get();

        $this->assertSame(3, $messages->pluck('user_id')->unique()->count());
        $this->assertGreaterThan(1, $messages->map(fn ($m) => $m->created_at->toDateString())->unique()->count());
    }

    public function test_返信が付いたメッセージが2件以上あり_うち1件は返信が3件以上ある(): void
    {
        $counts = Message::whereNull('parent_message_id')
            ->withCount('replies')
            ->get()
            ->pluck('replies_count')
            ->filter(fn ($count) => $count > 0);

        $this->assertGreaterThanOrEqual(2, $counts->count());
        $this->assertGreaterThanOrEqual(3, $counts->max());
    }

    public function test_返信は親と同じチャンネルに属する(): void
    {
        // 親の channel_id と常に一致する意図的な冗長化（data.md 2-4）
        foreach (Message::whereNotNull('parent_message_id')->with('parent')->get() as $reply) {
            $this->assertSame($reply->parent->channel_id, $reply->channel_id);
        }
    }

    public function test_返信は1段までで返信への返信は無い(): void
    {
        // 返信は1段まで（questions.md Q-07の回答）
        foreach (Message::whereNotNull('parent_message_id')->with('parent')->get() as $reply) {
            $this->assertNull($reply->parent->parent_message_id);
        }
    }

    public function test_編集済みのメッセージが1件以上ある(): void
    {
        $this->assertGreaterThanOrEqual(1, Message::whereNotNull('edited_at')->count());
    }

    public function test_削除済みのメッセージが1件以上あり本文は残っている(): void
    {
        $deleted = Message::whereNotNull('deleted_at')->get();

        $this->assertGreaterThanOrEqual(1, $deleted->count());

        // 削除済みでも body はクリアしない（data.md 0章）
        foreach ($deleted as $message) {
            $this->assertNotSame('', $message->body);
        }
    }
}
