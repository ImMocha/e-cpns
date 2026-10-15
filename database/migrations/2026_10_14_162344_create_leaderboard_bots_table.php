<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_bots', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key', 80)->unique();
            $table->string('name', 120);
            $table->string('province', 60)->index();
            $table->unsignedTinyInteger('score');
            $table->timestamps();
        });

        Schema::create('leaderboard_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('simulations_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_settings');
        Schema::dropIfExists('leaderboard_bots');
    }
};
