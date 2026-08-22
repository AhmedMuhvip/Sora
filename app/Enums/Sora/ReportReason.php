<?php

declare(strict_types=1);

namespace App\Enums\Sora;

enum ReportReason: string
{
    case SPAM = 'spam';
    case HARASSMENT = 'harassment';
    case HATE = 'hate';
    case IMPERSONATION = 'impersonation';
    case SEXUAL_CONTENT = 'sexual_content';
    case VIOLENCE = 'violence';
    case PRIVACY = 'privacy';
    case OTHER = 'other';
}
