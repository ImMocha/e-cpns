<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\ExamClaims;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewExamClaimRequest;
use App\Models\ExamClaim;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamClaimController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:all,pending,approved,rejected']]);
        $status = $filters['status'] ?? 'all';

        return view('Dashboard.Admin.exam-claims.index', [
            'claims' => ExamClaim::query()->with(['user', 'reviewer'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest('id')->paginate(15)->withQueryString(),
            'status' => $status,
            'pendingCount' => ExamClaim::query()->where('status', 'pending')->count(),
        ]);
    }

    public function show(ExamClaim $examClaim): View
    {
        return view('Dashboard.Admin.exam-claims.show', [
            'claim' => $examClaim->load(['user', 'exam', 'reviewer']),
            'history' => ExamClaim::query()->where('user_id', $examClaim->user_id)->where('id', '<', $examClaim->id)
                ->with('reviewer')->latest('id')->paginate(10, ['*'], 'history_page')->withQueryString(),
        ]);
    }

    public function update(ReviewExamClaimRequest $request, ExamClaim $examClaim, ExamClaims $claims): RedirectResponse
    {
        $claims->review($examClaim, $request->user(), $request->validated('status'), $request->validated('review_note'));

        return redirect()->route('admin.exam-claims.show', $examClaim)->with('success', 'Pengajuan berhasil diproses.');
    }
}
