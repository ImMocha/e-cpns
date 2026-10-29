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
        Schema::table('packages', function (Blueprint $table): void {
            $table->unsignedInteger('tryout_tokens')->default(0);
        });
        Schema::table('user_packages', function (Blueprint $table): void {
            $table->unsignedInteger('tryout_tokens')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table): void {
            $table->dropColumn('tryout_tokens');
        });
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn('tryout_tokens');
        });
    }
};
