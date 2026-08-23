<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\CreateEngagementCommentAction;
use App\Actions\Sora\LikeEngageableAction;
use App\Actions\Sora\RepostEngageableAction;
use App\Actions\Sora\UnlikeEngageableAction;
use App\Actions\Sora\UnrepostEngageableAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\CreateEngagementCommentRequest;
use App\Http\Resources\Sora\EngagementCommentResource;
use App\Http\Resources\Sora\ThreadMessageResource;
use App\Models\ThreadMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnswerEngagementController extends Controller
{
    use ApiResponse;

    /**
     *   Get Answer Comments
     */
    public function comments(Request $request, ThreadMessage $threadMessage): JsonResponse
    {
        $this->ensureEngageable($threadMessage);

        $paginator = $threadMessage->engagementComments()
            ->visible()
            ->with('user')
            ->latest()
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            EngagementCommentResource::collection($paginator->items()),
        );
    }

    /**
     *   Comment On Answer
     */
    public function comment(
        CreateEngagementCommentRequest $request,
        ThreadMessage $threadMessage,
        CreateEngagementCommentAction $action,
    ): JsonResponse {
        $this->ensureEngageable($threadMessage);

        $comment = $action->execute(
            $request->user(),
            $threadMessage,
            $request->string('body')->toString(),
        )->load('user');

        return $this->successResponse(
            data: [
                'comment' => new EngagementCommentResource($comment),
            ],
            message: 'Comment added.',
            code: ApiCode::CREATED,
        );
    }

    /**
     *   Like Answer
     */
    public function like(Request $request, ThreadMessage $threadMessage, LikeEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($threadMessage);

        $action->execute($request->user(), $threadMessage);

        return $this->messageResponse($request, $threadMessage, 'Answer liked.');
    }

    /**
     *   Unlike Answer
     */
    public function unlike(Request $request, ThreadMessage $threadMessage, UnlikeEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($threadMessage);

        $action->execute($request->user(), $threadMessage);

        return $this->messageResponse($request, $threadMessage, 'Answer unliked.');
    }

    /**
     *   Repost Answer
     */
    public function repost(Request $request, ThreadMessage $threadMessage, RepostEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($threadMessage);

        $action->execute($request->user(), $threadMessage);

        return $this->messageResponse($request, $threadMessage, 'Answer reposted.');
    }

    /**
     *   Unrepost Answer
     */
    public function unrepost(Request $request, ThreadMessage $threadMessage, UnrepostEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($threadMessage);

        $action->execute($request->user(), $threadMessage);

        return $this->messageResponse($request, $threadMessage, 'Answer unreposted.');
    }

    private function messageResponse(Request $request, ThreadMessage $threadMessage, string $message): JsonResponse
    {
        $threadMessage = $this->loadMessageEngagementState($request, $threadMessage->refresh());

        return $this->successResponse([
            'answer' => new ThreadMessageResource($threadMessage, $threadMessage->thread),
        ], $message);
    }

    private function loadMessageEngagementState(Request $request, ThreadMessage $threadMessage): ThreadMessage
    {
        $threadMessage
            ->load(['user', 'thread.question', 'thread.asker', 'thread.recipient'])
            ->loadCount([
                'engagementLikes',
                'engagementComments' => fn ($commentQuery) => $commentQuery->visible(),
                'engagementReposts',
            ]);

        $threadMessage->setAttribute(
            'is_liked_by_viewer',
            $threadMessage->engagementLikes()->where('user_id', $request->user()->id)->exists(),
        );
        $threadMessage->setAttribute(
            'is_reposted_by_viewer',
            $threadMessage->engagementReposts()->where('user_id', $request->user()->id)->exists(),
        );

        return $threadMessage;
    }

    private function ensureEngageable(ThreadMessage $threadMessage): void
    {
        if (! $threadMessage->isPubliclyEngageable()) {
            throw new NotFoundHttpException;
        }
    }
}
