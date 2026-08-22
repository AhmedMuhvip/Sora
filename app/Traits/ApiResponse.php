<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApiCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        ApiCode $code = ApiCode::OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code->value);
    }

    protected function errorResponse(
        string $message = 'Something went wrong.',
        ApiCode $code = ApiCode::BAD_REQUEST,
        mixed $errors = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code->value);
    }

    protected function paginatedResponse(
        AbstractPaginator|AbstractCursorPaginator $paginator,
        mixed $items,
        string $message = 'Success',
    ): JsonResponse {
        $pagination = [
            'per_page' => $paginator->perPage(),
            'has_more' => $paginator->hasMorePages(),
        ];

        if ($paginator instanceof AbstractCursorPaginator) {
            $pagination['next_cursor'] = $paginator->nextCursor()?->encode();
            $pagination['previous_cursor'] = $paginator->previousCursor()?->encode();
        } else {
            $pagination['current_page'] = $paginator->currentPage();
            $pagination['last_page'] = method_exists($paginator, 'lastPage')
                ? $paginator->lastPage()
                : null;
            $pagination['total'] = method_exists($paginator, 'total')
                ? $paginator->total()
                : null;
        }

        return $this->successResponse(
            data: [
                'items' => $items,
                'pagination' => $pagination,
            ],
            message: $message,
        );
    }
}
