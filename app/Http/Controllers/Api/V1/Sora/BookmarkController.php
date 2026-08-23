<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\BookmarkThreadAction;
use App\Actions\Sora\UnbookmarkThreadAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\BookmarkResource;
use App\Http\Resources\Sora\ThreadResource;
use App\Models\Bookmark;
use App\Models\Thread;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    use ApiResponse;

    /**
     *   Get Bookmarks
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = Bookmark::query()
            ->where('user_id', $request->user()->id)
            ->where('bookmarkable_type', Thread::class)
            ->with(['bookmarkable.question.sender', 'bookmarkable.asker', 'bookmarkable.recipient', 'bookmarkable.firstMessage.user'])
            ->with(['bookmarkable' => fn ($query) => $query->withCount(['messages', 'likes'])])
            ->latest()
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            BookmarkResource::collection($paginator->items()),
        );
    }

    /**
     *   Bookmark Thread
     */
    public function store(Request $request, Thread $thread, BookmarkThreadAction $action): JsonResponse
    {
        Gate::authorize('bookmark', $thread);

        $action->execute($request->user(), $thread);
        $thread->setAttribute('is_bookmarked_by_viewer', true);

        return $this->successResponse([
            'thread' => new ThreadResource($thread->load(['question.sender', 'asker', 'recipient'])),
        ], 'Thread bookmarked.');
    }

    /**
     *   Unbookmark Thread
     */
    public function destroy(Request $request, Thread $thread, UnbookmarkThreadAction $action): JsonResponse
    {
        Gate::authorize('bookmark', $thread);

        $action->execute($request->user(), $thread);
        $thread->setAttribute('is_bookmarked_by_viewer', false);

        return $this->successResponse([
            'thread' => new ThreadResource($thread->load(['question.sender', 'asker', 'recipient'])),
        ], 'Thread unbookmarked.');
    }
}
