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
        $this->call([
            UserSeeder::class,
            ChannelSeeder::class,
        ]);
    }
}
