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
use App\Http\Resources\Sora\InboxQuestionResource;
use App\Models\Question;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionEngagementController extends Controller
{
    use ApiResponse;

    public function comments(Request $request, Question $question): JsonResponse
    {
        $this->ensureEngageable($question);

        $paginator = $question->engagementComments()
            ->visible()
            ->with('user')
            ->latest()
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            EngagementCommentResource::collection($paginator->items()),
        );
    }

    public function comment(
        CreateEngagementCommentRequest $request,
        Question $question,
        CreateEngagementCommentAction $action,
    ): JsonResponse {
        $this->ensureEngageable($question);

        $comment = $action->execute(
            $request->user(),
            $question,
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

    public function like(Request $request, Question $question, LikeEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($question);

        $action->execute($request->user(), $question);

        return $this->questionResponse($request, $question, 'Question liked.');
    }

    public function unlike(Request $request, Question $question, UnlikeEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($question);

        $action->execute($request->user(), $question);

        return $this->questionResponse($request, $question, 'Question unliked.');
    }

    public function repost(Request $request, Question $question, RepostEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($question);

        $action->execute($request->user(), $question);

        return $this->questionResponse($request, $question, 'Question reposted.');
    }

    public function unrepost(Request $request, Question $question, UnrepostEngageableAction $action): JsonResponse
    {
        $this->ensureEngageable($question);

        $action->execute($request->user(), $question);

        return $this->questionResponse($request, $question, 'Question unreposted.');
    }

    private function questionResponse(Request $request, Question $question, string $message): JsonResponse
    {
        $question = $this->loadQuestionEngagementState($request, $question->refresh());

        return $this->successResponse([
            'question' => new InboxQuestionResource($question),
        ], $message);
    }

    private function loadQuestionEngagementState(Request $request, Question $question): Question
    {
        $question
            ->load(['sender', 'recipient', 'thread'])
            ->loadCount([
                'engagementLikes',
                'engagementComments' => fn ($commentQuery) => $commentQuery->visible(),
                'engagementReposts',
            ]);

        $question->setAttribute(
            'is_liked_by_viewer',
            $question->engagementLikes()->where('user_id', $request->user()->id)->exists(),
        );
        $question->setAttribute(
            'is_reposted_by_viewer',
            $question->engagementReposts()->where('user_id', $request->user()->id)->exists(),
        );

        return $question;
    }

    private function ensureEngageable(Question $question): void
    {
        if (! $question->isPubliclyEngageable()) {
            throw new NotFoundHttpException;
        }
    }
}
