<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\DeleteEngagementCommentAction;
use App\Http\Controllers\Controller;
use App\Models\EngagementComment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementCommentController extends Controller
{
    use ApiResponse;

    public function destroy(
        Request $request,
        EngagementComment $comment,
        DeleteEngagementCommentAction $action,
    ): JsonResponse {
        $action->execute($request->user(), $comment);

        return $this->successResponse(message: 'Comment deleted.');
    }
}
