<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizeExpiredExams extends Command
{
    protected $signature = 'exams:finalize-expired';

    protected $description = 'Submit expired exam attempts using saved answers';

    public function handle(): int
    {
        $count = 0;
        ExamAttempt::query()->where('status', ExamAttempt::IN_PROGRESS)->where('expires_at', '<=', now())
            ->select('id')->chunkById(100, function ($attempts) use (&$count): void {
                foreach ($attempts as $item) {
                    DB::transaction(function () use ($item, &$count): void {
                        $attempt = ExamAttempt::query()->lockForUpdate()->find($item->id);
                        if ($attempt?->status === ExamAttempt::IN_PROGRESS && $attempt->isExpired()) {
                            $attempt->complete(true);
                            $count++;
                        }
                    });
                }
            });
        $this->info($count.' sesi kedaluwarsa diselesaikan.');

        return self::SUCCESS;
    }
}
