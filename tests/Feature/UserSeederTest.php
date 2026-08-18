<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 初期データのアカウント3件（docs/spec.md §5-4）。
 * acceptance.md の受け入れ条件はこのデータが入っている前提で書かれている。
 */
class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_仕様書のアカウント3件が入る(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 3);

        foreach ([
            '佐藤 太郎' => 'sato@example.com',
            '鈴木 花子' => 'suzuki@example.com',
            '高橋 健' => 'takahashi@example.com',
        ] as $name => $email) {
            $this->assertDatabaseHas('users', ['name' => $name, 'email' => $email]);
        }
    }

    public function test_3件ともパスワードpasswordでログインできる(): void
    {
        $this->seed(UserSeeder::class);

        foreach (['sato@example.com', 'suzuki@example.com', 'takahashi@example.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])
                ->assertRedirect('/channels');

            $this->post('/logout');
        }
    }
}
