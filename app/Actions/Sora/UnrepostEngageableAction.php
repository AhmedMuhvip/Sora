<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\EngagementRepost;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnrepostEngageableAction
{
    public function execute(User $user, Model $engageable): void
    {
        EngagementRepost::query()
            ->where('user_id', $user->id)
            ->where('engageable_type', $engageable::class)
            ->where('engageable_id', $engageable->getKey())
            ->delete();
    }
}
