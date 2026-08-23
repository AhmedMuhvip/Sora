<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\SendEmailVerificationCodeAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;
    /**
     *   Register Account
     */
    public function register(
        RegisterRequest $request,
        RegisterUserAction $registerAction,
        SendEmailVerificationCodeAction $verificationAction,
    ): JsonResponse {
        $user = $registerAction->execute(
            $request->validated(),
        );

        $verificationAction->execute($user);

        return $this->successResponse(
            data: [
                'email' => $user->email,
                'requires_verification' => true,
            ],
            message: 'Account created successfully. Please verify your email.',
            code: ApiCode::CREATED,
        );
    }

    /**
     *   Login Account
     */
    public function login(
        LoginRequest $request,
        LoginUserAction $action,
    ): JsonResponse {
        $user = $action->execute(
            $request->email,
            $request->password,
        );

        /*
         * Don't allow unverified users into the app.
         */
        if (! $user->hasVerifiedEmail()) {
            return $this->errorResponse(
                message: 'Please verify your email before logging in.',
                code: ApiCode::FORBIDDEN,
            );
        }

        $token = $user
            ->createToken($request->device_name)
            ->plainTextToken;

        return $this->successResponse(
            data: [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            message: 'Logged in successfully.',
            code: ApiCode::OK,
        );
    }

    /**
     *   Get Authenticated User
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: [
                'user' => new UserResource(
                    $request->user(),
                ),
            ],
        );
    }

    /**
     *   Logout Current Device
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return $this->successResponse(
            message: 'Logged out successfully.',
        );
    }

    /**
     *   Logout All Devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()
            ->tokens()
            ->delete();

        return $this->successResponse(
            message: 'Logged out from all devices.',
        );
    }
}
