<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Enums\Sora\QuestionStatus;
use App\Models\Block;
use App\Models\Question;
use App\Models\User;
use App\Services\SoraNotificationService;
use Illuminate\Validation\ValidationException;

class AskQuestionAction
{
    public function __construct(private readonly SoraNotificationService $notifications) {}

    public function execute(User $sender, User $recipient, string $body, bool $isAnonymous = false): Question
    {
        if ($sender->is($recipient)) {
            throw ValidationException::withMessages([
                'recipient' => ['CANNOT_ASK_SELF'],
            ]);
        }

        if ($this->hasBlockBetween($sender, $recipient)) {
            throw ValidationException::withMessages([
                'recipient' => ['USER_BLOCKED'],
            ]);
        }

        $question = Question::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => $body,
            'is_anonymous' => $isAnonymous,
            'status' => QuestionStatus::PENDING,
        ]);

        $this->notifications->questionAsked($question);

        return $question;
    }

    private function hasBlockBetween(User $sender, User $recipient): bool
    {
        return Block::query()
            ->where(fn ($query) => $query->where('blocker_id', $sender->id)->where('blocked_id', $recipient->id))
            ->orWhere(fn ($query) => $query->where('blocker_id', $recipient->id)->where('blocked_id', $sender->id))
            ->exists();
    }
}
