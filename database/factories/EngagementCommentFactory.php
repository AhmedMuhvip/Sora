<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EngagementComment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EngagementComment>
 */
class EngagementCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'engageable_type' => Question::class,
            'engageable_id' => Question::factory(),
            'body' => fake()->sentence(),
        ];
    }

    public function deleted(): static
    {
        return $this->state(fn () => [
            'deleted_at' => now(),
        ]);
    }
}
