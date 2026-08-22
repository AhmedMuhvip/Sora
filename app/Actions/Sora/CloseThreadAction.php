<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Enums\Sora\ThreadStatus;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CloseThreadAction
{
    public function execute(User $recipient, Thread $thread): Thread
    {
        return DB::transaction(function () use ($recipient, $thread): Thread {
            /** @var Thread $lockedThread */
            $lockedThread = Thread::query()
                ->with(['question', 'asker', 'recipient'])
                ->whereKey($thread->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedThread->recipient_id !== (int) $recipient->id) {
                throw new AuthorizationException('ONLY_RECIPIENT_CAN_CLOSE');
            }

            if ($lockedThread->status !== ThreadStatus::CLOSED) {
                $lockedThread->forceFill([
                    'status' => ThreadStatus::CLOSED,
                    'closed_at' => now(),
                ])->save();
            }

            return $lockedThread->refresh()->load(['question.sender', 'asker', 'recipient']);
        });
    }
}
