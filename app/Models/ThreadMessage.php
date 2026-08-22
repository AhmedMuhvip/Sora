<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ThreadMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'user_id',
        'body',
        'sequence',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function engagementLikes(): MorphMany
    {
        return $this->morphMany(EngagementLike::class, 'engageable');
    }

    public function engagementComments(): MorphMany
    {
        return $this->morphMany(EngagementComment::class, 'engageable');
    }

    public function engagementReposts(): MorphMany
    {
        return $this->morphMany(EngagementRepost::class, 'engageable');
    }

    public function isPubliclyEngageable(): bool
    {
        $this->loadMissing('thread.question');

        return $this->thread->isPubliclyVisible();
    }
}
