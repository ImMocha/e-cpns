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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 12);
            $table->string('bank_slug', 80);
            $table->string('title', 180);
            $table->string('status', 20)->default('in_progress');
            $table->json('question_snapshot');
            $table->json('answers');
            $table->json('flags');
            $table->unsignedSmallInteger('current_index')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('max_score')->nullable();
            $table->unsignedInteger('passing_score')->nullable();
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('blank_count')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('auto_submitted')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'kind', 'status', 'submitted_at']);
            $table->index(['user_id', 'bank_slug', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
