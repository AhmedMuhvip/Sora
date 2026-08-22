<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sora\QuestionStatus;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'body' => fake()->sentence(12),
            'is_anonymous' => false,
            'status' => QuestionStatus::PENDING,
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn () => [
            'is_anonymous' => true,
        ]);
    }

    public function answered(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::ANSWERED,
            'answered_at' => now(),
        ]);
    }

    public function ignored(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::IGNORED,
            'ignored_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::ARCHIVED,
            'archived_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn () => [
            'status' => QuestionStatus::DELETED,
            'deleted_at' => now(),
        ]);
    }
}
