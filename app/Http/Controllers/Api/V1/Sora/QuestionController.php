<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\AskQuestionAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\AskQuestionRequest;
use App\Http\Resources\Sora\InboxQuestionResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    use ApiResponse;

    /**
     *   Ask Question
     */
    public function store(AskQuestionRequest $request, User $user, AskQuestionAction $action): JsonResponse
    {
        $question = $action->execute(
            sender: $request->user(),
            recipient: $user,
            body: $request->string('body')->toString(),
            isAnonymous: $request->boolean('is_anonymous'),
        )->load(['sender', 'recipient']);

        return $this->successResponse(
            data: [
                'question' => new InboxQuestionResource($question),
            ],
            message: 'Question sent successfully.',
            code: ApiCode::CREATED,
        );
    }
}
