<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\VerificationType;
use App\Models\DeviceToken;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\VerificationCode;
use DateTimeInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $testUser = $this->seedUser(
            username: 'testuser',
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            bio: 'Demo account for trying the app. Password is password.',
        );
        $this->seedUser(
            username: 'sora',
            firstName: 'Sora',
            lastName: 'Official',
            email: 'sora@example.com',
            bio: 'Official Sora account sharing product notes and community answers.',
        );
        $sara = $this->seedUser(
            username: 'sara',
            firstName: 'Sara',
            lastName: 'Example',
            email: 'sara@example.com',
            bio: 'Backend engineer answering questions about programming.',
        );
        $this->seedUser(
            username: 'ahmed',
            firstName: 'Ahmed',
            lastName: 'Example',
            email: 'ahmed@example.com',
            bio: 'Curious Sora user who asks detailed follow-up questions.',
        );
        $this->seedUser(
            username: 'layla',
            firstName: 'Layla',
            lastName: 'Design',
            email: 'layla@example.com',
            bio: 'Product designer collecting thoughtful questions about mobile UX.',
        );
        $this->seedUser(
            username: 'omar',
            firstName: 'Omar',
            lastName: 'Maker',
            email: 'omar@example.com',
            bio: 'Indie maker building small tools in public.',
        );
        $nina = $this->seedUser(
            username: 'nina',
            firstName: 'Nina',
            lastName: 'Ops',
            email: 'nina@example.com',
            bio: 'Community manager testing moderation, reports, and safety workflows.',
        );
        $this->seedUser(
            username: 'yusuf',
            firstName: 'Yusuf',
            lastName: 'Mobile',
            email: 'yusuf@example.com',
            bio: 'Mobile developer checking notifications and threaded replies.',
        );
        $this->seedUser(
            username: 'mariam',
            firstName: 'Mariam',
            lastName: 'Research',
            email: 'mariam@example.com',
            bio: 'Researcher asking detailed product and learning questions.',
        );
        $this->seedUser(
            username: 'zain',
            firstName: 'Zain',
            lastName: 'Quiet',
            email: 'zain@example.com',
            bio: 'Low-activity account used to test empty-ish profile states.',
        );
        $inactive = $this->seedUser(
            username: 'inactive_user',
            firstName: 'Inactive',
            lastName: 'Example',
            email: 'inactive@example.com',
            bio: 'Inactive account fixture.',
            status: 'inactive',
            verified: false,
        );
        $this->seedUser(
            username: 'blocked_user',
            firstName: 'Blocked',
            lastName: 'Example',
            email: 'blocked@example.com',
            bio: 'Account used to demonstrate the block list.',
        );

        $this->seedDeviceToken($testUser, 'demo-testuser-ios-token', 'ios', 'iphone-demo');
        $this->seedDeviceToken($testUser, 'demo-testuser-web-token', 'web', 'browser-demo', disabledAt: now()->subDay());
        $this->seedDeviceToken($sara, 'demo-sara-android-token', 'android', 'pixel-demo');
        $this->seedDeviceToken($nina, 'demo-nina-ios-token', 'ios', 'ipad-demo');

        $this->seedSocialAccount($sara, 'google', 'google_sara_demo', 'sara.google@example.com');
        $this->seedSocialAccount($testUser, 'apple', 'apple_testuser_demo', 'test.apple@example.com');
        $this->seedSocialAccount($nina, 'google', 'google_nina_demo', 'nina.google@example.com');

        $this->seedVerificationCode($testUser->email, VerificationType::EMAIL_VERIFICATION);
        $this->seedVerificationCode($sara->email, VerificationType::PASSWORD_RESET, used: true);
        $this->seedVerificationCode($inactive->email, VerificationType::EMAIL_VERIFICATION, expired: true);
    }

    private function seedUser(
        string $username,
        string $firstName,
        string $lastName,
        string $email,
        string $bio,
        string $status = 'active',
        bool $verified = true,
    ): User {
        $factory = $verified ? User::factory() : User::factory()->unverified();

        if ($status !== 'active') {
            $factory = $factory->inactive();
        }

        return User::query()->updateOrCreate([
            'username' => $username,
        ], $factory->raw([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'bio' => $bio,
            'email_verified_at' => $verified ? now() : null,
            'status' => $status,
        ]));
    }

    private function seedDeviceToken(
        User $user,
        string $token,
        string $platform,
        string $deviceId,
        ?DateTimeInterface $disabledAt = null,
    ): DeviceToken {
        $factory = $disabledAt ? DeviceToken::factory()->disabled() : DeviceToken::factory();
        $tokenHash = hash('sha256', $token);

        return DeviceToken::query()->updateOrCreate([
            'token_hash' => $tokenHash,
        ], $factory->raw([
            'user_id' => $user->id,
            'token' => $token,
            'token_hash' => $tokenHash,
            'platform' => $platform,
            'device_id' => $deviceId,
            'app_version' => '1.0.0',
            'last_used_at' => now(),
            'disabled_at' => $disabledAt,
        ]));
    }

    private function seedSocialAccount(User $user, string $provider, string $providerUserId, string $providerEmail): SocialAccount
    {
        return SocialAccount::query()->updateOrCreate([
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
        ], SocialAccount::factory()->raw([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'provider_email' => $providerEmail,
        ]));
    }

    private function seedVerificationCode(
        string $email,
        VerificationType $type,
        bool $used = false,
        bool $expired = false,
    ): VerificationCode {
        $factory = $expired ? VerificationCode::factory()->expired() : VerificationCode::factory();

        if ($used) {
            $factory = $factory->used();
        }

        return VerificationCode::query()->updateOrCreate([
            'email' => $email,
            'type' => $type,
        ], $factory->raw([
            'email' => $email,
            'code_hash' => Hash::make('123456'),
            'type' => $type,
            'expires_at' => $expired ? now()->subMinute() : now()->addMinutes(10),
            'attempts' => 0,
        ]));
    }
}
