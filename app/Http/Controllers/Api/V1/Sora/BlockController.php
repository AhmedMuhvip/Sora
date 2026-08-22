<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\BlockUserAction;
use App\Actions\Sora\UnblockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sora\PublicUserResource;
use App\Models\Block;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $paginator = Block::query()
            ->where('blocker_id', $request->user()->id)
            ->with(['blocked' => fn ($query) => $query->withCount([
                'followers',
                'following',
                'threadsAsRecipient as published_threads_count' => fn ($threadQuery) => $threadQuery->published(),
            ])])
            ->latest()
            ->cursorPaginate((int) $request->integer('per_page', 20));

        return $this->paginatedResponse(
            $paginator,
            collect($paginator->items())
                ->map(fn (Block $block) => new PublicUserResource($block->blocked))
                ->values(),
        );
    }

    public function store(Request $request, User $user, BlockUserAction $action): JsonResponse
    {
        $action->execute($request->user(), $user);

        return $this->successResponse([
            'user' => new PublicUserResource($user),
        ], 'User blocked.');
    }

    public function destroy(Request $request, User $user, UnblockUserAction $action): JsonResponse
    {
        $action->execute($request->user(), $user);

        return $this->successResponse([
            'user' => new PublicUserResource($user),
        ], 'User unblocked.');
    }
}
