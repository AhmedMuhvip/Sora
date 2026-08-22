<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SocialLoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class SocialAuthController extends Controller
{
    public function login(
        SocialLoginRequest $request,
        SocialLoginAction $action,
    ): JsonResponse {
        $user = $action->execute(
            $request->provider,
            $request->access_token,
        );

        $token = $user
            ->createToken($request->device_name)
            ->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }
}
