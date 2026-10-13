<?php

namespace Database\Seeders;

use App\Enums\ExamBank;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'peserta@lontar.asia')->first();

        if ($user === null) {
            return;
        }

        foreach ([ExamBank::QuizSilogisme, ExamBank::TryoutSkd5] as $bank) {
            if ($user->examAttempts()->where('bank_slug', $bank->value)->exists()) {
                continue;
            }

            $attempt = ExamAttempt::resumeOrStart($user, $bank);
            $attempt->answers = collect($attempt->question_snapshot['questions'])
                ->mapWithKeys(fn (array $question): array => [(string) $question['id'] => $question['answer']])
                ->all();
            $attempt->save();
            $attempt->complete();
        }
    }
}
