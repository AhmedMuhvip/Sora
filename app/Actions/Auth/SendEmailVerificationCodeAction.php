<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\VerificationType;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Support\Facades\Mail;

class SendEmailVerificationCodeAction
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
    ) {}

    public function execute(User $user): void
    {
        $code = $this->verificationCodeService->generate(
            email: $user->email,
            type: VerificationType::EMAIL_VERIFICATION,
        );

        Mail::to($user->email)->send(
            new EmailVerificationCodeMail($code),
        );
    }
}
