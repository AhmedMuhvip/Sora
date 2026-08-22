<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code_hash',
        'type',
        'expires_at',
        'used_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'type' => VerificationType::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
