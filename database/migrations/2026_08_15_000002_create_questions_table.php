<?php

declare(strict_types=1);

use App\Enums\Sora\QuestionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default(QuestionStatus::PENDING->value);
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ignored_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'status', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['status', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
