<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadLike>
 */
class ThreadLikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'thread_id' => Thread::factory(),
        ];
    }
}
