<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function __construct(
        private readonly AvatarService $avatarService,
    ) {
    }

    public function execute(array $data): User
    {
        $avatarPath = null;

        if (isset($data['avatar'])) {
            $avatarPath = $this->avatarService->store(
                $data['avatar'],
            );
        }

        try {
            return DB::transaction(function () use (
                $data,
                $avatarPath
            ) {
                return User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'username' => strtolower($data['username']),
                    'email' => strtolower($data['email']),
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                    'avatar' => $avatarPath,
                ]);
            });
        } catch (\Throwable $exception) {

            if ($avatarPath) {
                $this->avatarService->delete(
                    $avatarPath,
                );
            }

            throw $exception;
        }
    }
}
