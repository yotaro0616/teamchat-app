<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 表示名は30文字まで（spec §5-1、users.name も string('name', 30)）。
            // fake()->name() は「Prof. Marcelino Pfannerstill MD」のように31文字以上を返すことがあり、
            // そのときだけ INSERT が 1406 Data too long で落ちていた。列の上限に合わせて切る。
            'name' => mb_substr(fake()->name(), 0, 30),
            'email' => fake()->unique()->safeEmail(),
            // User の 'password' => 'hashed' キャストがハッシュ化するので平文で渡す。
            'password' => 'password',
        ];
    }
}
