<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,

            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,

            'avatar' => $this->avatar
                ? asset('storage/' . $this->avatar)
                : null,
            'bio' => $this->bio,

            'email_verified' => $this->email_verified_at !== null,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
