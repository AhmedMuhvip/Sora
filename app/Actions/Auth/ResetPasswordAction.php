<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\VerificationType;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\DB;

class ResetPasswordAction
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
    ) {}

    public function execute(
        User $user,
        string $code,
        string $password,
    ): void {
        $verification = $this->verificationCodeService->verify(
            email: $user->email,
            code: $code,
            type: VerificationType::PASSWORD_RESET,
        );

        DB::transaction(function () use (
            $user,
            $password,
            $verification,
        ) {
            $user->update([
                'password' => $password,
            ]);

            $this->verificationCodeService
                ->markAsUsed($verification);

            /*
             * Security:
             * invalidate all existing sessions/devices.
             */
            $user->tokens()->delete();
        });
    }
}
