<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('province', 60)->nullable()->index();
        });
        Schema::table('packages', function (Blueprint $table): void {
            $table->string('leaderboard_access', 20)->default('none');
        });
        Schema::table('user_packages', function (Blueprint $table): void {
            $table->string('leaderboard_access', 20)->default('none');
        });
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->index(['kind', 'status', 'user_id'], 'exam_attempts_leaderboard_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropIndex('exam_attempts_leaderboard_index');
        });
        Schema::table('user_packages', function (Blueprint $table): void {
            $table->dropColumn('leaderboard_access');
        });
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn('leaderboard_access');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['province']);
            $table->dropColumn('province');
        });
    }
};
