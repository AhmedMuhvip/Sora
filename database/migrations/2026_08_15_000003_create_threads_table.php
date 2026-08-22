<?php

declare(strict_types=1);

use App\Enums\Sora\ThreadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained('questions')->restrictOnDelete();
            $table->foreignId('asker_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default(ThreadStatus::OPEN->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['recipient_id', 'status', 'published_at']);
            $table->index(['asker_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
