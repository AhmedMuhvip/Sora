<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('questions', 'archived_at')) {
            Schema::table('questions', function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->after('ignored_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'archived_at')) {
            Schema::table('questions', function (Blueprint $table): void {
                $table->dropColumn('archived_at');
            });
        }
    }
};
