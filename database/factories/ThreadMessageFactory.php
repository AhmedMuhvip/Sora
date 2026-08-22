<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadMessage>
 */
class ThreadMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(10),
            'sequence' => 1,
        ];
    }

    public function deleted(): static
    {
        return $this->state(fn () => [
            'deleted_at' => now(),
        ]);
    }
}
