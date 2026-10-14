<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('scoring_type', 20)->default('binary');
            $table->json('option_scores')->nullable();
            $table->boolean('shuffle_options')->default(true);
            $table->string('source_key', 100)->nullable()->unique();
            $table->unsignedTinyInteger('correct_option')->nullable()->change();
        });
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->unsignedInteger('weighted_answered_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('questions')->whereNull('correct_option')->exists()) {
            throw new RuntimeException('Ubah soal berbobot menjadi soal berkunci sebelum membatalkan migrasi penilaian.');
        }
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['scoring_type', 'option_scores', 'shuffle_options', 'source_key']);
            $table->unsignedTinyInteger('correct_option')->nullable(false)->change();
        });
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn('weighted_answered_count');
        });
    }
};
