<?php

declare(strict_types=1);

use App\Enums\Sora\QuestionStatus;
use App\Enums\Sora\ThreadStatus;
use App\Models\Bookmark;
use App\Models\DeviceToken;
use App\Models\EngagementComment;
use App\Models\EngagementLike;
use App\Models\EngagementRepost;
use App\Models\Follow;
use App\Models\Question;
use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\ThreadMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function publishedThread(bool $anonymous = false): array
{
    $asker = User::factory()->create();
    $recipient = User::factory()->create();
    $question = Question::factory()->create([
        'sender_id' => $asker->id,
        'recipient_id' => $recipient->id,
        'is_anonymous' => $anonymous,
        'status' => QuestionStatus::ANSWERED,
        'answered_at' => now(),
    ]);
    $thread = Thread::query()->create([
        'question_id' => $question->id,
        'asker_id' => $asker->id,
        'recipient_id' => $recipient->id,
        'status' => ThreadStatus::OPEN,
        'published_at' => now(),
    ]);
    ThreadMessage::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $recipient->id,
        'body' => 'First answer',
        'sequence' => 1,
    ]);

    return [$asker, $recipient, $question, $thread];
}

it('lets an authenticated user ask anonymously while storing the real sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/users/{$recipient->username}/questions", [
            'body' => 'What made you start programming?',
            'is_anonymous' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.question.sender.type', 'guest')
        ->assertJsonPath('data.question.sender.display_name', 'Sora Guest')
        ->assertJsonMissing(['username' => $sender->username]);

    $this->assertDatabaseHas('questions', [
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'is_anonymous' => true,
    ]);
});

it('exposes a public asker for non-anonymous questions and prevents asking yourself', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/users/{$recipient->username}/questions", [
            'body' => 'Public question?',
        ])
        ->assertCreated()
        ->assertJsonPath('data.question.sender.type', 'user')
        ->assertJsonPath('data.question.sender.username', $sender->username);

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/users/{$sender->username}/questions", [
            'body' => 'Can I ask myself?',
        ])
        ->assertUnprocessable();
});

it('only returns private inbox questions to the recipient', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => 'Inbox only',
    ]);

    $this->actingAs($recipient, 'sanctum')
        ->getJson('/api/v1/questions/inbox')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $question->id);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/questions/inbox')
        ->assertOk()
        ->assertJsonMissing(['body' => 'Inbox only']);
});

it('allows only the recipient to answer and creates exactly one published thread', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $other = User::factory()->create();
    $question = Question::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/answer", [
            'body' => 'Nope',
        ])
        ->assertForbidden();

    $this->actingAs($recipient, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/answer", [
            'body' => 'First answer',
        ])
        ->assertCreated()
        ->assertJsonPath('data.thread.question.id', $question->id);

    $this->actingAs($recipient, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/answer", [
            'body' => 'Duplicate answer',
        ])
        ->assertForbidden();

    expect(Thread::query()->where('question_id', $question->id)->count())->toBe(1);
    expect(ThreadMessage::query()->where('body', 'First answer')->count())->toBe(1);
});

it('restricts thread replies to the two participants and blocks replies after close', function () {
    [$asker, $recipient, , $thread] = publishedThread();
    $other = User::factory()->create();

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Follow up from asker',
        ])
        ->assertCreated();

    $this->actingAs($recipient, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Follow up from recipient',
        ])
        ->assertCreated();

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Third party comment',
        ])
        ->assertForbidden();

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/close")
        ->assertForbidden();

    $this->actingAs($recipient, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.can_reply', false);

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Too late',
        ])
        ->assertForbidden();
});

it('hides anonymous asker identity through follow-up thread messages', function () {
    [$asker, , , $thread] = publishedThread(anonymous: true);

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Anonymous follow up',
        ])
        ->assertCreated()
        ->assertJsonPath('data.message.author.type', 'guest')
        ->assertJsonMissing(['username' => $asker->username]);

    $this->getJson("/api/v1/threads/{$thread->id}")
        ->assertOk()
        ->assertJsonPath('data.thread.asker.type', 'guest')
        ->assertJsonPath('data.thread.messages.1.author.type', 'guest')
        ->assertJsonMissing(['username' => $asker->username]);
});

it('supports idempotent follows, unfollows, and prevents self-follow', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$target->username}/follow")
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$target->username}/follow")
        ->assertOk();

    expect(Follow::query()->where('follower_id', $user->id)->where('following_id', $target->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$user->username}/follow")
        ->assertUnprocessable();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/users/{$target->username}/follow")
        ->assertOk();

    expect(Follow::query()->count())->toBe(0);
});

it('prevents blocked users from asking anonymously and removes follow relationships on block', function () {
    $ahmed = User::factory()->create();
    $mohammed = User::factory()->create();
    Follow::factory()->create([
        'follower_id' => $mohammed->id,
        'following_id' => $ahmed->id,
    ]);

    $this->actingAs($ahmed, 'sanctum')
        ->postJson("/api/v1/users/{$mohammed->username}/block")
        ->assertOk();

    $this->assertDatabaseHas('blocks', [
        'blocker_id' => $ahmed->id,
        'blocked_id' => $mohammed->id,
    ]);
    expect(Follow::query()->count())->toBe(0);

    $this->actingAs($mohammed, 'sanctum')
        ->postJson("/api/v1/users/{$ahmed->username}/questions", [
            'body' => 'Anonymous bypass?',
            'is_anonymous' => true,
        ])
        ->assertUnprocessable();
});

it('keeps bookmarks private and idempotent', function () {
    [$asker, , , $thread] = publishedThread();
    $other = User::factory()->create();

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/bookmark")
        ->assertOk()
        ->assertJsonPath('data.thread.viewer.is_bookmarked', true);

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/bookmark")
        ->assertOk();

    expect(Bookmark::query()->where('user_id', $asker->id)->count())->toBe(1);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/bookmarks')
        ->assertOk()
        ->assertJsonPath('data.items', []);
});

it('shows only published home threads and preserves anonymity', function () {
    [$asker, , , $thread] = publishedThread(anonymous: true);
    Question::factory()->create([
        'body' => 'Pending private question',
    ]);

    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $thread->id)
        ->assertJsonPath('data.items.0.asker.type', 'guest')
        ->assertJsonMissing(['username' => $asker->username])
        ->assertJsonMissing(['body' => 'Pending private question']);
});

it('lists sent answered ignored and archived questions for the authenticated user', function () {
    $me = User::factory()->create();
    $other = User::factory()->create([
        'first_name' => 'Sara',
        'last_name' => 'Example',
        'username' => 'sara_example',
    ]);
    $sent = Question::factory()->create([
        'sender_id' => $me->id,
        'recipient_id' => $other->id,
        'body' => 'Question I sent',
    ]);
    $answered = Question::factory()->answered()->create([
        'sender_id' => $other->id,
        'recipient_id' => $me->id,
        'body' => 'Question I answered',
    ]);
    Question::factory()->create([
        'sender_id' => $other->id,
        'recipient_id' => $me->id,
        'body' => 'Question I ignored',
        'is_anonymous' => true,
        'status' => QuestionStatus::IGNORED,
        'ignored_at' => now(),
    ]);
    $archiveCandidate = Question::factory()->create([
        'sender_id' => $other->id,
        'recipient_id' => $me->id,
        'body' => 'Question I archive',
    ]);

    $this->actingAs($me, 'sanctum')
        ->getJson('/api/v1/questions/sent')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $sent->id)
        ->assertJsonPath('data.items.0.direction', 'outgoing')
        ->assertJsonPath('data.items.0.counterpart.type', 'user')
        ->assertJsonPath('data.items.0.counterpart.first_name', 'Sara')
        ->assertJsonPath('data.items.0.counterpart.last_name', 'Example')
        ->assertJsonPath('data.items.0.counterpart.username', 'sara_example')
        ->assertJsonPath('data.items.0.counterpart.profile_url', url('/api/v1/users/sara_example'));

    $this->actingAs($me, 'sanctum')
        ->getJson('/api/v1/questions/answered')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', $answered->id)
        ->assertJsonPath('data.items.0.direction', 'incoming')
        ->assertJsonPath('data.items.0.counterpart.type', 'user')
        ->assertJsonPath('data.items.0.counterpart.profile_url', url('/api/v1/users/sara_example'));

    $this->actingAs($me, 'sanctum')
        ->getJson('/api/v1/questions/ignored')
        ->assertOk()
        ->assertJsonFragment(['body' => 'Question I ignored'])
        ->assertJsonPath('data.items.0.direction', 'incoming')
        ->assertJsonPath('data.items.0.counterpart.type', 'guest')
        ->assertJsonPath('data.items.0.counterpart.display_name', 'Sora Guest')
        ->assertJsonPath('data.items.0.counterpart.profile_url', null)
        ->assertJsonMissing(['username' => 'sara_example']);

    $this->actingAs($me, 'sanctum')
        ->postJson("/api/v1/questions/{$archiveCandidate->id}/archive")
        ->assertOk()
        ->assertJsonPath('data.question.status', 'archived');

    $this->actingAs($me, 'sanctum')
        ->getJson('/api/v1/questions/archived')
        ->assertOk()
        ->assertJsonFragment(['body' => 'Question I archive'])
        ->assertJsonPath('data.items.0.direction', 'incoming')
        ->assertJsonPath('data.items.0.counterpart.type', 'user')
        ->assertJsonPath('data.items.0.counterpart.profile_url', url('/api/v1/users/sara_example'));
});

it('likes and unlikes public threads idempotently', function () {
    [$asker, , , $thread] = publishedThread();

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/like")
        ->assertOk()
        ->assertJsonPath('data.thread.viewer.is_liked', true)
        ->assertJsonPath('data.thread.stats.likes', 1);

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/like")
        ->assertOk()
        ->assertJsonPath('data.thread.stats.likes', 1);

    expect(ThreadLike::query()->where('thread_id', $thread->id)->where('user_id', $asker->id)->count())->toBe(1);

    $this->actingAs($asker, 'sanctum')
        ->deleteJson("/api/v1/threads/{$thread->id}/like")
        ->assertOk()
        ->assertJsonPath('data.thread.viewer.is_liked', false)
        ->assertJsonPath('data.thread.stats.likes', 0);
});

it('allows normal users to like comment and repost published questions', function () {
    [, , $question] = publishedThread();
    $viewer = User::factory()->create([
        'first_name' => 'Normal',
        'last_name' => 'User',
        'username' => 'normal_user',
    ]);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/like")
        ->assertOk()
        ->assertJsonPath('data.question.viewer.is_liked', true)
        ->assertJsonPath('data.question.engagement.likes', 1);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/like")
        ->assertOk()
        ->assertJsonPath('data.question.engagement.likes', 1);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/repost")
        ->assertOk()
        ->assertJsonPath('data.question.viewer.is_reposted', true)
        ->assertJsonPath('data.question.engagement.reposts', 1);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/comments", [
            'body' => 'This is a normal user comment.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.comment.author.type', 'user')
        ->assertJsonPath('data.comment.author.profile_url', url('/api/v1/users/normal_user'));

    expect(EngagementLike::query()->where('engageable_type', Question::class)->where('engageable_id', $question->id)->count())->toBe(1);
    expect(EngagementRepost::query()->where('engageable_type', Question::class)->where('engageable_id', $question->id)->count())->toBe(1);
    expect(EngagementComment::query()->where('engageable_type', Question::class)->where('engageable_id', $question->id)->visible()->count())->toBe(1);

    $this->getJson("/api/v1/questions/{$question->id}/comments")
        ->assertOk()
        ->assertJsonPath('data.items.0.author.username', 'normal_user');
});

it('allows normal users to like comment and repost published answers', function () {
    [, , , $thread] = publishedThread(anonymous: true);
    $answer = ThreadMessage::query()->where('thread_id', $thread->id)->where('sequence', 1)->firstOrFail();
    $viewer = User::factory()->create([
        'username' => 'answer_fan',
    ]);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/answers/{$answer->id}/like")
        ->assertOk()
        ->assertJsonPath('data.answer.viewer.is_liked', true)
        ->assertJsonPath('data.answer.engagement.likes', 1)
        ->assertJsonPath('data.answer.author.type', 'user');

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/answers/{$answer->id}/repost")
        ->assertOk()
        ->assertJsonPath('data.answer.viewer.is_reposted', true)
        ->assertJsonPath('data.answer.engagement.reposts', 1);

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/answers/{$answer->id}/comments", [
            'body' => 'Useful answer.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.comment.author.username', 'answer_fan');

    $this->getJson("/api/v1/answers/{$answer->id}/comments")
        ->assertOk()
        ->assertJsonPath('data.items.0.body', 'Useful answer.');
});

it('does not allow engagement on private pending questions', function () {
    $viewer = User::factory()->create();
    $question = Question::factory()->create();

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/like")
        ->assertNotFound();

    $this->actingAs($viewer, 'sanctum')
        ->postJson("/api/v1/questions/{$question->id}/comments", [
            'body' => 'Should not work.',
        ])
        ->assertNotFound();
});

it('registers and removes device tokens for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-one',
            'platform' => 'ios',
            'device_id' => 'iphone-15',
            'app_version' => '1.0.0',
        ])
        ->assertCreated()
        ->assertJsonPath('data.device_token.platform', 'ios')
        ->assertJsonMissing(['token' => 'fcm-token-one']);

    $this->assertDatabaseHas('device_tokens', [
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'fcm-token-one'),
        'platform' => 'ios',
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-one',
        ])
        ->assertOk();

    $this->assertDatabaseMissing('device_tokens', [
        'token_hash' => hash('sha256', 'fcm-token-one'),
    ]);
});

it('sends anonymous question notifications without exposing the real sender', function () {
    config([
        'services.firebase.messaging_enabled' => true,
        'services.firebase.project_id' => 'sora-test',
        'services.firebase.client_email' => 'firebase@example.com',
        'services.firebase.private_key' => 'fake-key',
    ]);
    Cache::put('firebase_messaging_access_token', 'fake-access-token', now()->addMinutes(10));
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response(['name' => 'messages/1']),
    ]);

    $sender = User::factory()->create([
        'first_name' => 'Ahmed',
        'last_name' => 'Secret',
        'username' => 'ahmedsecret',
    ]);
    $recipient = User::factory()->create();
    DeviceToken::factory()->create([
        'user_id' => $recipient->id,
        'token' => 'recipient-fcm-token',
        'token_hash' => hash('sha256', 'recipient-fcm-token'),
    ]);

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/users/{$recipient->username}/questions", [
            'body' => 'Anonymous push?',
            'is_anonymous' => true,
        ])
        ->assertCreated();

    Http::assertSent(function ($request) use ($sender): bool {
        $body = $request->data()['message']['notification']['body'] ?? '';

        return str_contains($body, 'Sora Guest')
            && ! str_contains($body, $sender->username)
            && ! str_contains($body, $sender->first_name)
            && ! str_contains($body, $sender->last_name);
    });
});

it('sends anonymous follow-up notifications without exposing the real asker', function () {
    config([
        'services.firebase.messaging_enabled' => true,
        'services.firebase.project_id' => 'sora-test',
        'services.firebase.client_email' => 'firebase@example.com',
        'services.firebase.private_key' => 'fake-key',
    ]);
    Cache::put('firebase_messaging_access_token', 'fake-access-token', now()->addMinutes(10));
    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response(['name' => 'messages/1']),
    ]);

    [$asker, $recipient, , $thread] = publishedThread(anonymous: true);
    DeviceToken::factory()->create([
        'user_id' => $recipient->id,
        'token' => 'recipient-reply-fcm-token',
        'token_hash' => hash('sha256', 'recipient-reply-fcm-token'),
    ]);

    $this->actingAs($asker, 'sanctum')
        ->postJson("/api/v1/threads/{$thread->id}/messages", [
            'body' => 'Anonymous reply push?',
        ])
        ->assertCreated();

    Http::assertSent(function ($request) use ($asker): bool {
        $body = $request->data()['message']['notification']['body'] ?? '';

        return str_contains($body, 'Sora Guest')
            && ! str_contains($body, $asker->username)
            && ! str_contains($body, $asker->first_name)
            && ! str_contains($body, $asker->last_name);
    });
});
