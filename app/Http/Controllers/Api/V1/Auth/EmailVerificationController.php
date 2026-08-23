<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendEmailVerificationCodeAction;
use App\Actions\Auth\VerifyEmailAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    /**
     *   Verify Email
     */
    public function verify(
        VerifyEmailRequest $request,
        VerifyEmailAction $action,
    ): JsonResponse {
        $user = User::query()
            ->where(
                'email',
                strtolower($request->email),
            )
            ->first();

        if (! $user) {
            return $this->errorResponse(
                message: 'Invalid verification request.',
                code: ApiCode::UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse(
                message: 'Email is already verified.',
                code: ApiCode::CONFLICT,
            );
        }

        $action->execute(
            user: $user,
            code: $request->code,
        );

        $token = $user
            ->createToken($request->device_name)
            ->plainTextToken;

        return $this->successResponse(
            data: [
                'user' => new UserResource(
                    $user->fresh(),
                ),
                'token' => $token,
            ],
            message: 'Email verified successfully.',
            code: ApiCode::OK,
        );
    }

    /**
     *   Resend Email Verification Code
     */
    public function resend(
        Request $request,
        SendEmailVerificationCodeAction $action,
    ): JsonResponse {
        $request->validate([
            'email' => [
                'required',
                'email',
            ],
        ]);

        $user = User::query()
            ->where(
                'email',
                strtolower($request->email),
            )
            ->first();

        /*
         * Don't reveal account existence.
         */
        if ($user && ! $user->hasVerifiedEmail()) {
            $action->execute($user);
        }

        return $this->successResponse(
            message: 'If the email requires verification, a new code has been sent.',
        );
    }
}
