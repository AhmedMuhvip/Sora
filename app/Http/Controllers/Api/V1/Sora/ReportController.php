<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sora;

use App\Actions\Sora\CreateReportAction;
use App\Enums\ApiCode;
use App\Enums\Sora\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sora\ReportRequest;
use App\Models\Question;
use App\Models\Thread;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     *   Report User
     */
    public function user(ReportRequest $request, User $user, CreateReportAction $action): JsonResponse
    {
        return $this->store($request, $user, $action);
    }

    /**
     *   Report Question
     */
    public function question(ReportRequest $request, Question $question, CreateReportAction $action): JsonResponse
    {
        Gate::authorize('report', $question);

        return $this->store($request, $question, $action);
    }

    /**
     *   Report Thread
     */
    public function thread(ReportRequest $request, Thread $thread, CreateReportAction $action): JsonResponse
    {
        Gate::authorize('report', $thread);

        return $this->store($request, $thread, $action);
    }

    private function store(ReportRequest $request, Model $reportable, CreateReportAction $action): JsonResponse
    {
        $report = $action->execute(
            reporter: $request->user(),
            reportable: $reportable,
            reason: ReportReason::from($request->string('reason')->toString()),
            details: $request->validated('details'),
        );

        return $this->successResponse(
            data: [
                'report' => [
                    'id' => $report->id,
                    'reason' => $report->reason->value,
                    'status' => $report->status->value,
                ],
            ],
            message: 'Report submitted.',
            code: ApiCode::CREATED,
        );
    }
}
