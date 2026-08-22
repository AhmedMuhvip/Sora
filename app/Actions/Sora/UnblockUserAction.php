<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Block;
use App\Models\User;

class UnblockUserAction
{
    public function execute(User $blocker, User $blocked): void
    {
        Block::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }
}
