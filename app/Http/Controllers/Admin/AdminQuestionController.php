<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'],
            'section_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,published'],
            'difficulty' => ['nullable', 'in:easy,medium,hard']]);

        return view('Dashboard.Admin.questions.index', [
            'questions' => Question::query()->search($filters['q'] ?? null)
                ->when($filters['section_id'] ?? null, fn (Builder $query, string|int $id) => $query->where('section_id', $id))
                ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
                ->when($filters['difficulty'] ?? null, fn (Builder $query, string $difficulty) => $query->where('difficulty', $difficulty))
                ->with(['section', 'author'])->latest('updated_at')->latest('id')->paginate(10)->withQueryString(),
            'sections' => Section::query()->ordered()->get(),
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

    public function store(QuestionRequest $request): RedirectResponse
    {
        $question = new Question;
        $this->persist($request, $question);

        return redirect()->route('admin.questions.edit', $question)->with('success', 'Soal berhasil disimpan.');
    }

    public function show(Question $question): RedirectResponse
    {
        return redirect()->route('question-bank.show', $question);
    }

    public function edit(Question $question): View
    {
        return $this->form($question);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->persist($request, $question);

        return redirect()->route('admin.questions.edit', $question)->with('success', 'Perubahan soal berhasil disimpan.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    private function form(Question $question): View
    {
        return view($question->exists ? 'Dashboard.Admin.questions.edit' : 'Dashboard.Admin.questions.create', [
            'question' => $question, 'sections' => Section::query()->ordered()->get(),
            'difficulties' => QuestionDifficulty::cases(), 'statuses' => QuestionStatus::cases(),
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
            $question->fill($attributes)->save();
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
