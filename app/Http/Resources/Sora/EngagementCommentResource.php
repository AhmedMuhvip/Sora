<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\EngagementComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EngagementCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var EngagementComment $comment */
        $comment = $this->resource;
        /** @var User|null $viewer */
        $viewer = $request->user();

        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => new ParticipantResource($comment->user),
            'viewer' => [
                'can_delete' => $viewer !== null && (int) $comment->user_id === (int) $viewer->id,
            ],
            'created_at' => $comment->created_at?->toISOString(),
        ];
    }
}
