<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationType: string
{
    case EMAIL_VERIFICATION = 'email_verification';
    case PASSWORD_RESET = 'password_reset';
}
