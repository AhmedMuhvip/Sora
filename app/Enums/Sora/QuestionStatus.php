<?php

declare(strict_types=1);

namespace App\Enums\Sora;

enum QuestionStatus: string
{
    case PENDING = 'pending';
    case ANSWERED = 'answered';
    case IGNORED = 'ignored';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
}
