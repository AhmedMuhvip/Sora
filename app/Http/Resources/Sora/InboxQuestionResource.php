<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InboxQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;
        /** @var User|null $viewer */
        $viewer = $request->user();
        $isSentByViewer = $viewer !== null && (int) $question->sender_id === (int) $viewer->id;
        $isReceivedByViewer = $viewer !== null && (int) $question->recipient_id === (int) $viewer->id;

        return [
            'id' => $question->id,
            'body' => $question->body,
            'is_anonymous' => $question->is_anonymous,
            'status' => $question->status->value,
            'direction' => $this->direction($isSentByViewer, $isReceivedByViewer),
            'counterpart' => $this->counterpart($question, $isSentByViewer),
            'sender' => new ParticipantResource($question->sender, $question->is_anonymous),
            'recipient' => new ParticipantResource($question->recipient),
            'engagement' => [
                'likes' => (int) ($question->engagement_likes_count ?? $question->engagementLikes()->count()),
                'comments' => (int) ($question->engagement_comments_count ?? $question->engagementComments()->visible()->count()),
                'reposts' => (int) ($question->engagement_reposts_count ?? $question->engagementReposts()->count()),
            ],
            'viewer' => [
                'can_like' => $viewer !== null && $question->isPubliclyEngageable(),
                'is_liked' => $viewer !== null && $this->isLiked($viewer, $question),
                'can_comment' => $viewer !== null && $question->isPubliclyEngageable(),
                'can_repost' => $viewer !== null && $question->isPubliclyEngageable(),
                'is_reposted' => $viewer !== null && $this->isReposted($viewer, $question),
            ],
            'created_at' => $question->created_at?->toISOString(),
            'answered_at' => $question->answered_at?->toISOString(),
            'ignored_at' => $question->ignored_at?->toISOString(),
            'archived_at' => $question->archived_at?->toISOString(),
            'thread_id' => $question->thread?->id,
        ];
    }

    private function direction(bool $isSentByViewer, bool $isReceivedByViewer): string
    {
        return match (true) {
            $isSentByViewer => 'outgoing',
            $isReceivedByViewer => 'incoming',
            default => 'public',
        };
    }

    private function counterpart(Question $question, bool $isSentByViewer): ParticipantResource
    {
        if ($isSentByViewer) {
            return new ParticipantResource($question->recipient);
        }

        return new ParticipantResource($question->sender, $question->is_anonymous);
    }

    private function isLiked(User $viewer, Question $question): bool
    {
        return (bool) ($question->is_liked_by_viewer ?? $question->engagementLikes()
            ->where('user_id', $viewer->id)
            ->exists());
    }

    private function isReposted(User $viewer, Question $question): bool
    {
        return (bool) ($question->is_reposted_by_viewer ?? $question->engagementReposts()
            ->where('user_id', $viewer->id)
            ->exists());
    }
}
