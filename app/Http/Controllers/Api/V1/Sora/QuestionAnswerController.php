<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\AnswerQuestionAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\AnswerQuestionRequest;
use App\Http\Resources\Sora\ThreadResource;
use App\Models\Question;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class QuestionAnswerController extends Controller
{
    use ApiResponse;

    public function store(AnswerQuestionRequest $request, Question $question, AnswerQuestionAction $action): JsonResponse
    {
        Gate::authorize('answer', $question);

        $thread = $action->execute(
            recipient: $request->user(),
            question: $question,
            body: $request->string('body')->toString(),
        );

        return $this->successResponse(
            data: [
                'thread' => new ThreadResource($thread),
            ],
            message: 'Question answered successfully.',
            code: ApiCode::CREATED,
        );
    }
}
