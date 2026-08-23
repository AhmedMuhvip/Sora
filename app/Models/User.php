<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'bio',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function generateUniqueUsername(?string $firstName, ?string $lastName): string
    {
        $base = Str::slug(
            trim(($firstName ?? '').' '.($lastName ?? '')),
            '_',
        ) ?: 'user';

        $base = Str::limit(Str::lower($base), 20, '');

        do {
            $username = $base.'_'.Str::lower(
                Str::random(8),
            );
        } while (
            self::query()->where('username', $username)->exists()
        );

        return $username;
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'following_id',
            'follower_id',
        )->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'follower_id',
            'following_id',
        )->withTimestamps();
    }

    public function sentQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'sender_id');
    }

    public function receivedQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'recipient_id');
    }

    public function threadsAsAsker(): HasMany
    {
        return $this->hasMany(Thread::class, 'asker_id');
    }

    public function threadsAsRecipient(): HasMany
    {
        return $this->hasMany(Thread::class, 'recipient_id');
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(ThreadMessage::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function threadLikes(): HasMany
    {
        return $this->hasMany(ThreadLike::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(Block::class, 'blocked_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function engagementLikes(): HasMany
    {
        return $this->hasMany(EngagementLike::class);
    }

    public function engagementComments(): HasMany
    {
        return $this->hasMany(EngagementComment::class);
    }

    public function engagementReposts(): HasMany
    {
        return $this->hasMany(EngagementRepost::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
