<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leaderboard_bots')->where('generation_version', '<', 3)->update([
            'score' => DB::raw('ROUND(CASE WHEN score <= 100 THEN score * 5.5 ELSE score END)'),
            'generation_version' => 3,
        ]);
    }

    public function down(): void
    {
        throw new LogicException('Skor bot lama tidak dapat dipulihkan secara tepat setelah pembulatan poin.');
    }
};
