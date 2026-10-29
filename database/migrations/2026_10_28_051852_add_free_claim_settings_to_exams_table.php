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
        Schema::table('exams', function (Blueprint $table): void {
            $table->boolean('free_claim_enabled')->default(false);
            $table->text('free_claim_terms')->nullable();
            $table->string('free_claim_instagram_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn(['free_claim_enabled', 'free_claim_terms', 'free_claim_instagram_url']);
        });
    }
};
