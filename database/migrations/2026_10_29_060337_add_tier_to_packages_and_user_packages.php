<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['packages', 'user_packages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('tier', 20)->nullable();
            });
        }

        foreach ([
            ['regular', 'Paket 1', 'Paket Reguler', 15000, 2, 'none'],
            ['bundling', 'Paket 2', 'Paket Bundling', 35000, 3, 'local'],
            ['premium', 'Paket 3', 'Paket Premium', null, null, 'national'],
        ] as [$tier, $oldName, $name, $price, $quota, $leaderboard]) {
            $ids = DB::table('packages')->whereIn('name', [$oldName, $name])->pluck('id');
            DB::table('packages')->whereIn('id', $ids)->update(array_filter([
                'tier' => $tier, 'name' => $name, 'price' => $price,
                'description' => match ($tier) {
                    'regular' => 'Satu paket soal Tryout SKD, 2 percobaan, kunci jawaban dan pembahasan.',
                    'bundling' => 'Tiga paket soal Tryout SKD, 3 percobaan total, pembahasan dan perangkingan daerah.',
                    'premium' => 'Tryout dan percobaan unlimited, materi lengkap, latihan SKD, analisa pembelajaran, statistik nilai, perangkingan nasional dan sertifikat.',
                },
                'tryout_quota' => $quota, 'leaderboard_access' => $leaderboard,
            ], fn (mixed $value, string $key): bool => $key !== 'price' || $value !== null, ARRAY_FILTER_USE_BOTH));
            DB::table('user_packages')->whereIn('package_id', $ids)->update(['tier' => $tier]);
            DB::table('user_packages')->whereIn('package_id', $ids)->where('name', $oldName)->update(['name' => $name]);
            if ($tier !== 'premium') {
                DB::table('material_package')->whereIn('package_id', $ids)->delete();
                $nonTryouts = DB::table('exams')->where('kind', '!=', 'tryout')->pluck('id');
                DB::table('exam_package')->whereIn('package_id', $ids)->whereIn('exam_id', $nonTryouts)->delete();
                foreach ($ids as $packageId) {
                    $limit = $tier === 'regular' ? 1 : 3;
                    $examIds = DB::table('exam_package')->where('package_id', $packageId)->orderBy('exam_id')->pluck('exam_id');
                    DB::table('exam_package')->where('package_id', $packageId)->whereIn('exam_id', $examIds->slice($limit))->delete();
                    DB::table('packages')->where('id', $packageId)->update(['tryout_tokens' => max(0, $limit - $examIds->count())]);
                }
            } else {
                DB::table('packages')->whereIn('id', $ids)->update(['tryout_tokens' => 0]);
            }
            DB::table('transactions')->whereIn('package_id', $ids)->where('status', 'pending')
                ->orderBy('id')->each(function (object $transaction) use ($tier, $oldName, $name): void {
                    $snapshot = json_decode($transaction->package_snapshot, true, flags: JSON_THROW_ON_ERROR);
                    $snapshot['tier'] = $tier;
                    if (($snapshot['name'] ?? null) === $oldName) {
                        $snapshot['name'] = $name;
                    }
                    DB::table('transactions')->where('id', $transaction->id)->update([
                        'package_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                    ]);
                });
        }
    }

    public function down(): void
    {
        foreach (['packages', 'user_packages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('tier');
            });
        }
    }
};
