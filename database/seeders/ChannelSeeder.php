<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * docs/spec.md §5-4 のチャンネル4件。名前・種類・作成者・メンバーは仕様書で固定されているので変えない。
     *
     * 説明文は仕様書に無いため mockup/channels.html の行の表示（row__sub）から採った。
     * acceptance.md の受け入れ条件はこのデータが入っている前提で書かれている。
     */
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        $channels = [
            [
                'name' => '全体連絡',
                'description' => '全員に共有したいこと',
                'type' => 'public',
                'created_by' => 'suzuki@example.com',
                'members' => [],
            ],
            [
                'name' => '開発',
                'description' => '開発に関する相談と報告',
                'type' => 'public',
                'created_by' => 'sato@example.com',
                'members' => [],
            ],
            [
                'name' => '雑談',
                'description' => 'なんでもどうぞ',
                'type' => 'public',
                'created_by' => 'takahashi@example.com',
                'members' => [],
            ],
            [
                'name' => '採用プロジェクト',
                'description' => '採用まわりの進行',
                'type' => 'private',
                'created_by' => 'sato@example.com',
                // 作成者（佐藤 太郎）は下で自動的に入るので、ここには残りのメンバーだけを書く。
                'members' => ['suzuki@example.com'],
            ],
        ];

        foreach ($channels as $channel) {
            $created = Channel::create([
                'name' => $channel['name'],
                'description' => $channel['description'],
                'type' => $channel['type'],
                'created_by' => $users[$channel['created_by']],
            ]);

            // 作った人は種類を問わずメンバーになる（spec §3-2 / data.md 2-3）。
            // channel_user は updated_at を持たないので created_at を明示する。
            $memberIds = [$users[$channel['created_by']]];

            foreach ($channel['members'] as $email) {
                $memberIds[] = $users[$email];
            }

            foreach ($memberIds as $memberId) {
                $created->members()->attach($memberId, ['created_at' => now()]);
            }
        }
    }
}
