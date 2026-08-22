<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('engageable');
            $table->timestamps();

            $table->unique(['user_id', 'engageable_type', 'engageable_id'], 'engagement_likes_unique_target');
        });

        Schema::create('engagement_reposts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('engageable');
            $table->timestamps();

            $table->unique(['user_id', 'engageable_type', 'engageable_id'], 'engagement_reposts_unique_target');
        });

        Schema::create('engagement_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('engageable');
            $table->text('body');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_comments');
        Schema::dropIfExists('engagement_reposts');
        Schema::dropIfExists('engagement_likes');
    }
};
