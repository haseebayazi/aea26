<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('rubric_item_id')->constrained('rubric_items')->onDelete('cascade');
            $table->decimal('score', 4, 1)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'rubric_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_scores');
    }
};
