<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\SearchUsersRequest;
use App\Http\Resources\Sora\PublicUserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDiscoveryController extends Controller
{
    use ApiResponse;

    /**
     *   Search Users
     */
    public function search(SearchUsersRequest $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        $userQuery = User::query()
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('username', 'like', $query.'%')
                    ->orWhere('first_name', 'like', $query.'%')
                    ->orWhere('last_name', 'like', $query.'%');
            })
            ->withCount([
                'followers',
                'following',
                'threadsAsRecipient as published_threads_count' => fn ($threadQuery) => $threadQuery->published(),
            ])
            ->orderBy('username');

        if ($request->user() !== null) {
            $userQuery->withExists([
                'followers as is_followed_by_viewer' => fn ($followersQuery) => $followersQuery
                    ->where('follower_id', $request->user()->id),
            ]);
        }

        $paginator = $userQuery->paginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            PublicUserResource::collection($paginator->items()),
        );
    }

    /**
     *   Get Suggested Users
     */
    public function suggestions(Request $request): JsonResponse
    {
        $usernames = config('sora.featured_usernames', []);

        $query = User::query()
            ->whereIn('username', $usernames)
            ->withCount([
                'followers',
                'following',
                'threadsAsRecipient as published_threads_count' => fn ($threadQuery) => $threadQuery->published(),
            ]);

        if ($request->user() !== null) {
            $query
                ->whereKeyNot($request->user()->id)
                ->withExists([
                    'followers as is_followed_by_viewer' => fn ($followersQuery) => $followersQuery
                        ->where('follower_id', $request->user()->id),
                ]);
        }

        return $this->successResponse([
            'items' => PublicUserResource::collection($query->limit(10)->get()),
        ]);
    }
}
