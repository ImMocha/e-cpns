<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class AdminQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'],
            'section_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,published'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'usage' => ['nullable', 'in:quiz,tryout,unused'],
            'exam_id' => ['nullable', 'integer', Rule::exists('exams', 'id')->whereNull('deleted_at')]]);

        return view('Dashboard.Admin.questions.index', [
            'questions' => Question::query()->search($filters['q'] ?? null)
                ->when($filters['section_id'] ?? null, fn (Builder $query, string|int $id) => $query->where('section_id', $id))
                ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
                ->when($filters['difficulty'] ?? null, fn (Builder $query, string $difficulty) => $query->where('difficulty', $difficulty))
                ->when(($filters['usage'] ?? null) === 'unused', fn (Builder $query) => $query->whereDoesntHave('exams'))
                ->when(($filters['exam_id'] ?? null) || in_array($filters['usage'] ?? null, ['quiz', 'tryout'], true),
                    fn (Builder $query) => $query->whereHas('exams', fn (Builder $exams) => $exams
                        ->when($filters['exam_id'] ?? null, fn (Builder $examQuery, string|int $id) => $examQuery->whereKey($id))
                        ->when(in_array($filters['usage'] ?? null, ['quiz', 'tryout'], true), fn (Builder $examQuery) => $examQuery->where('kind', $filters['usage']))))
                ->with(['section', 'author', 'exams' => fn (BelongsToMany $exams) => $exams->select('exams.id', 'title', 'kind', 'status')->orderBy('kind')->orderBy('title')])
                ->latest('updated_at')->latest('id')->paginate(10)->withQueryString(),
            'sections' => Section::query()->ordered()->get(),
            'exams' => Exam::query()->orderBy('kind')->orderBy('title')->get(['id', 'title', 'kind', 'status']),
            'difficulties' => QuestionDifficulty::cases(),
            'stats' => ['total' => Question::count(), 'published' => Question::where('status', QuestionStatus::Published)->count(),
                'draft' => Question::where('status', QuestionStatus::Draft)->count(),
                'sections' => Question::query()->distinct()->count('section_id')],
        ]);
    }

    public function create(): View
    {
        return $this->form(new Question(['status' => QuestionStatus::Draft, 'difficulty' => QuestionDifficulty::Medium,
            'body' => '', 'explanation' => '', 'options' => ['', '', '', '', '']]));
    }

    public function store(QuestionRequest $request): RedirectResponse|JsonResponse
    {
        $question = new Question;
        $this->persist($request, $question);

        if ($request->expectsJson()) {
            return response()->json($this->editorData($question), 201);
        }

        return redirect()->route('admin.questions.edit', $question)->with('success', 'Soal berhasil disimpan.');
    }

    public function show(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($this->editorData($question));
        }

        return redirect()->route('question-bank.show', $question);
    }

    public function edit(Question $question): View
    {
        return $this->form($question);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse|JsonResponse
    {
        $this->persist($request, $question);

        if ($request->expectsJson()) {
            return response()->json($this->editorData($question));
        }

        return redirect()->route('admin.questions.edit', $question)->with('success', 'Perubahan soal berhasil disimpan.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        $question->delete();

        if ($request->expectsJson()) {
            return response()->json(['id' => $question->id, 'message' => 'Soal diarsipkan. Riwayat pengerjaan tetap tersimpan.']);
        }

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    /** @return array{question: array<string, mixed>, html: string} */
    private function editorData(Question $question): array
    {
        $question->load('section')->loadCount('exams');

        return [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'section_id' => (int) $question->section_id,
                'body' => $question->safeBody(),
                'options' => $question->options,
                'correct_option' => $question->correct_option,
                'scoring_type' => $question->scoring_type->value,
                'option_scores' => $question->option_scores,
                'shuffle_options' => $question->shuffle_options,
                'explanation' => $question->safeExplanation(),
                'difficulty' => $question->difficulty->value,
                'status' => $question->status->value,
                'image_url' => $question->image_path ? route('question-bank.image', $question) : null,
                'video_url' => $question->video_path ? route('question-bank.video', $question) : null,
                'usage_count' => $question->exams_count,
                'update_url' => route('admin.questions.update', $question),
            ],
            'html' => view('Dashboard.Admin.exams._question', ['question' => $question, 'isSelected' => false])->render(),
        ];
    }

    private function form(Question $question): View
    {
        return view($question->exists ? 'Dashboard.Admin.questions.edit' : 'Dashboard.Admin.questions.create', [
            'question' => $question, 'sections' => Section::query()->ordered()->get(),
            'difficulties' => QuestionDifficulty::cases(), 'statuses' => QuestionStatus::cases(),
            'exams' => Exam::query()->withCount('questions')->orderBy('kind')->orderBy('title')->get(),
            'selectedExamIds' => $question->exists ? $question->exams()->pluck('exams.id')->all() : [],
        ]);
    }

    private function persist(QuestionRequest $request, Question $question): void
    {
        $oldPaths = ['image' => $question->image_path, 'video' => $question->video_path];
        $newPaths = [];
        $attributes = $request->questionAttributes();
        $attributes['published_at'] = $attributes['status'] === QuestionStatus::Published->value ? ($question->published_at ?? now()) : null;
        $attributes['updated_by'] = $request->user()->id;
        if (! $question->exists) {
            $attributes['created_by'] = $request->user()->id;
        }

        try {
            foreach ($oldPaths as $media => $oldPath) {
                $newPaths[$media] = $request->file($media)?->store('questions/'.$media.'s', 'local');
                abort_if($newPaths[$media] === false, 500, 'Media soal gagal disimpan. Silakan coba lagi.');
                $attributes[$media.'_path'] = $newPaths[$media] ?: ($request->boolean('remove_'.$media) ? null : $oldPath);
            }
            DB::transaction(function () use ($request, $question, $attributes): void {
                $question->fill($attributes)->save();
                if ($request->has('exam_ids')) {
                    $archivedExamIds = $question->exams()->onlyTrashed()->pluck('exams.id')->all();
                    $question->exams()->sync([...$request->validated('exam_ids'), ...$archivedExamIds]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($newPaths as $newPath) {
                if ($newPath) {
                    Storage::disk('local')->delete($newPath);
                }
            }
            throw $exception;
        }

        foreach ($oldPaths as $media => $oldPath) {
            if ($oldPath && $oldPath !== $question->{$media.'_path'}
                && ! ExamAttempt::query()->whereJsonContains('question_snapshot->mediaPaths', $oldPath)->exists()) {
                Storage::disk('local')->delete($oldPath);
            }
        }
    }
}
