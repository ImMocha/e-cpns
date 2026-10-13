<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HistoryController extends SliceController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        ExamAttempt::finalizeExpiredFor($user);
        $kind = in_array($request->query('kind'), ['tryout', 'quiz'], true)
            ? $request->query('kind') : null;
        $status = in_array($request->query('status'), [ExamAttempt::COMPLETED, ExamAttempt::IN_PROGRESS], true)
            ? $request->query('status') : null;

        return view('Dashboard.User.history.index', [
            'summary' => ExamAttempt::summaryFor($user),
            'attempts' => ExamAttempt::query()->whereBelongsTo($user)
                ->when($kind, fn ($query) => $query->where('kind', $kind))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest('id')->paginate(12)->withQueryString(),
            'kind' => $kind,
            'status' => $status,
        ]);
    }
}
