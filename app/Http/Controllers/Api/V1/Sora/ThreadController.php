<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\ThreadCardResource;
use App\Http\Resources\Sora\ThreadMessageResource;
use App\Http\Resources\Sora\ThreadResource;
use App\Models\Thread;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Thread $thread): JsonResponse
    {
        $this->ensurePubliclyVisible($thread);

        $thread->load([
            'question.sender',
            'asker',
            'recipient',
            'messages' => function ($query) use ($request): void {
                $this->withMessageEngagementState($query, $request);
                $query->with('user');
            },
        ])
            ->loadCount(['messages', 'likes']);

        if ($request->user() !== null) {
            $thread->setAttribute(
                'is_liked_by_viewer',
                $thread->likes()->where('user_id', $request->user()->id)->exists(),
            );
            $thread->setAttribute(
                'is_bookmarked_by_viewer',
                $thread->bookmarks()->where('user_id', $request->user()->id)->exists(),
            );
        }

        return $this->successResponse([
            'thread' => new ThreadResource($thread),
        ]);
    }

    public function messages(Request $request, Thread $thread): JsonResponse
    {
        $this->ensurePubliclyVisible($thread);

        $thread->load(['question.sender', 'asker', 'recipient']);

        $messagesQuery = $thread->messages()->with('user');
        $this->withMessageEngagementState($messagesQuery, $request);

        $paginator = $messagesQuery->cursorPaginate((int) $request->integer('per_page', 30));

        return $this->paginatedResponse(
            $paginator,
            collect($paginator->items())
                ->map(fn ($message) => new ThreadMessageResource($message, $thread))
                ->values(),
        );
    }

    public function home(Request $request): JsonResponse
    {
        $query = Thread::query()
            ->published()
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

    private function ensurePubliclyVisible(Thread $thread): void
    {
        $thread->loadMissing('question');

        if (! $thread->isPubliclyVisible()) {
            throw new NotFoundHttpException;
        }
    }

    private function withMessageEngagementState($query, Request $request): void
    {
        $query->withCount([
            'engagementLikes',
            'engagementComments' => fn ($commentQuery) => $commentQuery->visible(),
            'engagementReposts',
        ]);

        if ($request->user() !== null) {
            $query->withExists([
                'engagementLikes as is_liked_by_viewer' => fn ($likeQuery) => $likeQuery
                    ->where('user_id', $request->user()->id),
                'engagementReposts as is_reposted_by_viewer' => fn ($repostQuery) => $repostQuery
                    ->where('user_id', $request->user()->id),
            ]);
        }
    }
}
