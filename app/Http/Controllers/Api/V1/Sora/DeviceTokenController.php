<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\StoreDeviceTokenRequest;
use App\Http\Resources\Sora\DeviceTokenResource;
use App\Models\DeviceToken;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    use ApiResponse;

    /**
     *   Save Device Token
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $token = $request->string('token')->toString();

        $deviceToken = DeviceToken::query()->updateOrCreate([
            'token_hash' => hash('sha256', $token),
        ], [
            'user_id' => $request->user()->id,
            'token' => $token,
            'platform' => $request->validated('platform'),
            'device_id' => $request->validated('device_id'),
            'app_version' => $request->validated('app_version'),
            'last_used_at' => now(),
            'disabled_at' => null,
        ]);

        return $this->successResponse(
            data: [
                'device_token' => new DeviceTokenResource($deviceToken),
            ],
            message: 'Device token saved.',
            code: ApiCode::CREATED,
        );
    }

    /**
     *   Remove Device Token
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token_hash', hash('sha256', $request->string('token')->toString()))
            ->delete();

        return $this->successResponse(message: 'Device token removed.');
    }
}
