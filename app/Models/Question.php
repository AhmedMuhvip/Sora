<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sora\QuestionStatus;
use App\Enums\Sora\ThreadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'is_anonymous',
        'status',
        'answered_at',
        'ignored_at',
        'archived_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'status' => QuestionStatus::class,
            'answered_at' => 'immutable_datetime',
            'ignored_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function thread(): HasOne
    {
        return $this->hasOne(Thread::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', QuestionStatus::PENDING);
    }

    public function isPubliclyEngageable(): bool
    {
        $this->loadMissing('thread');

        return $this->status === QuestionStatus::ANSWERED
            && $this->thread !== null
            && $this->thread->published_at !== null
            && in_array($this->thread->status, [ThreadStatus::OPEN, ThreadStatus::CLOSED], true);
    }
}
