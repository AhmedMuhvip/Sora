<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\User;

class UnlikeThreadAction
{
    public function execute(User $user, Thread $thread): void
    {
        ThreadLike::query()
            ->where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->delete();
    }
}
