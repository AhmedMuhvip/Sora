<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Enums\Sora\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\InboxQuestionResource;
use App\Models\Question;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionInboxController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->receivedByStatus($request, QuestionStatus::PENDING);
    }

    public function sent(Request $request): JsonResponse
    {
        $query = Question::query()
            ->where('sender_id', $request->user()->id)
            ->where('status', '!=', QuestionStatus::DELETED)
            ->with(['sender', 'recipient', 'thread'])
            ->latest();

        $this->withEngagementState($query, $request);

        $paginator = $query->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            InboxQuestionResource::collection($paginator->items()),
        );
    }

    public function answered(Request $request): JsonResponse
    {
        return $this->receivedByStatus($request, QuestionStatus::ANSWERED);
    }

    public function ignored(Request $request): JsonResponse
    {
        return $this->receivedByStatus($request, QuestionStatus::IGNORED);
    }

    public function archived(Request $request): JsonResponse
    {
        return $this->receivedByStatus($request, QuestionStatus::ARCHIVED);
    }

    public function ignore(Request $request, Question $question): JsonResponse
    {
        Gate::authorize('ignore', $question);

        $question->forceFill([
            'status' => QuestionStatus::IGNORED,
            'ignored_at' => now(),
        ])->save();

        return $this->successResponse([
            'question' => new InboxQuestionResource($question->refresh()->load(['sender', 'recipient'])),
        ], 'Question ignored.');
    }

    public function archive(Request $request, Question $question): JsonResponse
    {
        Gate::authorize('archive', $question);

        $question->forceFill([
            'status' => QuestionStatus::ARCHIVED,
            'archived_at' => now(),
        ])->save();

        return $this->successResponse([
            'question' => new InboxQuestionResource($question->refresh()->load(['sender', 'recipient'])),
        ], 'Question archived.');
    }

    public function destroy(Request $request, Question $question): JsonResponse
    {
        Gate::authorize('delete', $question);

        $question->forceFill([
            'status' => QuestionStatus::DELETED,
            'deleted_at' => now(),
        ])->save();

        return $this->successResponse(message: 'Question deleted.');
    }

    private function receivedByStatus(Request $request, QuestionStatus $status): JsonResponse
    {
        $query = Question::query()
            ->where('recipient_id', $request->user()->id)
            ->where('status', $status)
            ->with(['sender', 'recipient', 'thread'])
            ->latest();

        $this->withEngagementState($query, $request);

        $paginator = $query->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            InboxQuestionResource::collection($paginator->items()),
        );
    }

    private function withEngagementState(Builder $query, Request $request): void
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
