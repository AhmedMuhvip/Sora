<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'platform',
        'device_id',
        'app_version',
        'last_used_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'immutable_datetime',
            'disabled_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
