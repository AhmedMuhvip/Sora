<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SendPasswordResetCodeAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function forgot(
        ForgotPasswordRequest $request,
        SendPasswordResetCodeAction $action,
    ): JsonResponse {
        $user = User::query()
            ->where(
                'email',
                strtolower($request->email),
            )
            ->first();

        /*
         * Do not expose whether this email exists.
         */
        if ($user) {
            $action->execute($user);
        }

        return $this->successResponse(
            message: 'If an account exists with this email, a password reset code has been sent.',
            code: ApiCode::OK,
        );
    }

    public function reset(
        ResetPasswordRequest $request,
        ResetPasswordAction $action,
    ): JsonResponse {
        $user = User::query()
            ->where(
                'email',
                strtolower($request->email),
            )
            ->first();

        if (! $user) {
            return $this->errorResponse(
                message: 'Invalid password reset request.',
                code: ApiCode::UNPROCESSABLE_ENTITY,
            );
        }

        $action->execute(
            user: $user,
            code: $request->code,
            password: $request->password,
        );

        return $this->successResponse(
            message: 'Password reset successfully. Please log in with your new password.',
            code: ApiCode::OK,
        );
    }
}
