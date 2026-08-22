<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\EngagementComment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteEngagementCommentAction
{
    public function execute(User $user, EngagementComment $comment): void
    {
        if ((int) $comment->user_id !== (int) $user->id) {
            throw new AuthorizationException('ONLY_COMMENT_AUTHOR_CAN_DELETE');
        }

        $comment->forceFill([
            'deleted_at' => now(),
        ])->save();
    }
}
