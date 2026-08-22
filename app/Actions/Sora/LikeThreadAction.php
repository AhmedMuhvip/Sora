<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\User;

class LikeThreadAction
{
    public function execute(User $user, Thread $thread): ThreadLike
    {
        return ThreadLike::query()->firstOrCreate([
            'user_id' => $user->id,
            'thread_id' => $thread->id,
        ]);
    }
}
