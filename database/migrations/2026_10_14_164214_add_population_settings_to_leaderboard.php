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
        Schema::table('leaderboard_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('minimum_per_province')->default(15);
            $table->unsignedTinyInteger('maximum_per_province')->default(25);
        });
        Schema::table('leaderboard_bots', function (Blueprint $table): void {
            $table->boolean('is_visible')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_bots', function (Blueprint $table): void {
            $table->dropColumn('is_visible');
        });
        Schema::table('leaderboard_settings', function (Blueprint $table): void {
            $table->dropColumn(['minimum_per_province', 'maximum_per_province']);
        });
    }
};
