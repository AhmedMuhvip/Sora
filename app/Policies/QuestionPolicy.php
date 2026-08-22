<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Sora\QuestionStatus;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id;
    }

    public function answer(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id
            && $question->status === QuestionStatus::PENDING;
    }

    public function ignore(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id
            && $question->status === QuestionStatus::PENDING;
    }

    public function delete(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id
            && in_array($question->status, [QuestionStatus::PENDING, QuestionStatus::IGNORED, QuestionStatus::ARCHIVED], true);
    }

    public function archive(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id
            && in_array($question->status, [QuestionStatus::PENDING, QuestionStatus::IGNORED], true);
    }

    public function report(User $user, Question $question): bool
    {
        return (int) $question->recipient_id === (int) $user->id
            || (int) $question->sender_id === (int) $user->id;
    }
}
