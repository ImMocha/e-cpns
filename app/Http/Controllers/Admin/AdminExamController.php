<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminExamController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filters = $request->validate(['kind' => ['nullable', 'in:quiz,tryout'], 'q' => ['nullable', 'string', 'max:180'],
            'topic_id' => ['nullable', 'integer', 'min:1'], 'section_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,published']]);
        $isQuiz = $request->routeIs('admin.materials.quizzes.*');
        if (! $isQuiz && (($filters['kind'] ?? null) === 'quiz' || $request->filled('topic_id'))) {
            unset($filters['kind']);

            return redirect()->route('admin.materials.quizzes.index', $filters);
        }

        $selectedTopic = $isQuiz && $request->filled('topic_id') ? Topic::with('section')->findOrFail($request->integer('topic_id')) : null;
        $sectionId = $isQuiz ? ($request->filled('section_id') ? $request->integer('section_id') : $selectedTopic?->section_id) : null;
        $selectedSection = $sectionId ? Section::findOrFail($sectionId) : null;
        abort_if($selectedTopic && $selectedTopic->section_id !== $sectionId, 404);

        $query = Exam::query()->where('kind', $isQuiz ? 'quiz' : 'tryout')
            ->when($sectionId, fn (Builder $query): Builder => $query->whereHas('topic', fn (Builder $topic): Builder => $topic->where('section_id', $sectionId)))
            ->when($filters['topic_id'] ?? null, fn ($query, $topic) => $query->where('topic_id', $topic))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        return view('Dashboard.Admin.exams.index', [
            'exams' => $query->with('topic.section')->withCount(['questions as available_count' => fn ($query) => $query->visibleToMembers()])
                ->latest('id')->paginate(12)->withQueryString(),
            'isQuiz' => $isQuiz,
            'routePrefix' => $isQuiz ? 'admin.materials.quizzes' : 'admin.exams',
            'selectedSection' => $selectedSection,
            'selectedTopic' => $selectedTopic,
            'sections' => $isQuiz ? Section::query()->ordered()->withCount('topics')->get() : collect(),
            'topics' => $isQuiz ? Topic::query()->whereHas('section')->with('section')
                ->when($sectionId, fn (Builder $query): Builder => $query->where('section_id', $sectionId))->ordered()->get() : collect(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $request->validate(['topic_id' => ['nullable', 'integer', 'min:1']]);
        $isQuiz = $request->routeIs('admin.materials.quizzes.*');
        if (! $isQuiz && ($request->filled('topic_id') || $request->query('kind') === 'quiz')) {
            return redirect()->route('admin.materials.quizzes.create', $request->only(['topic_id', 'section_id']));
        }
        $topic = $isQuiz && $request->filled('topic_id') ? Topic::findOrFail($request->integer('topic_id')) : null;

        return $this->form(new Exam(['kind' => $isQuiz ? 'quiz' : 'tryout', 'topic_id' => $topic?->id, 'requires_topic_completion' => false,
            'status' => 'draft', 'question_count' => 1,
            'duration_minutes' => $isQuiz ? 0 : 30, 'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true]));
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = new Exam;
        $this->persist($request, $exam);

        return redirect()->route($this->routePrefix($exam).'.edit', $exam)->with('success', 'Sesi berhasil disimpan.');
    }

    public function edit(Request $request, Exam $exam): View|RedirectResponse
    {
        if ($request->routeIs('admin.materials.quizzes.*') !== ($exam->kind === 'quiz')) {
            return redirect()->route($this->routePrefix($exam).'.edit', $exam);
        }

        return $this->form($exam);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->persist($request, $exam);

        return redirect()->route($this->routePrefix($exam).'.edit', $exam)->with('success', 'Pengaturan sesi berhasil disimpan. Sesi yang sudah dimulai tetap memakai pengaturan sebelumnya.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless($request->routeIs('admin.materials.quizzes.*') === ($exam->kind === 'quiz'), 404);
        $exam->delete();

        return redirect()->route($this->routePrefix($exam).'.index', $exam->kind === 'quiz' && $exam->topic_id ? ['topic_id' => $exam->topic_id] : [])
            ->with('success', 'Sesi dihapus dari katalog. Riwayat peserta tetap tersimpan.');
    }

    private function form(Exam $exam): View
    {
        $topic = $exam->topic_id ? Topic::find($exam->topic_id) : null;

        return view('Dashboard.Admin.exams.form', ['exam' => $exam,
            'isQuiz' => $exam->kind === 'quiz',
            'routePrefix' => $this->routePrefix($exam),
            'returnUrl' => $exam->kind === 'quiz' && $topic
                ? route('admin.materials.index', ['section_id' => $topic->section_id, 'topic_id' => $topic->id])
                : route($this->routePrefix($exam).'.index'),
            'topics' => Topic::query()->whereHas('section')->with('section')->ordered()->get(),
            'selected' => $exam->exists ? $exam->questions()->pluck('questions.id')->all() : [],
            'questions' => Question::query()->with('section')->withCount('exams')->orderBy('section_id')->orderBy('id')->get(),
            'sections' => Section::query()->ordered()->get(),
            'difficulties' => QuestionDifficulty::cases(),
            'inlineQuestion' => new Question(['status' => QuestionStatus::Draft, 'difficulty' => QuestionDifficulty::Medium,
                'body' => '', 'explanation' => '', 'options' => ['', '', '', '', '']]),
        ]);
    }

    private function routePrefix(Exam $exam): string
    {
        return $exam->kind === 'quiz' ? 'admin.materials.quizzes' : 'admin.exams';
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
