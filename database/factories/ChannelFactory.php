<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 名前は UNIQUE なので、テストごとに重複しないよう連番を混ぜる（data.md 2-2）。
            'name' => 'チャンネル'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->realText(30),
            'type' => 'public',
            'created_by' => User::factory(),
        ];
    }

    /**
     * 公開チャンネル。
     */
    public function publicChannel(): static
    {
        return $this->state(fn () => ['type' => 'public']);
    }

    /**
     * プライベートチャンネル。
     */
    public function privateChannel(): static
    {
        return $this->state(fn () => ['type' => 'private']);
    }

    /**
     * 作成者は種別を問わずメンバーになる（spec §3-2 / data.md 2-3）。
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Channel $channel) {
            $channel->members()->attach($channel->created_by, ['created_at' => now()]);
        });
    }
}
