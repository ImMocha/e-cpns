<?php

declare(strict_types=1);

namespace App;

use App\Models\Exam;
use App\Models\ExamClaim;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ExamClaims
{
    /** @param array{instagram_username: string, customer_note?: ?string, terms_version: string, proofs: array<UploadedFile>, accept_terms: mixed} $data */
    public function submit(User $user, Exam $exam, array $data): ExamClaim
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($user, $exam, $data, &$paths): ExamClaim {
                $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $exam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
                abort_if(! $user->isActive() || $user->isAdmin(), 403);
                $this->ensureAvailable($exam);
                if (! hash_equals($exam->freeClaimTermsVersion(), $data['terms_version'])) {
                    throw ValidationException::withMessages(['proofs' => 'Syarat telah diperbarui. Muat ulang halaman dan baca syarat terbaru sebelum mengirim bukti.']);
                }
                if (ExamClaim::query()->whereBelongsTo($user)->whereBelongsTo($exam)->whereIn('status', ['pending', 'approved'])->exists()) {
                    throw ValidationException::withMessages(['proofs' => 'Pengajuan sesi ini sedang ditinjau atau sudah disetujui.']);
                }
                foreach ($data['proofs'] as $proof) {
                    $path = $proof->store('exam-claims/'.$user->id, 'local');
                    if ($path === false) {
                        throw new RuntimeException('Bukti gagal disimpan. Silakan coba kembali.');
                    }
                    $paths[] = $path;
                }

                return ExamClaim::create([
                    'user_id' => $user->id, 'exam_id' => $exam->id, 'exam_title' => $exam->title, 'kind' => $exam->kind,
                    'terms' => $exam->free_claim_terms, 'instagram_url' => $exam->free_claim_instagram_url,
                    'instagram_username' => $data['instagram_username'], 'customer_note' => $data['customer_note'] ?? null,
                    'proof_paths' => $paths, 'status' => 'pending',
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    public function review(ExamClaim $claim, User $admin, string $status, ?string $note): void
    {
        abort_unless($admin->isAdmin(), 403);
        DB::transaction(function () use ($claim, $admin, $status, $note): void {
            $user = User::withTrashed()->whereKey($claim->user_id)->lockForUpdate()->firstOrFail();
            $exam = Exam::withTrashed()->whereKey($claim->exam_id)->lockForUpdate()->firstOrFail();
            $claim = ExamClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if ($claim->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
            }
            if ($status === 'approved') {
                $this->ensureAvailable($exam);
                if ($user->trashed() || ! $user->isActive() || $user->isAdmin()) {
                    throw ValidationException::withMessages(['status' => 'Akses hanya dapat dibuka untuk akun peserta aktif.']);
                }
            }
            $claim->update([
                'status' => $status, 'review_note' => $note, 'reviewed_by' => $admin->id, 'reviewed_at' => now(),
            ]);
        });
    }

    private function ensureAvailable(Exam $exam): void
    {
        if ($exam->trashed() || ! $exam->free_claim_enabled || ! in_array($exam->kind, ['tryout', 'practice'], true)
            || ! Exam::query()->visibleToMembers()->whereKey($exam->id)->exists()
            || $exam->questions()->visibleToMembers()->count() < $exam->question_count) {
            throw ValidationException::withMessages(['exam' => 'Unlock gratis untuk sesi ini sedang tidak tersedia.']);
        }
    }
}
