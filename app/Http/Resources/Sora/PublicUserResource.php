<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Block;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        /** @var User|null $viewer */
        $viewer = $request->user();

        $isMe = $viewer?->is($user) ?? false;
        $isFollowing = $viewer && ! $isMe
            ? (bool) ($user->is_followed_by_viewer ?? $viewer->following()->whereKey($user->id)->exists())
            : false;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => trim($user->first_name.' '.$user->last_name),
            'username' => $user->username,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'bio' => $user->bio,
            'stats' => [
                'followers' => (int) ($user->followers_count ?? $user->followers()->count()),
                'following' => (int) ($user->following_count ?? $user->following()->count()),
                'threads' => (int) ($user->published_threads_count ?? $user->threadsAsRecipient()->published()->count()),
            ],
            'viewer' => [
                'is_me' => $isMe,
                'is_following' => $isFollowing,
                'can_ask' => $viewer !== null && ! $isMe && ! $this->hasBlockBetween($viewer, $user),
            ],
        ];
    }

    private function hasBlockBetween(User $viewer, User $user): bool
    {
        return Block::query()
            ->where(function ($query) use ($viewer, $user): void {
                $query
                    ->where('blocker_id', $viewer->id)
                    ->where('blocked_id', $user->id);
            })
            ->orWhere(function ($query) use ($viewer, $user): void {
                $query
                    ->where('blocker_id', $user->id)
                    ->where('blocked_id', $viewer->id);
            })
            ->exists();
    }
}
