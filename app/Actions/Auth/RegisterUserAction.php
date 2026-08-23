<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => User::generateUniqueUsername(
                    $data['first_name'],
                    $data['last_name'],
                ),
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);
        });
    }
}
