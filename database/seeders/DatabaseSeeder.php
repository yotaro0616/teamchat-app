<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ChannelSeeder は作成者・メンバーを users から引くので UserSeeder のあとに置く。
        // MessageSeeder は投稿者を users から、置き場所を channels から引くので最後に置く。
        $this->call([
            UserSeeder::class,
            ChannelSeeder::class,
            MessageSeeder::class,
        ]);
    }
}
