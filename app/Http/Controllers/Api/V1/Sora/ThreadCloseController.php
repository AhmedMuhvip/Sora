<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\CloseThreadAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\ThreadResource;
use App\Models\Thread;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ThreadCloseController extends Controller
{
    use ApiResponse;

    public function store(Request $request, Thread $thread, CloseThreadAction $action): JsonResponse
    {
        Gate::authorize('close', $thread);

        $thread = $action->execute($request->user(), $thread);

        return $this->successResponse([
            'thread' => new ThreadResource($thread),
            'status' => $thread->status->value,
            'can_reply' => false,
        ], 'Thread closed.');
    }
}
