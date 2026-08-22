<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EngagementRepost;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EngagementRepost>
 */
class EngagementRepostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'engageable_type' => Question::class,
            'engageable_id' => Question::factory(),
        ];
    }
}
