<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Follow;
use App\Models\User;

class UnfollowUserAction
{
    public function execute(User $follower, User $following): void
    {
        Follow::query()
            ->where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete();
    }
}
