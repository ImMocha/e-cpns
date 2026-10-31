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
        Schema::table('leaderboard_settings', function (Blueprint $table) {
            Schema::table('leaderboard_settings', function (Blueprint $table): void {
                $table->unsignedInteger('national_bot_count')->nullable();
                $table->unsignedInteger('local_bot_count')->nullable();
            });
        });
        DB::table('leaderboard_settings')->update(['national_bot_count' => 5000, 'local_bot_count' => 100]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboard_settings', function (Blueprint $table) {
            Schema::table('leaderboard_settings', function (Blueprint $table): void {
                $table->dropColumn(['national_bot_count', 'local_bot_count']);
            });
        });
    }
};
