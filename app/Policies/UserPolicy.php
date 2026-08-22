<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $profile): bool
    {
        return $user->is($profile);
    }
}
