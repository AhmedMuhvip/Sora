<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginAction
{
    public function execute(
        string $provider,
        string $accessToken,
    ): User {
        $socialUser = Socialite::driver($provider)
            ->userFromToken($accessToken);

        return DB::transaction(function () use (
            $provider,
            $socialUser
        ) {
            return $this->findOrCreateUser(
                $provider,
                $socialUser,
            );
        });
    }

    private function findOrCreateUser(
        string $provider,
        SocialiteUser $socialUser,
    ): User {
        $socialAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        $user = User::where(
            'email',
            $socialUser->getEmail(),
        )->first();

        if (! $user) {
            $user = User::create([
                'first_name' => $this->firstName(
                    $socialUser->getName(),
                ),

                'last_name' => $this->lastName(
                    $socialUser->getName(),
                ),

                'username' => $this->generateUsername(
                    $socialUser,
                ),

                'email' => strtolower(
                    $socialUser->getEmail(),
                ),

                'avatar' => $socialUser->getAvatar(),

                'email_verified_at' => now(),
            ]);
        }

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_user_id' => $socialUser->getId(),
            'provider_email' => $socialUser->getEmail(),
        ]);

        return $user;
    }

    private function generateUsername(
        SocialiteUser $socialUser,
    ): string {
        $base = Str::slug(
            $socialUser->getNickname()
                ?: $socialUser->getName()
                ?: 'user',
            '_',
        );

        $base = Str::limit($base, 20, '');

        do {
            $username = $base . '_' . Str::lower(
                Str::random(5),
            );
        } while (
            User::where('username', $username)->exists()
        );

        return $username;
    }

    private function firstName(?string $name): string
    {
        return Str::of($name ?? 'User')
            ->explode(' ')
            ->first();
    }

    private function lastName(?string $name): string
    {
        $parts = Str::of($name ?? '')
            ->explode(' ');

        return $parts->count() > 1
            ? $parts->slice(1)->implode(' ')
            : '';
    }
}
