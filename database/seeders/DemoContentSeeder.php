<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Sora\QuestionStatus;
use App\Enums\Sora\ThreadStatus;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $testUser = $this->user('testuser');
        $sora = $this->user('sora');
        $sara = $this->user('sara');
        $ahmed = $this->user('ahmed');
        $layla = $this->user('layla');
        $omar = $this->user('omar');
        $nina = $this->user('nina');
        $yusuf = $this->user('yusuf');
        $mariam = $this->user('mariam');
        $blocked = $this->user('blocked_user');

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $ahmed,
                recipient: $sara,
                body: 'What made you start programming?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$sara, 'I started by trying to build small tools for myself, then kept going.'],
                [$ahmed, 'What was the first language you used?'],
                [$sara, 'PHP, because it let me put useful things on the web quickly.'],
            ],
            publishedAt: now()->subHours(3),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $testUser,
                recipient: $sara,
                body: 'How do you stay consistent while learning?',
                isAnonymous: true,
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$sara, 'I keep the scope small enough that I can finish something most days.'],
                [$testUser, 'That helps. How small is small enough?'],
                [$sara, 'Small enough to ship in one sitting, even if it is only a rough version.'],
            ],
            publishedAt: now()->subHours(2),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $omar,
                recipient: $layla,
                body: 'What makes a profile screen feel trustworthy?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$layla, 'Clear identity, recent activity, and controls that behave predictably.'],
                [$omar, 'Would you show social counts above or below the bio?'],
            ],
            publishedAt: now()->subHour(),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $layla,
                recipient: $sora,
                body: 'What is one feature every Sora user should try first?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$sora, 'Start by asking someone a direct question, then continue the conversation in the thread.'],
            ],
            status: ThreadStatus::CLOSED,
            publishedAt: now()->subDay(),
            closedAt: now()->subHours(20),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $nina,
                recipient: $testUser,
                body: 'What should I test first in the API?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$testUser, 'Start with login, profile loading, then the home feed because they reveal most integration issues.'],
                [$nina, 'Would you test edge cases before the normal flow?'],
                [$testUser, 'I usually prove the normal flow first, then test validation and authorization failures.'],
            ],
            publishedAt: now()->subMinutes(45),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $mariam,
                recipient: $sora,
                body: 'How should new users find people to ask?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$sora, 'Start with suggestions, then search for people by username or area of interest.'],
                [$mariam, 'Should featured users change often?'],
                [$sora, 'They should stay stable enough to be recognizable, but fresh enough to show active people.'],
            ],
            publishedAt: now()->subMinutes(30),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $testUser,
                recipient: $yusuf,
                body: 'What makes push notifications feel useful instead of noisy?',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$yusuf, 'They need clear intent, good timing, and a direct path back to the relevant thread.'],
                [$testUser, 'Would you notify for every reply?'],
                [$yusuf, 'Only for replies in threads the user participates in or explicitly follows.'],
                [$testUser, 'This deleted message helps test hidden engagement rows.', now()->subMinutes(18)],
            ],
            publishedAt: now()->subMinutes(20),
        );

        $this->seedThread(
            question: $this->seedQuestion(
                sender: $blocked,
                recipient: $nina,
                body: 'This hidden thread should not appear in public feeds.',
                status: QuestionStatus::ANSWERED,
            ),
            messages: [
                [$nina, 'Hidden moderation fixture.'],
            ],
            status: ThreadStatus::HIDDEN,
            publishedAt: now()->subMinutes(10),
        );

        $this->seedQuestion(
            sender: $ahmed,
            recipient: $testUser,
            body: 'What are you learning this week?',
            isAnonymous: true,
        );
        $this->seedQuestion(
            sender: $testUser,
            recipient: $layla,
            body: 'How do you decide when a prototype is good enough to test?',
        );
        $this->seedQuestion(
            sender: $omar,
            recipient: $testUser,
            body: 'Which API endpoint should new clients call first?',
            status: QuestionStatus::IGNORED,
            ignoredAt: now()->subHours(4),
        );
        $this->seedQuestion(
            sender: $sara,
            recipient: $testUser,
            body: 'Can you archive questions you want to answer later?',
            status: QuestionStatus::ARCHIVED,
            archivedAt: now()->subHours(6),
        );
        $this->seedQuestion(
            sender: $mariam,
            recipient: $testUser,
            body: 'Can you recommend a simple way to organize API test cases?',
        );
        $this->seedQuestion(
            sender: $testUser,
            recipient: $nina,
            body: 'How should moderation reports be prioritized?',
        );
        $this->seedQuestion(
            sender: $nina,
            recipient: $testUser,
            body: 'This older ignored question should stay in the ignored tab.',
            status: QuestionStatus::IGNORED,
            ignoredAt: now()->subDays(2),
        );
        $this->seedQuestion(
            sender: $layla,
            recipient: $testUser,
            body: 'This archived design question should stay in the archived tab.',
            status: QuestionStatus::ARCHIVED,
            archivedAt: now()->subDay(),
        );
        $this->seedQuestion(
            sender: $testUser,
            recipient: $sora,
            body: 'This deleted question should stay out of normal sent lists.',
            status: QuestionStatus::DELETED,
            deletedAt: now()->subDays(2),
        );
    }

    private function user(string $username): User
    {
        return User::query()->where('username', $username)->firstOrFail();
    }

    private function seedQuestion(
        User $sender,
        User $recipient,
        string $body,
        bool $isAnonymous = false,
        QuestionStatus $status = QuestionStatus::PENDING,
        ?DateTimeInterface $answeredAt = null,
        ?DateTimeInterface $ignoredAt = null,
        ?DateTimeInterface $archivedAt = null,
        ?DateTimeInterface $deletedAt = null,
    ): Question {
        $answeredAt ??= $status === QuestionStatus::ANSWERED ? now() : null;
        $ignoredAt ??= $status === QuestionStatus::IGNORED ? now() : null;
        $archivedAt ??= $status === QuestionStatus::ARCHIVED ? now() : null;
        $deletedAt ??= $status === QuestionStatus::DELETED ? now() : null;

        return Question::query()->updateOrCreate([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => $body,
        ], Question::factory()->raw([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => $body,
            'is_anonymous' => $isAnonymous,
            'status' => $status,
            'answered_at' => $answeredAt,
            'ignored_at' => $ignoredAt,
            'archived_at' => $archivedAt,
            'deleted_at' => $deletedAt,
        ]));
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2?: DateTimeInterface}>  $messages
     */
    private function seedThread(
        Question $question,
        array $messages,
        ThreadStatus $status = ThreadStatus::OPEN,
        ?DateTimeInterface $publishedAt = null,
        ?DateTimeInterface $closedAt = null,
    ): Thread {
        $thread = Thread::query()->updateOrCreate([
            'question_id' => $question->id,
        ], Thread::factory()->raw([
            'question_id' => $question->id,
            'asker_id' => $question->sender_id,
            'recipient_id' => $question->recipient_id,
            'status' => $status,
            'published_at' => $publishedAt ?? now(),
            'closed_at' => $closedAt,
        ]));

        foreach ($messages as $index => $message) {
            [$user, $body] = $message;
            $deletedAt = $message[2] ?? null;
            $factory = $deletedAt ? ThreadMessage::factory()->deleted() : ThreadMessage::factory();

            ThreadMessage::query()->updateOrCreate([
                'thread_id' => $thread->id,
                'sequence' => $index + 1,
            ], $factory->raw([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'body' => $body,
                'sequence' => $index + 1,
                'deleted_at' => $deletedAt,
            ]));
        }

        return $thread;
    }
}
