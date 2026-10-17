<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaderboard_bots', function (Blueprint $table): void {
            $table->decimal('score', 5, 2)->unsigned()->change();
            $table->unsignedTinyInteger('generation_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('leaderboard_bots', function (Blueprint $table): void {
            $table->dropColumn('generation_version');
            $table->unsignedTinyInteger('score')->change();
        });
    }
};
