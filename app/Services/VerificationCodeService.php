<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VerificationType;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerificationCodeService
{
    private const EXPIRATION_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function generate(
        string $email,
        VerificationType $type,
    ): string {
        $email = strtolower($email);

        // Invalidate previous unused codes.
        VerificationCode::query()
            ->where('email', $email)
            ->where('type', $type->value)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        VerificationCode::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'type' => $type,
            'expires_at' => now()->addMinutes(
                self::EXPIRATION_MINUTES,
            ),
        ]);

        return $code;
    }

    public function verify(
        string $email,
        string $code,
        VerificationType $type,
    ): VerificationCode {
        $verification = VerificationCode::query()
            ->where('email', strtolower($email))
            ->where('type', $type->value)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'code' => [
                    'No active verification code was found.',
                ],
            ]);
        }

        if ($verification->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => [
                    'The verification code has expired.',
                ],
            ]);
        }

        if ($verification->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => [
                    'Too many verification attempts.',
                ],
            ]);
        }

        if (! Hash::check(
            $code,
            $verification->code_hash,
        )) {
            $verification->increment('attempts');

            throw ValidationException::withMessages([
                'code' => [
                    'The verification code is invalid.',
                ],
            ]);
        }

        return $verification;
    }

    public function markAsUsed(
        VerificationCode $verification,
    ): void {
        $verification->update([
            'used_at' => now(),
        ]);
    }
}
