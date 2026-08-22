<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EngagementComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'engageable_type',
        'engageable_id',
        'body',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function engageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
