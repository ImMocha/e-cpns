<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TryoutHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in([ExamAttempt::COMPLETED, ExamAttempt::IN_PROGRESS])],
        ]);
        $search = trim($filters['q'] ?? '');
        $participant = ! empty($filters['user_id'])
            ? User::withTrashed()->findOrFail($filters['user_id']) : null;

        $attempts = ExamAttempt::query()
            ->select(['id', 'user_id', 'title', 'status', 'started_at', 'submitted_at', 'expires_at',
                'score', 'max_score', 'section_scores', 'duration_seconds'])
            ->where('kind', 'tryout')
            ->with(['user' => fn (BelongsTo $query) => $query->withTrashed()->select('id', 'name', 'email', 'deleted_at')])
            ->when($participant, fn (Builder $query) => $query->whereBelongsTo($participant))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $query) => $query->withTrashed()
                            ->where(fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('started_at')->latest('id')
            ->paginate(15)->withQueryString();

        return view('Dashboard.Admin.users.history', [
            'attempts' => $attempts,
            'participant' => $participant,
            'search' => $search,
            'status' => $filters['status'] ?? null,
        ]);
    }
}
