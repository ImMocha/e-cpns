<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 12);
            $table->string('slug', 80)->unique();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('question_count')->default(10);
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->unsignedSmallInteger('points_per_correct')->default(5);
            $table->unsignedSmallInteger('penalty_wrong')->default(0);
            $table->unsignedInteger('passing_score')->nullable();
            $table->boolean('random_questions')->default(true);
            $table->boolean('random_options')->default(true);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['kind', 'status']);
        });
        Schema::create('exam_question', function (Blueprint $table) {
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->primary(['exam_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_question');
        Schema::dropIfExists('exams');
    }
};
