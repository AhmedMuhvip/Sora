<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Bookmark;
use App\Models\Thread;
use App\Models\User;

class UnbookmarkThreadAction
{
    public function execute(User $user, Thread $thread): void
    {
        Bookmark::query()
            ->where('user_id', $user->id)
            ->where('bookmarkable_type', Thread::class)
            ->where('bookmarkable_id', $thread->id)
            ->delete();
    }
}
