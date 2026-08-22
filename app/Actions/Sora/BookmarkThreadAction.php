<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Bookmark;
use App\Models\Thread;
use App\Models\User;

class BookmarkThreadAction
{
    public function execute(User $user, Thread $thread): Bookmark
    {
        return Bookmark::query()->firstOrCreate([
            'user_id' => $user->id,
            'bookmarkable_type' => Thread::class,
            'bookmarkable_id' => $thread->id,
        ]);
    }
}
