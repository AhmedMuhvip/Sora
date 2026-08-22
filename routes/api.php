<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Sora\AnswerEngagementController;
use App\Http\Controllers\Api\V1\Sora\BlockController;
use App\Http\Controllers\Api\V1\Sora\BookmarkController;
use App\Http\Controllers\Api\V1\Sora\DeviceTokenController;
use App\Http\Controllers\Api\V1\Sora\EngagementCommentController;
use App\Http\Controllers\Api\V1\Sora\FollowController;
use App\Http\Controllers\Api\V1\Sora\ProfileController;
use App\Http\Controllers\Api\V1\Sora\QuestionAnswerController;
use App\Http\Controllers\Api\V1\Sora\QuestionController;
use App\Http\Controllers\Api\V1\Sora\QuestionEngagementController;
use App\Http\Controllers\Api\V1\Sora\QuestionInboxController;
use App\Http\Controllers\Api\V1\Sora\ReportController;
use App\Http\Controllers\Api\V1\Sora\ThreadCloseController;
use App\Http\Controllers\Api\V1\Sora\ThreadController;
use App\Http\Controllers\Api\V1\Sora\ThreadLikeController;
use App\Http\Controllers\Api\V1\Sora\ThreadMessageController;
use App\Http\Controllers\Api\V1\Sora\UserDiscoveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Public Auth Routes
        |--------------------------------------------------------------------------
        */
        Route::post('/register', [
            AuthController::class,
            'register',
        ]);
        Route::middleware('throttle:auth')->group(function () {

            Route::post('/login', [
                AuthController::class,
                'login',
            ]);

            Route::post('/social', [
                SocialAuthController::class,
                'login',
            ]);

            Route::post('/email/verify', [
                EmailVerificationController::class,
                'verify',
            ]);

            Route::post('/email/resend', [
                EmailVerificationController::class,
                'resend',
            ]);

            Route::post('/forgot-password', [
                PasswordResetController::class,
                'forgot',
            ]);

            Route::post('/reset-password', [
                PasswordResetController::class,
                'reset',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Authenticated Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/me', [
                AuthController::class,
                'me',
            ]);

            Route::post('/change-password', [
                PasswordController::class,
                'update',
            ]);

            Route::post('/logout', [
                AuthController::class,
                'logout',
            ]);

            Route::post('/logout-all', [
                AuthController::class,
                'logoutAll',
            ]);
        });
    });

    Route::middleware(['optional.auth', 'throttle:sora-read'])->group(function () {
        Route::get('/home', [
            ThreadController::class,
            'home',
        ]);

        Route::get('/users/search', [
            UserDiscoveryController::class,
            'search',
        ])->middleware('throttle:sora-search');

        Route::get('/users/suggestions', [
            UserDiscoveryController::class,
            'suggestions',
        ]);

        Route::get('/users/{user:username}', [
            ProfileController::class,
            'show',
        ]);

        Route::get('/users/{user:username}/threads', [
            ProfileController::class,
            'threads',
        ]);

        Route::get('/users/{user:username}/followers', [
            FollowController::class,
            'followers',
        ]);

        Route::get('/users/{user:username}/following', [
            FollowController::class,
            'following',
        ]);

        Route::get('/threads/{thread}', [
            ThreadController::class,
            'show',
        ]);

        Route::get('/threads/{thread}/messages', [
            ThreadController::class,
            'messages',
        ]);

        Route::get('/questions/{question}/comments', [
            QuestionEngagementController::class,
            'comments',
        ]);

        Route::get('/answers/{threadMessage}/comments', [
            AnswerEngagementController::class,
            'comments',
        ]);
    });

    Route::middleware(['auth:sanctum', 'throttle:sora-write'])->group(function () {
        Route::get('/profile', [
            ProfileController::class,
            'me',
        ]);

        Route::post('/device-tokens', [
            DeviceTokenController::class,
            'store',
        ]);

        Route::delete('/device-tokens', [
            DeviceTokenController::class,
            'destroy',
        ]);

        Route::patch('/profile', [
            ProfileController::class,
            'update',
        ]);

        Route::post('/users/{user:username}/follow', [
            FollowController::class,
            'store',
        ]);

        Route::delete('/users/{user:username}/follow', [
            FollowController::class,
            'destroy',
        ]);

        Route::post('/users/{user:username}/questions', [
            QuestionController::class,
            'store',
        ])->middleware('throttle:sora-ask');

        Route::get('/questions/inbox', [
            QuestionInboxController::class,
            'index',
        ]);

        Route::get('/questions/sent', [
            QuestionInboxController::class,
            'sent',
        ]);

        Route::get('/questions/answered', [
            QuestionInboxController::class,
            'answered',
        ]);

        Route::get('/questions/ignored', [
            QuestionInboxController::class,
            'ignored',
        ]);

        Route::get('/questions/archived', [
            QuestionInboxController::class,
            'archived',
        ]);

        Route::post('/questions/{question}/answer', [
            QuestionAnswerController::class,
            'store',
        ]);

        Route::post('/questions/{question}/ignore', [
            QuestionInboxController::class,
            'ignore',
        ]);

        Route::post('/questions/{question}/archive', [
            QuestionInboxController::class,
            'archive',
        ]);

        Route::delete('/questions/{question}', [
            QuestionInboxController::class,
            'destroy',
        ]);

        Route::post('/questions/{question}/like', [
            QuestionEngagementController::class,
            'like',
        ]);

        Route::delete('/questions/{question}/like', [
            QuestionEngagementController::class,
            'unlike',
        ]);

        Route::post('/questions/{question}/repost', [
            QuestionEngagementController::class,
            'repost',
        ]);

        Route::delete('/questions/{question}/repost', [
            QuestionEngagementController::class,
            'unrepost',
        ]);

        Route::post('/questions/{question}/comments', [
            QuestionEngagementController::class,
            'comment',
        ]);

        Route::post('/threads/{thread}/messages', [
            ThreadMessageController::class,
            'store',
        ]);

        Route::post('/threads/{thread}/close', [
            ThreadCloseController::class,
            'store',
        ]);

        Route::post('/threads/{thread}/bookmark', [
            BookmarkController::class,
            'store',
        ]);

        Route::delete('/threads/{thread}/bookmark', [
            BookmarkController::class,
            'destroy',
        ]);

        Route::post('/threads/{thread}/like', [
            ThreadLikeController::class,
            'store',
        ]);

        Route::delete('/threads/{thread}/like', [
            ThreadLikeController::class,
            'destroy',
        ]);

        Route::post('/answers/{threadMessage}/like', [
            AnswerEngagementController::class,
            'like',
        ]);

        Route::delete('/answers/{threadMessage}/like', [
            AnswerEngagementController::class,
            'unlike',
        ]);

        Route::post('/answers/{threadMessage}/repost', [
            AnswerEngagementController::class,
            'repost',
        ]);

        Route::delete('/answers/{threadMessage}/repost', [
            AnswerEngagementController::class,
            'unrepost',
        ]);

        Route::post('/answers/{threadMessage}/comments', [
            AnswerEngagementController::class,
            'comment',
        ]);

        Route::delete('/comments/{comment}', [
            EngagementCommentController::class,
            'destroy',
        ]);

        Route::get('/bookmarks', [
            BookmarkController::class,
            'index',
        ]);

        Route::get('/blocks', [
            BlockController::class,
            'index',
        ]);

        Route::post('/users/{user:username}/block', [
            BlockController::class,
            'store',
        ]);

        Route::delete('/users/{user:username}/block', [
            BlockController::class,
            'destroy',
        ]);

        Route::post('/users/{user:username}/reports', [
            ReportController::class,
            'user',
        ]);

        Route::post('/questions/{question}/reports', [
            ReportController::class,
            'question',
        ]);

        Route::post('/threads/{thread}/reports', [
            ReportController::class,
            'thread',
        ]);
    });
});
