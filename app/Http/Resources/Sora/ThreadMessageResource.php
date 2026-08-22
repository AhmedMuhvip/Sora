<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadMessageResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly ?Thread $thread = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var ThreadMessage $message */
        $message = $this->resource;
        $thread = $this->thread ?? $message->thread;
        /** @var User|null $viewer */
        $viewer = $request->user();
        $isAnonymousAsker = (bool) $thread->question->is_anonymous
            && (int) $message->user_id === (int) $thread->asker_id;

        return [
            'id' => $message->id,
            'body' => $message->body,
            'sequence' => $message->sequence,
            'author' => new ParticipantResource($message->user, $isAnonymousAsker),
            'engagement' => [
                'likes' => (int) ($message->engagement_likes_count ?? $message->engagementLikes()->count()),
                'comments' => (int) ($message->engagement_comments_count ?? $message->engagementComments()->visible()->count()),
                'reposts' => (int) ($message->engagement_reposts_count ?? $message->engagementReposts()->count()),
            ],
            'viewer' => [
                'can_like' => $viewer !== null && $message->isPubliclyEngageable(),
                'is_liked' => $viewer !== null && $this->isLiked($viewer, $message),
                'can_comment' => $viewer !== null && $message->isPubliclyEngageable(),
                'can_repost' => $viewer !== null && $message->isPubliclyEngageable(),
                'is_reposted' => $viewer !== null && $this->isReposted($viewer, $message),
            ],
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    private function isLiked(User $viewer, ThreadMessage $message): bool
    {
        return (bool) ($message->is_liked_by_viewer ?? $message->engagementLikes()
            ->where('user_id', $viewer->id)
            ->exists());
    }

    private function isReposted(User $viewer, ThreadMessage $message): bool
    {
        return (bool) ($message->is_reposted_by_viewer ?? $message->engagementReposts()
            ->where('user_id', $viewer->id)
            ->exists());
    }
}
