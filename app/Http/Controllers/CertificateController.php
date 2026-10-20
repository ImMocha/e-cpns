<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        ExamAttempt::finalizeExpiredFor($request->user());

        return view('Dashboard.User.certificates.index', [
            'attempts' => ExamAttempt::query()->whereBelongsTo($request->user())
                ->certifiable()->select(['id', 'user_id', 'title', 'submitted_at', 'score', 'max_score', 'section_scores'])
                ->orderByDesc('submitted_at')->orderByDesc('id')->paginate(12),
        ]);
    }

    public function show(Request $request, int $examAttempt): View
    {
        $attempt = ExamAttempt::query()->whereBelongsTo($request->user())
            ->certifiable()->findOrFail($examAttempt);

        return view('Dashboard.User.certificates.show', [
            'attempt' => $attempt,
            'participant' => $request->user(),
            'sections' => $attempt->sectionGrades(),
        ]);
    }
}
