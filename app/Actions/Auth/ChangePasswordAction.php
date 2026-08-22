<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordAction
{
    public function execute(
        User $user,
        string $currentPassword,
        string $newPassword,
    ): void {
        if (! Hash::check(
            $currentPassword,
            $user->password,
        )) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'The current password is incorrect.',
                ],
            ]);
        }

        $user->update([
            'password' => $newPassword,
        ]);

        /*
         * Logout every other device while keeping
         * the current device authenticated.
         */
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();
        }
    }
}
