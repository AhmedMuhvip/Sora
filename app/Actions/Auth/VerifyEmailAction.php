<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\VerificationType;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\DB;

class VerifyEmailAction
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
    ) {}

    public function execute(
        User $user,
        string $code,
    ): void {
        $verification = $this->verificationCodeService->verify(
            email: $user->email,
            code: $code,
            type: VerificationType::EMAIL_VERIFICATION,
        );

        DB::transaction(function () use (
            $user,
            $verification
        ) {
            $user->markEmailAsVerified();

            $this->verificationCodeService
                ->markAsUsed($verification);
        });
    }
}
