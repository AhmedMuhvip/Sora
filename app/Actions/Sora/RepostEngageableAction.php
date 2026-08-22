<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\EngagementRepost;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RepostEngageableAction
{
    public function execute(User $user, Model $engageable): EngagementRepost
    {
        return EngagementRepost::query()->firstOrCreate([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->getKey(),
        ]);
    }
}
