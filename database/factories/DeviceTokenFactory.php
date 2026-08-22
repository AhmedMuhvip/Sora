<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    public function definition(): array
    {
        $token = 'fcm_'.Str::random(120);

        return [
            'user_id' => User::factory(),
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'platform' => 'ios',
            'device_id' => fake()->uuid(),
            'app_version' => '1.0.0',
            'last_used_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'disabled_at' => now(),
        ]);
    }
}
