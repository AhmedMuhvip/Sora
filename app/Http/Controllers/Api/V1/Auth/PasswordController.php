<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ChangePasswordAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    use ApiResponse;

    /**
     *   Change Password
     */
    public function update(
        ChangePasswordRequest $request,
        ChangePasswordAction $action,
    ): JsonResponse {
        $action->execute(
            user: $request->user(),
            currentPassword: $request->current_password,
            newPassword: $request->password,
        );

        return $this->successResponse(
            message: 'Password changed successfully.',
            code: ApiCode::OK,
        );
    }
}
