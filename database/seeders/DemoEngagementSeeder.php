<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Sora\ReportReason;
use App\Enums\Sora\ReportStatus;
use App\Models\Bookmark;
use App\Models\EngagementComment;
use App\Models\EngagementLike;
use App\Models\EngagementRepost;
use App\Models\Question;
use App\Models\Report;
use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Seeder;

class DemoEngagementSeeder extends Seeder
{
    public function run(): void
    {
        $testUser = $this->user('testuser');
        $ahmed = $this->user('ahmed');
        $layla = $this->user('layla');
        $omar = $this->user('omar');
        $nina = $this->user('nina');
        $yusuf = $this->user('yusuf');
        $mariam = $this->user('mariam');
        $blocked = $this->user('blocked_user');

        $programmingThread = $this->thread('What made you start programming?');
        $consistencyThread = $this->thread('How do you stay consistent while learning?');
        $designThread = $this->thread('What makes a profile screen feel trustworthy?');
        $closedThread = $this->thread('What is one feature every Sora user should try first?');
        $apiThread = $this->thread('What should I test first in the API?');
        $discoveryThread = $this->thread('How should new users find people to ask?');
        $notificationThread = $this->thread('What makes push notifications feel useful instead of noisy?');
        $hiddenThread = $this->thread('This hidden thread should not appear in public feeds.');

        foreach ([$consistencyThread, $designThread, $closedThread, $apiThread, $discoveryThread] as $thread) {
            $this->seedBookmark($testUser, $thread);
        }

        $this->seedBookmark($nina, $programmingThread);
        $this->seedBookmark($yusuf, $apiThread);
        $this->seedBookmark($mariam, $notificationThread);

        foreach ([
            [$testUser, $consistencyThread],
            [$testUser, $designThread],
            [$testUser, $apiThread],
            [$testUser, $notificationThread],
            [$ahmed, $designThread],
            [$layla, $programmingThread],
            [$omar, $programmingThread],
            [$nina, $programmingThread],
            [$nina, $notificationThread],
            [$yusuf, $apiThread],
            [$mariam, $discoveryThread],
            [$mariam, $closedThread],
        ] as [$user, $thread]) {
            $this->seedThreadLike($user, $thread);
        }

        $programmingAnswer = $programmingThread->messages()->where('sequence', 1)->firstOrFail();
        $consistencyAnswer = $consistencyThread->messages()->where('sequence', 1)->firstOrFail();
        $apiAnswer = $apiThread->messages()->where('sequence', 1)->firstOrFail();
        $notificationAnswer = $notificationThread->messages()->where('sequence', 1)->firstOrFail();

        $this->seedEngagement($testUser, $programmingThread->question, 'This is exactly the origin story I wanted to hear.');
        $this->seedEngagement($layla, $programmingThread->question, 'The small-tools angle is a good reminder.');
        $this->seedEngagement($omar, $programmingAnswer, 'Useful answer. Shipping tiny tools is underrated.');
        $this->seedEngagement($ahmed, $consistencyAnswer, 'The one-sitting framing makes this practical.');
        $this->seedEngagement($layla, $designThread->question, 'This question is relevant to product onboarding too.');
        $this->seedEngagement($nina, $apiThread->question, 'Good coverage checklist for onboarding QA.');
        $this->seedEngagement($testUser, $discoveryThread->question, 'This makes the suggestions page easier to explain.');
        $this->seedEngagement($mariam, $apiAnswer, 'The normal-flow-first advice is useful.');
        $this->seedEngagement($testUser, $notificationAnswer, 'Direct paths back to the thread are key.');
        $this->seedEngagement($yusuf, $notificationThread->question, 'Helpful framing for mobile notification settings.');

        $this->seedComment(
            user: $testUser,
            engageable: $notificationAnswer,
            body: 'Deleted answer comment fixture for testing visible comment counts.',
            deletedAt: now()->subMinutes(5),
        );
        $this->seedComment(
            user: $nina,
            engageable: $apiThread->question,
            body: 'Deleted question comment fixture for moderation screens.',
            deletedAt: now()->subMinutes(7),
        );

        $this->seedReport($testUser, $blocked, ReportReason::HARASSMENT, 'Demo user report for moderation screens.');
        $this->seedReport($ahmed, $consistencyThread, ReportReason::SPAM, 'Demo thread report.');
        $this->seedReport($layla, $programmingThread->question, ReportReason::OTHER, 'Demo question report.');
        $this->seedReport($nina, $hiddenThread, ReportReason::HATE, 'Hidden thread moderation fixture.', ReportStatus::REVIEWING);
        $this->seedReport($mariam, $blocked, ReportReason::IMPERSONATION, 'Resolved demo user report.', ReportStatus::RESOLVED);
        $this->seedReport($yusuf, $notificationThread->question, ReportReason::PRIVACY, 'Rejected demo question report.', ReportStatus::REJECTED);
    }

    private function user(string $username): User
    {
        return User::query()->where('username', $username)->firstOrFail();
    }

    private function thread(string $questionBody): Thread
    {
        return Question::query()
            ->where('body', $questionBody)
            ->firstOrFail()
            ->thread()
            ->firstOrFail();
    }

    private function seedBookmark(User $user, Thread $thread): Bookmark
    {
        return Bookmark::query()->firstOrCreate(Bookmark::factory()->raw([
            'user_id' => $user->id,
            'bookmarkable_type' => Thread::class,
            'bookmarkable_id' => $thread->id,
        ]));
    }

    private function seedThreadLike(User $user, Thread $thread): ThreadLike
    {
        return ThreadLike::query()->firstOrCreate(ThreadLike::factory()->raw([
            'user_id' => $user->id,
            'thread_id' => $thread->id,
        ]));
    }

    private function seedEngagement(User $user, EloquentModel $engageable, string $comment): void
    {
        EngagementLike::query()->firstOrCreate(EngagementLike::factory()->raw([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
        ]));

        EngagementRepost::query()->firstOrCreate(EngagementRepost::factory()->raw([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
        ]));

        EngagementComment::query()->updateOrCreate([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
            'body' => $comment,
        ], EngagementComment::factory()->raw([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
            'body' => $comment,
            'deleted_at' => null,
        ]));
    }

    private function seedComment(
        User $user,
        EloquentModel $engageable,
        string $body,
        ?DateTimeInterface $deletedAt = null,
    ): EngagementComment {
        $factory = $deletedAt ? EngagementComment::factory()->deleted() : EngagementComment::factory();

        return EngagementComment::query()->updateOrCreate([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
            'body' => $body,
        ], $factory->raw([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->id,
            'body' => $body,
            'deleted_at' => $deletedAt,
        ]));
    }

    private function seedReport(
        User $reporter,
        EloquentModel $reportable,
        ReportReason $reason,
        string $details,
        ReportStatus $status = ReportStatus::PENDING,
    ): Report {
        $factory = match ($status) {
            ReportStatus::REVIEWING => Report::factory()->reviewing(),
            ReportStatus::RESOLVED => Report::factory()->resolved(),
            ReportStatus::REJECTED => Report::factory()->rejected(),
            ReportStatus::PENDING => Report::factory(),
        };

        return Report::query()->updateOrCreate([
            'reporter_id' => $reporter->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->id,
        ], $factory->raw([
            'reporter_id' => $reporter->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->id,
            'reason' => $reason,
            'details' => $details,
            'status' => $status,
        ]));
    }
}
