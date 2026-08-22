<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Enums\Sora\ReportReason;
use App\Enums\Sora\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreateReportAction
{
    public function execute(User $reporter, Model $reportable, ReportReason $reason, ?string $details = null): Report
    {
        return Report::query()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
            'details' => $details,
            'status' => ReportStatus::PENDING,
        ]);
    }
}
