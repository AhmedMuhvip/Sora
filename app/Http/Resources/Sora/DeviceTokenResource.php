<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DeviceToken $deviceToken */
        $deviceToken = $this->resource;

        return [
            'id' => $deviceToken->id,
            'platform' => $deviceToken->platform,
            'device_id' => $deviceToken->device_id,
            'app_version' => $deviceToken->app_version,
            'last_used_at' => $deviceToken->last_used_at?->toISOString(),
            'disabled_at' => $deviceToken->disabled_at?->toISOString(),
        ];
    }
}
