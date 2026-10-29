<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['materials', 'exams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('access_level', 20)->default('premium')->change();
            });
        }

        DB::transaction(function (): void {
            DB::table('materials')->where('access_level', '!=', 'premium')->update(['access_level' => 'premium']);
            DB::table('exams')->update(['access_level' => 'premium', 'free_claim_enabled' => false]);
        });
    }

    /** Restores column defaults; converted content keeps its Premium access. */
    public function down(): void
    {
        foreach (['materials', 'exams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('access_level', 20)->default('free')->change();
            });
        }
    }
};
