<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\ReplyToThreadAction;
use App\Enums\ApiCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\CreateThreadMessageRequest;
use App\Http\Resources\Sora\ThreadMessageResource;
use App\Models\Thread;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ThreadMessageController extends Controller
{
    use ApiResponse;

    public function store(CreateThreadMessageRequest $request, Thread $thread, ReplyToThreadAction $action): JsonResponse
    {
        Gate::authorize('reply', $thread);

        $message = $action->execute(
            user: $request->user(),
            thread: $thread,
            body: $request->string('body')->toString(),
        );

        return $this->successResponse(
            data: [
                'message' => new ThreadMessageResource($message, $message->thread),
            ],
            message: 'Message sent successfully.',
            code: ApiCode::CREATED,
        );
    }
}
