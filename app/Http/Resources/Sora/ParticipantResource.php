<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly bool $anonymous = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        if ($this->anonymous) {
            return [
                'type' => 'guest',
                'display_name' => 'Sora Guest',
                'avatar' => null,
                'profile_url' => null,
            ];
        }

        /** @var User $user */
        $user = $this->resource;

        return [
            'type' => 'user',
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => trim($user->first_name.' '.$user->last_name),
            'username' => $user->username,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'profile_url' => url('/api/v1/users/'.$user->username),
            'bio' => $this->when(isset($user->bio), $user->bio),
        ];
    }
}
