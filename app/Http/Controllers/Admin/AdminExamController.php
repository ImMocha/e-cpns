<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminExamController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['kind' => ['nullable', 'in:quiz,tryout'], 'q' => ['nullable', 'string', 'max:180']]);

        return view('Dashboard.Admin.exams.index', ['exams' => Exam::query()
            ->when($filters['kind'] ?? null, fn ($query, $kind) => $query->where('kind', $kind))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))
            ->withCount(['questions as available_count' => fn ($query) => $query->visibleToMembers()])
            ->latest('id')->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->form(new Exam(['kind' => 'quiz', 'status' => 'draft', 'question_count' => 1,
            'duration_minutes' => 0, 'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true]));
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = new Exam;
        $this->persist($request, $exam);

        return redirect()->route('admin.exams.edit', $exam)->with('success', 'Sesi berhasil disimpan.');
    }

    public function edit(Exam $exam): View
    {
        return $this->form($exam);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->persist($request, $exam);

        return redirect()->route('admin.exams.edit', $exam)->with('success', 'Pengaturan sesi berhasil disimpan. Sesi yang sudah dimulai tetap memakai pengaturan sebelumnya.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Sesi dihapus dari katalog. Riwayat peserta tetap tersimpan.');
    }

    private function form(Exam $exam): View
    {
        return view('Dashboard.Admin.exams.form', ['exam' => $exam,
            'selected' => $exam->exists ? $exam->questions()->pluck('questions.id')->all() : [],
            'questions' => Question::query()->with('section')->orderBy('section_id')->orderBy('id')->get()]);
    }

    private function persist(ExamRequest $request, Exam $exam): void
    {
        DB::transaction(function () use ($request, $exam): void {
            $data = $request->safe()->except('question_ids');
            $data['updated_by'] = $request->user()->id;
            if (! $exam->exists) {
                $data['created_by'] = $request->user()->id;
            }
            $exam->fill($data)->save();
            $exam->questions()->sync($request->validated('question_ids'));
        });
    }
}
