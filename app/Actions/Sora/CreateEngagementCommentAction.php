<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\EngagementComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateEngagementCommentAction
{
    public function execute(User $user, Model $engageable, string $body): EngagementComment
    {
        return EngagementComment::query()->create([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->getKey(),
            'body' => $body,
        ]);
    }
}
