<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Enums\Sora\QuestionStatus;
use App\Enums\Sora\ThreadStatus;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use App\Services\SoraNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnswerQuestionAction
{
    public function __construct(private readonly SoraNotificationService $notifications) {}

    public function execute(User $recipient, Question $question, string $body): Thread
    {
        $thread = DB::transaction(function () use ($recipient, $question, $body): Thread {
            /** @var Question $lockedQuestion */
            $lockedQuestion = Question::query()
                ->whereKey($question->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedQuestion->recipient_id !== (int) $recipient->id) {
                throw new AuthorizationException('ONLY_RECIPIENT_CAN_ANSWER');
            }

            if ($lockedQuestion->status !== QuestionStatus::PENDING) {
                throw ValidationException::withMessages([
                    'question' => ['QUESTION_ALREADY_ANSWERED'],
                ]);
            }

            $thread = Thread::query()->create([
                'question_id' => $lockedQuestion->id,
                'asker_id' => $lockedQuestion->sender_id,
                'recipient_id' => $lockedQuestion->recipient_id,
                'status' => ThreadStatus::OPEN,
                'published_at' => now(),
            ]);

            ThreadMessage::query()->create([
                'thread_id' => $thread->id,
                'user_id' => $recipient->id,
                'body' => $body,
                'sequence' => 1,
            ]);

            $lockedQuestion->forceFill([
                'status' => QuestionStatus::ANSWERED,
                'answered_at' => now(),
            ])->save();

            return $thread->load(['question.sender', 'asker', 'recipient', 'messages.user']);
        });

        $this->notifications->questionAnswered($thread);

        return $thread;
    }
}
