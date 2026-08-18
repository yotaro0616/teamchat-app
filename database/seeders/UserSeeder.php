<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * docs/spec.md §5-4 のアカウント3件。内容は仕様書で固定されているので変えない。
     */
    public function run(): void
    {
        $users = [
            ['name' => '佐藤 太郎', 'email' => 'sato@example.com'],
            ['name' => '鈴木 花子', 'email' => 'suzuki@example.com'],
            ['name' => '高橋 健', 'email' => 'takahashi@example.com'],
        ];

        foreach ($users as $user) {
            // 'password' => 'hashed' キャストがハッシュ化するので Hash::make() は使わない。
            User::create($user + ['password' => 'password']);
        }
    }
}
