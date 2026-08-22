<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use App\Services\SoraNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplyToThreadAction
{
    public function __construct(private readonly SoraNotificationService $notifications) {}

    public function execute(User $user, Thread $thread, string $body): ThreadMessage
    {
        $message = DB::transaction(function () use ($user, $thread, $body): ThreadMessage {
            /** @var Thread $lockedThread */
            $lockedThread = Thread::query()
                ->with(['question', 'asker', 'recipient'])
                ->whereKey($thread->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedThread->isParticipant($user)) {
                throw new AuthorizationException('NOT_THREAD_PARTICIPANT');
            }

            if (! $lockedThread->canReceiveReplies()) {
                throw ValidationException::withMessages([
                    'thread' => ['THREAD_CLOSED'],
                ]);
            }

            $sequence = ((int) ThreadMessage::query()
                ->where('thread_id', $lockedThread->id)
                ->max('sequence')) + 1;

            return ThreadMessage::query()->create([
                'thread_id' => $lockedThread->id,
                'user_id' => $user->id,
                'body' => $body,
                'sequence' => $sequence,
            ])->load(['user', 'thread.question', 'thread.asker', 'thread.recipient']);
        });

        $this->notifications->threadMessageCreated($message);

        return $message;
    }
}
