<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('packages')->whereIn('tier', ['regular', 'bundling'])->orderBy('id')
                ->each(function (object $package): void {
                    $tokens = $package->tier === 'regular' ? 1 : 3;
                    DB::table('packages')->where('id', $package->id)->update(['tryout_tokens' => $tokens]);
                    DB::table('material_package')->where('package_id', $package->id)->delete();
                    DB::table('exam_package')->where('package_id', $package->id)->delete();
                    DB::table('transactions')->where('package_id', $package->id)->where('status', 'pending')
                        ->orderBy('id')->each(function (object $transaction) use ($tokens): void {
                            $snapshot = json_decode($transaction->package_snapshot, true, flags: JSON_THROW_ON_ERROR);
                            $snapshot['tryout_tokens'] = $tokens;
                            $snapshot['materials'] = [];
                            $snapshot['exams'] = [];
                            DB::table('transactions')->where('id', $transaction->id)->update([
                                'package_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                            ]);
                        });
                });
        });
    }

    /** Previous catalog selections cannot be reconstructed automatically. */
    public function down(): void {}
};
