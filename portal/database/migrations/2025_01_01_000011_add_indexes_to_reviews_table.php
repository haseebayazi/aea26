<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Add explicit index on reviewer_id for fast per-reviewer queries
            // (MySQL creates FK indexes automatically, but be explicit for SQLite compatibility)
            $table->index('reviewer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['reviewer_id']);
            $table->dropIndex(['status']);
        });
    }
};
