<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Bookmark;
use App\Models\Thread;
use App\Models\ThreadLike;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Thread $thread */
        $thread = $this->resource;
        /** @var User|null $viewer */
        $viewer = $request->user();
        $isParticipant = $viewer ? $thread->isParticipant($viewer) : false;
        $isRecipient = $viewer ? $viewer->is($thread->recipient) : false;

        return [
            'id' => $thread->id,
            'status' => $thread->status->value,
            'is_anonymous' => (bool) $thread->question->is_anonymous,
            'asker' => new ParticipantResource($thread->asker, (bool) $thread->question->is_anonymous),
            'recipient' => new ParticipantResource($thread->recipient),
            'question' => [
                'id' => $thread->question->id,
                'body' => $thread->question->body,
                'created_at' => $thread->question->created_at?->toISOString(),
            ],
            'messages' => $this->whenLoaded('messages', fn () => $thread->messages
                ->map(fn ($message) => new ThreadMessageResource($message, $thread))
                ->values()),
            'viewer' => [
                'is_participant' => $isParticipant,
                'can_reply' => $isParticipant && $thread->canReceiveReplies(),
                'can_close' => $isRecipient && $thread->canReceiveReplies(),
                'can_bookmark' => $viewer !== null,
                'is_bookmarked' => $viewer !== null && $this->isBookmarked($viewer, $thread),
                'can_like' => $viewer !== null,
                'is_liked' => $viewer !== null && $this->isLiked($viewer, $thread),
            ],
            'stats' => [
                'likes' => (int) ($thread->likes_count ?? $thread->likes()->count()),
                'replies' => max(0, (int) ($thread->messages_count ?? $thread->messages()->count()) - 1),
            ],
            'published_at' => $thread->published_at?->toISOString(),
            'closed_at' => $thread->closed_at?->toISOString(),
        ];
    }

    private function isBookmarked(User $viewer, Thread $thread): bool
    {
        return (bool) ($thread->is_bookmarked_by_viewer ?? Bookmark::query()
            ->where('user_id', $viewer->id)
            ->where('bookmarkable_type', Thread::class)
            ->where('bookmarkable_id', $thread->id)
            ->exists());
    }

    private function isLiked(User $viewer, Thread $thread): bool
    {
        return (bool) ($thread->is_liked_by_viewer ?? ThreadLike::query()
            ->where('user_id', $viewer->id)
            ->where('thread_id', $thread->id)
            ->exists());
    }
}
