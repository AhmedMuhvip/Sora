<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sora\QuestionStatus;
use App\Enums\Sora\ThreadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Thread extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'asker_id',
        'recipient_id',
        'status',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ThreadStatus::class,
            'published_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function asker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ThreadMessage::class)->orderBy('sequence');
    }

    public function firstMessage(): HasOne
    {
        return $this->hasOne(ThreadMessage::class)->oldestOfMany('sequence');
    }

    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ThreadLike::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->whereIn('status', [ThreadStatus::OPEN, ThreadStatus::CLOSED])
            ->whereHas('question', fn (Builder $questionQuery) => $questionQuery
                ->where('status', QuestionStatus::ANSWERED));
    }

    public function isParticipant(User $user): bool
    {
        return $user->is($this->asker) || $user->is($this->recipient);
    }

    public function canReceiveReplies(): bool
    {
        return $this->status === ThreadStatus::OPEN && $this->published_at !== null;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->published_at !== null
            && in_array($this->status, [ThreadStatus::OPEN, ThreadStatus::CLOSED], true)
            && $this->question->status === QuestionStatus::ANSWERED;
    }
}
