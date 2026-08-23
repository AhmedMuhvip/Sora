<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateProfileAction
{
    public function __construct(private readonly AvatarService $avatarService) {}

    public function execute(User $user, array $data): User
    {
        $avatarPath = null;

        if (isset($data['avatar'])) {
            $avatarPath = $this->avatarService->store($data['avatar']);
        }

        try {
            return DB::transaction(function () use ($user, $data, $avatarPath): User {
                $update = Arr::only($data, ['first_name', 'last_name', 'username', 'bio', 'phone']);

                if (isset($update['username'])) {
                    $update['username'] = Str::lower($update['username']);
                }

                if ($avatarPath !== null) {
                    $oldAvatar = $user->avatar;
                    $update['avatar'] = $avatarPath;
                }

                $user->forceFill($update)->save();

                if (isset($oldAvatar)) {
                    $this->avatarService->delete($oldAvatar);
                }

                return $user->refresh();
            });
        } catch (\Throwable $exception) {
            if ($avatarPath !== null) {
                $this->avatarService->delete($avatarPath);
            }

            throw $exception;
        }
    }
}
