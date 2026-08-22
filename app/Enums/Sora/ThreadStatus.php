<?php

declare(strict_types=1);

namespace App\Enums\Sora;

enum ThreadStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case HIDDEN = 'hidden';
}
