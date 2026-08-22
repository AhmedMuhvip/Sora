<?php

declare(strict_types=1);

namespace App\Enums\Sora;

enum ReportStatus: string
{
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
}
