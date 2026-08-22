<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    public function definition(): array
    {
        $provider = fake()->randomElement(['google', 'apple']);

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'provider_user_id' => $provider.'_'.fake()->unique()->uuid(),
            'provider_email' => fake()->unique()->safeEmail(),
        ];
    }
}
