<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;

class ThreadPolicy
{
    public function view(?User $user, Thread $thread): bool
    {
        $thread->loadMissing('question');

        return $thread->isPubliclyVisible();
    }

    public function reply(User $user, Thread $thread): bool
    {
        return $thread->isParticipant($user) && $thread->canReceiveReplies();
    }

    public function close(User $user, Thread $thread): bool
    {
        return (int) $thread->recipient_id === (int) $user->id
            && $thread->canReceiveReplies();
    }

    public function bookmark(User $user, Thread $thread): bool
    {
        return $this->view($user, $thread);
    }

    public function like(User $user, Thread $thread): bool
    {
        return $this->view($user, $thread);
    }

    public function report(User $user, Thread $thread): bool
    {
        return $this->view($user, $thread);
    }
}
