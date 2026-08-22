<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\UpdateProfileRequest;
use App\Http\Resources\Sora\PublicUserResource;
use App\Http\Resources\Sora\ThreadCardResource;
use App\Models\Thread;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => new PublicUserResource($this->withProfileStats($request->user(), $request->user())),
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return $this->successResponse([
            'user' => new PublicUserResource($this->withProfileStats($user, $request->user())),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return $this->successResponse([
            'user' => new PublicUserResource($this->withProfileStats($user, $request->user())),
        ]);
    }

    public function threads(Request $request, User $user): JsonResponse
    {
        $query = Thread::query()
            ->published()
            ->where('recipient_id', $user->id)
            ->with(['question.sender', 'asker', 'recipient', 'firstMessage.user'])
            ->withCount(['messages', 'likes'])
            ->latest('published_at');

        if ($request->user() !== null) {
            $query->withExists([
                'bookmarks as is_bookmarked_by_viewer' => fn ($bookmarkQuery) => $bookmarkQuery
                    ->where('user_id', $request->user()->id),
                'likes as is_liked_by_viewer' => fn ($likeQuery) => $likeQuery
                    ->where('user_id', $request->user()->id),
            ]);
        }

        $paginator = $query->cursorPaginate((int) $request->integer('per_page', 15));

        return $this->paginatedResponse(
            $paginator,
            ThreadCardResource::collection($paginator->items()),
        );
    }

    private function withProfileStats(User $user, ?User $viewer): User
    {
        $user->loadCount([
            'followers',
            'following',
            'threadsAsRecipient as published_threads_count' => fn ($query) => $query->published(),
        ]);

        if ($viewer !== null && ! $viewer->is($user)) {
            $user->setAttribute(
                'is_followed_by_viewer',
                $viewer->following()->whereKey($user->id)->exists(),
            );
        }

        return $user;
    }
}
