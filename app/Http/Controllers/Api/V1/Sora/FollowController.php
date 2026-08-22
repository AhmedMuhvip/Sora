<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\FollowUserAction;
use App\Actions\Sora\UnfollowUserAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\PublicUserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    use ApiResponse;

    public function store(Request $request, User $user, FollowUserAction $action): JsonResponse
    {
        $action->execute($request->user(), $user);

        return $this->successResponse([
            'user' => new PublicUserResource($this->withProfileStats($user, $request->user())),
        ], 'Followed successfully.');
    }

    public function destroy(Request $request, User $user, UnfollowUserAction $action): JsonResponse
    {
        $action->execute($request->user(), $user);

        return $this->successResponse([
            'user' => new PublicUserResource($this->withProfileStats($user, $request->user())),
        ], 'Unfollowed successfully.');
    }

    public function followers(Request $request, User $user): JsonResponse
    {
        $paginator = $this->userListQuery($user->followers(), $request)
            ->orderByPivot('created_at', 'desc')
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            PublicUserResource::collection($paginator->items()),
        );
    }

    public function following(Request $request, User $user): JsonResponse
    {
        $paginator = $this->userListQuery($user->following(), $request)
            ->orderByPivot('created_at', 'desc')
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            PublicUserResource::collection($paginator->items()),
        );
    }

    private function userListQuery($query, Request $request)
    {
        $query->withCount([
            'followers',
            'following',
            'threadsAsRecipient as published_threads_count' => fn ($threadQuery) => $threadQuery->published(),
        ]);

        if ($request->user() !== null) {
            $query->withExists([
                'followers as is_followed_by_viewer' => fn ($followersQuery) => $followersQuery
                    ->where('follower_id', $request->user()->id),
            ]);
        }

        return $query;
    }

    private function withProfileStats(User $user, User $viewer): User
    {
        $user->loadCount([
            'followers',
            'following',
            'threadsAsRecipient as published_threads_count' => fn ($query) => $query->published(),
        ]);
        $user->setAttribute('is_followed_by_viewer', $viewer->following()->whereKey($user->id)->exists());

        return $user;
    }
}
