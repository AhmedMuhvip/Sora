<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\EngagementLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LikeEngageableAction
{
    public function execute(User $user, Model $engageable): EngagementLike
    {
        return EngagementLike::query()->firstOrCreate([
            'user_id' => $user->id,
            'engageable_type' => $engageable::class,
            'engageable_id' => $engageable->getKey(),
        ]);
    }
}
