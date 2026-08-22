<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Follow;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;

class SoraNotificationService
{
    public function __construct(private readonly FirebaseCloudMessagingService $messaging) {}

    public function questionAsked(Question $question): void
    {
        $question->loadMissing(['sender', 'recipient']);

        $this->messaging->sendToUser(
            user: $question->recipient,
            title: 'New question',
            body: ($question->is_anonymous ? 'Sora Guest' : $this->displayName($question->sender)).' asked you a question.',
            data: [
                'type' => 'question_asked',
                'question_id' => $question->id,
            ],
        );
    }

    public function questionAnswered(Thread $thread): void
    {
        $thread->loadMissing(['question', 'asker', 'recipient']);

        $this->messaging->sendToUser(
            user: $thread->asker,
            title: 'Your question was answered',
            body: $this->displayName($thread->recipient).' answered your question.',
            data: [
                'type' => 'question_answered',
                'thread_id' => $thread->id,
                'question_id' => $thread->question_id,
            ],
        );
    }

    public function threadMessageCreated(ThreadMessage $message): void
    {
        $message->loadMissing(['user', 'thread.question', 'thread.asker', 'thread.recipient']);

        $thread = $message->thread;
        $recipient = (int) $message->user_id === (int) $thread->asker_id
            ? $thread->recipient
            : $thread->asker;
        $senderName = $thread->question->is_anonymous && (int) $message->user_id === (int) $thread->asker_id
            ? 'Sora Guest'
            : $this->displayName($message->user);

        $this->messaging->sendToUser(
            user: $recipient,
            title: 'New reply',
            body: $senderName.' replied to your conversation.',
            data: [
                'type' => 'thread_message',
                'thread_id' => $thread->id,
                'message_id' => $message->id,
            ],
        );
    }

    public function userFollowed(Follow $follow): void
    {
        $follow->loadMissing(['follower', 'following']);

        $this->messaging->sendToUser(
            user: $follow->following,
            title: 'New follower',
            body: $this->displayName($follow->follower).' followed you.',
            data: [
                'type' => 'user_followed',
                'follower_id' => $follow->follower_id,
            ],
        );
    }

    private function displayName(User $user): string
    {
        return trim($user->first_name.' '.$user->last_name) ?: $user->username;
    }
}
