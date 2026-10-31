<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamAttemptController extends Controller
{
    public function save(Request $request, ExamAttempt $examAttempt): JsonResponse
    {
        $this->ensureOwner($request, $examAttempt);

        $data = $request->validate([
            'answers' => ['present', 'array', 'max:200'],
            'answers.*' => ['nullable', 'integer', 'min:0', 'max:9'],
            'flags' => ['present', 'array', 'max:200'],
            'flags.*' => ['boolean'],
            'current' => ['required', 'integer', 'min:0'],
        ]);

        $optionCounts = [];
        foreach ($examAttempt->question_snapshot['questions'] as $question) {
            $optionCounts[(string) $question['id']] = count($question['options']);
        }

        if ($data['current'] >= count($optionCounts)) {
            throw ValidationException::withMessages(['current' => 'Nomor soal tidak valid.']);
        }

        foreach ($data['answers'] as $questionId => $optionIndex) {
            if (! isset($optionCounts[(string) $questionId]) || $optionIndex >= $optionCounts[(string) $questionId]) {
                throw ValidationException::withMessages(['answers' => 'Jawaban berisi soal atau pilihan yang tidak valid.']);
            }
        }

        foreach ($data['flags'] as $questionId => $flag) {
            if (! isset($optionCounts[(string) $questionId])) {
                throw ValidationException::withMessages(['flags' => 'Penanda berisi soal yang tidak valid.']);
            }
        }

        return DB::transaction(function () use ($request, $examAttempt, $data): JsonResponse {
            $attempt = ExamAttempt::query()->whereBelongsTo($request->user())
                ->whereKey($examAttempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->status === ExamAttempt::COMPLETED || $attempt->isExpired()) {
                if ($attempt->status === ExamAttempt::IN_PROGRESS) {
                    $attempt->complete(true);
                }

                return response()->json(['resultUrl' => $attempt->resultUrl()], 409);
            }

            $answers = $attempt->answers ?? [];
            $flags = $attempt->flags ?? [];
            foreach ($data['answers'] as $id => $answer) {
                if ($answer === null) {
                    unset($answers[$id]);
                } else {
                    $answers[$id] = (int) $answer;
                }
            }
            foreach ($data['flags'] as $id => $flag) {
                if ($flag) {
                    $flags[$id] = true;
                } else {
                    unset($flags[$id]);
                }
            }
            $attempt->update([
                'answers' => $answers,
                'flags' => $flags,
                'current_index' => $data['current'],
            ]);

            return response()->json(['savedAt' => now()->toIso8601String(), 'state' => ['answers' => $answers, 'flags' => $flags]]);
        });
    }

    public function submit(Request $request, ExamAttempt $examAttempt): JsonResponse
    {
        $this->ensureOwner($request, $examAttempt);

        return DB::transaction(function () use ($request, $examAttempt): JsonResponse {
            $attempt = ExamAttempt::query()->whereBelongsTo($request->user())
                ->whereKey($examAttempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->status === ExamAttempt::IN_PROGRESS) {
                $attempt->complete($attempt->isExpired());
            }

            return response()->json(['resultUrl' => $attempt->resultUrl()]);
        });
    }

    public function media(Request $request, ExamAttempt $examAttempt, string $question, string $media): BinaryFileResponse
    {
        $this->ensureOwner($request, $examAttempt);
        $snapshot = collect($examAttempt->question_snapshot['questions'])->first(fn (array $item): bool => (string) $item['id'] === $question);
        $path = null;
        if (in_array($media, ['image', 'video'], true)) {
            $path = $snapshot[$media.'_path'] ?? null;
        } elseif ($media === 'explanation-image') {
            abort_unless($examAttempt->status === ExamAttempt::COMPLETED && $request->user()->canViewExplanations(), 404);
            $path = $snapshot['explanation_image_path'] ?? null;
        } elseif (preg_match('/^option-([0-9])$/', $media, $matches)) {
            $path = $snapshot['option_image_paths'][(int) $matches[1]] ?? null;
        }
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($media === 'video') {
            $headers['Content-Type'] = str_ends_with($path, '.webm') ? 'video/webm' : 'video/mp4';
        }

        return response()->file($disk->path($path), $headers)->setPrivate();
    }

    private function ensureOwner(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
        abort_if($attempt->kind !== 'tryout' && ! $request->user()->canAccessLearningFeatures(), 403, 'Latihan soal tersedia dalam paket Premium.');
    }
}
