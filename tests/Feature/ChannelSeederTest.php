<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 初期データのチャンネル4件（docs/spec.md §5-4）。
 * acceptance.md の受け入れ条件はこのデータが入っている前提で書かれている。
 */
class ChannelSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(ChannelSeeder::class);
    }

    public function test_仕様書のチャンネル4件が入る(): void
    {
        $this->assertDatabaseCount('channels', 4);

        foreach ([
            ['全体連絡', 'public', 'suzuki@example.com'],
            ['開発', 'public', 'sato@example.com'],
            ['雑談', 'public', 'takahashi@example.com'],
            ['採用プロジェクト', 'private', 'sato@example.com'],
        ] as [$name, $type, $creatorEmail]) {
            $this->assertDatabaseHas('channels', [
                'name' => $name,
                'type' => $type,
                'created_by' => User::where('email', $creatorEmail)->value('id'),
            ]);
        }
    }

    public function test_採用プロジェクトのメンバーは佐藤太郎と鈴木花子の2人(): void
    {
        $channel = Channel::where('name', '採用プロジェクト')->firstOrFail();

        $emails = $channel->members()->orderBy('email')->pluck('email')->all();

        $this->assertSame(['sato@example.com', 'suzuki@example.com'], $emails);
    }

    public function test_公開チャンネルのメンバーは作成者だけ(): void
    {
        // 公開チャンネルはメンバーの追加・削除の操作が要らない（spec §3-3）。
        // 作成者の行だけが作られる（data.md 2-3）。
        foreach (['全体連絡' => 'suzuki@example.com', '開発' => 'sato@example.com', '雑談' => 'takahashi@example.com'] as $name => $email) {
            $channel = Channel::where('name', $name)->firstOrFail();

            $this->assertSame([$email], $channel->members()->pluck('email')->all());
        }
    }

    public function test_チャンネルの説明が入っている(): void
    {
        foreach ([
            '全体連絡' => '全員に共有したいこと',
            '開発' => '開発に関する相談と報告',
            '雑談' => 'なんでもどうぞ',
            '採用プロジェクト' => '採用まわりの進行',
        ] as $name => $description) {
            $this->assertDatabaseHas('channels', ['name' => $name, 'description' => $description]);
        }
    }
}
