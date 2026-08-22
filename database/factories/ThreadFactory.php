<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sora\ThreadStatus;
use App\Models\Question;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->answered(),
            'asker_id' => fn (array $attributes) => Question::query()->findOrFail($attributes['question_id'])->sender_id,
            'recipient_id' => fn (array $attributes) => Question::query()->findOrFail($attributes['question_id'])->recipient_id,
            'status' => ThreadStatus::OPEN,
            'published_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ThreadStatus::CLOSED,
            'closed_at' => now(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => [
            'status' => ThreadStatus::HIDDEN,
        ]);
    }
}
