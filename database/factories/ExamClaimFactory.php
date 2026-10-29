<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExamClaim> */
class ExamClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_id' => Exam::factory()->tryout()->claimable(),
            'exam_title' => 'Tryout Gratis Bersyarat',
            'kind' => 'tryout',
            'terms' => 'Follow Instagram dan upload poster ke Story.',
            'instagram_url' => 'https://www.instagram.com/ganeshanusa/',
            'instagram_username' => 'peserta_belajar',
            'proof_paths' => [],
            'status' => 'pending',
        ];
    }
}
