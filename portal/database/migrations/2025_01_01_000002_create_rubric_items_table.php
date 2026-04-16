<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_items', function (Blueprint $table) {
            $table->id();
            $table->enum('rubric_type', ['caac', 'uaac'])->default('caac');
            $table->string('dimension');
            $table->string('dimension_label');
            $table->integer('dimension_weight');
            $table->string('sub_indicator_key');
            $table->string('sub_indicator_label');
            $table->decimal('max_score', 4, 1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_type', 'sub_indicator_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_items');
    }
};
