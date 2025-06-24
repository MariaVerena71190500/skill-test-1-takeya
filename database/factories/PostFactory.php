<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'is_draft' => false,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function draft()
    {
        return $this->state(fn () => [
            'is_draft' => true,
            'published_at' => null,
        ]);
    }
}
