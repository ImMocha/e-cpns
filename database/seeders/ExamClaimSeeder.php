<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamClaim;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ExamClaimSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }
        $exam = Exam::query()->visibleToMembers()->whereIn('kind', ['tryout', 'practice'])->where('free_claim_enabled', true)->first();
        $user = User::query()->where('email', 'peserta@lontar.asia')->first();
        if ($exam === null || $user === null
            || ExamClaim::query()->whereBelongsTo($user)->whereBelongsTo($exam)->exists()) {
            return;
        }
        $path = 'exam-claims/demo/proof.png';
        Storage::disk('local')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/a9sAAAAASUVORK5CYII='));
        ExamClaim::factory()->for($user)->for($exam)->create([
            'exam_title' => $exam->title, 'kind' => $exam->kind, 'terms' => $exam->free_claim_terms,
            'instagram_url' => $exam->free_claim_instagram_url, 'proof_paths' => [$path],
            'customer_note' => 'Contoh pengajuan lokal untuk mencoba verifikasi. Gambar ini hanya placeholder, bukan bukti asli.',
        ]);
    }
}
