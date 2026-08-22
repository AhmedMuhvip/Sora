<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use App\Services\SoraNotificationService;
use Illuminate\Validation\ValidationException;

class FollowUserAction
{
    public function __construct(private readonly SoraNotificationService $notifications) {}

    public function execute(User $follower, User $following): Follow
    {
        if ($follower->is($following)) {
            throw ValidationException::withMessages([
                'user' => ['CANNOT_FOLLOW_SELF'],
            ]);
        }

        if ($this->hasBlockBetween($follower, $following)) {
            throw ValidationException::withMessages([
                'user' => ['USER_BLOCKED'],
            ]);
        }

        $follow = Follow::query()->firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        if ($follow->wasRecentlyCreated) {
            $this->notifications->userFollowed($follow);
        }

        return $follow;
    }

    private function hasBlockBetween(User $a, User $b): bool
    {
        return Block::query()
            ->where(fn ($query) => $query->where('blocker_id', $a->id)->where('blocked_id', $b->id))
            ->orWhere(fn ($query) => $query->where('blocker_id', $b->id)->where('blocked_id', $a->id))
            ->exists();
    }
}
