<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sora\ReportReason;
use App\Enums\Sora\ReportStatus;
use App\Models\Report;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Thread::class,
            'reportable_id' => Thread::factory(),
            'reason' => ReportReason::OTHER,
            'details' => fake()->sentence(),
            'status' => ReportStatus::PENDING,
        ];
    }

    public function reviewing(): static
    {
        return $this->state(fn () => [
            'status' => ReportStatus::REVIEWING,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ReportStatus::RESOLVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ReportStatus::REJECTED,
        ]);
    }
}
