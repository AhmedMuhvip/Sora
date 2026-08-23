<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\LikeThreadAction;
use App\Actions\Sora\UnlikeThreadAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\ThreadResource;
use App\Models\Thread;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ThreadLikeController extends Controller
{
    use ApiResponse;

    /**
     *   Like Thread
     */
    public function store(Request $request, Thread $thread, LikeThreadAction $action): JsonResponse
    {
        Gate::authorize('like', $thread);

        $action->execute($request->user(), $thread);
        $thread->setAttribute('is_liked_by_viewer', true);

        return $this->successResponse([
            'thread' => new ThreadResource($thread->load(['question.sender', 'asker', 'recipient'])->loadCount('likes')),
        ], 'Thread liked.');
    }

    /**
     *   Unlike Thread
     */
    public function destroy(Request $request, Thread $thread, UnlikeThreadAction $action): JsonResponse
    {
        Gate::authorize('like', $thread);

        $action->execute($request->user(), $thread);
        $thread->setAttribute('is_liked_by_viewer', false);

        return $this->successResponse([
            'thread' => new ThreadResource($thread->load(['question.sender', 'asker', 'recipient'])->loadCount('likes')),
        ], 'Thread unliked.');
    }
}
