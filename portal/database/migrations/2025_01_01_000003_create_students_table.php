<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->integer('submission_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('batch')->nullable();
            $table->string('department')->nullable();
            $table->string('campus')->nullable();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->text('citation')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('citation_path')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
