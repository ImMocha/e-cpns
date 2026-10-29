<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExamKind;
use App\ExamClaims;
use App\Http\Requests\StoreExamClaimRequest;
use App\Models\Exam;
use App\Models\ExamClaim;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamClaimController extends Controller
{
    public function create(Request $request, Exam $exam): View
    {
        abort_unless($exam->free_claim_enabled && in_array($exam->kind, ['tryout', 'practice'], true)
            && Exam::query()->visibleToMembers()->whereKey($exam->id)->exists(), 404);

        return view('Dashboard.User.exams.unlock', [
            'exam' => $exam,
            'claims' => ExamClaim::query()->whereBelongsTo($request->user())->whereBelongsTo($exam)->latest('id')->get(),
            'catalogRoute' => ExamKind::from($exam->kind)->catalogRoute(),
            'contentAvailable' => $exam->questions()->visibleToMembers()->count() >= $exam->question_count,
        ]);
    }

    public function store(StoreExamClaimRequest $request, Exam $exam, ExamClaims $claims): RedirectResponse
    {
        $claims->submit($request->user(), $exam, $request->validated());

        return redirect()->route('exam-claims.create', $exam)->with('success', 'Bukti terkirim. Tunggu verifikasi admin untuk membuka sesi ini.');
    }

    public function proof(Request $request, ExamClaim $examClaim, int $proof): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $examClaim->user_id === $request->user()->id, 404);
        $path = $examClaim->proof_paths[$proof] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
