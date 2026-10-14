<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuestionDifficulty;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('Dashboard.User.question-bank.index', [
            'questions' => $this->filteredQuestions($filters)
                ->with('section')->latest('published_at')->latest('id')->paginate(12)->withQueryString(),
            'filters' => $filters,
            'sections' => Section::query()->active()->ordered()->withCount([
                'questions as available_questions_count' => fn (Builder $query) => $query->visibleToMembers(),
            ])->get(),
            'difficulties' => QuestionDifficulty::cases(),
            'questionCount' => Question::query()->visibleToMembers()->count(),
        ]);
    }

    public function show(Request $request, Question $question): View
    {
        $this->ensureVisible($request, $question);
        $filters = $this->validatedFilters($request);
        $feedback = $request->session()->get('question_feedback');
        $selectedOption = is_array($feedback) && ($feedback['question_id'] ?? null) === $question->id
            && ($feedback['user_id'] ?? null) === $request->user()->id
            && ($feedback['revision'] ?? null) === $question->revision()
            && is_int($feedback['option'] ?? null) && array_key_exists($feedback['option'], $question->options)
            ? $feedback['option'] : null;
        $canViewExplanations = $request->user()->canViewExplanations();

        return view('Dashboard.User.question-bank.show', [
            'question' => $question->load('section'), 'selectedOption' => $selectedOption,
            'isPreview' => $request->user()->isAdmin(),
            'canViewExplanations' => $canViewExplanations,
            'explanationHtml' => $canViewExplanations && ($selectedOption !== null || $request->user()->isAdmin())
                ? $question->safeExplanation() : null,
            'filters' => $filters,
            'navigation' => $this->navigation($question, $filters),
        ]);
    }

    public function answer(Request $request, Question $question): RedirectResponse
    {
        $this->ensureVisible($request, $question);
        $filters = $this->validatedFilters($request);
        $data = $request->validate([
            'option' => ['required', 'integer', Rule::in(array_keys($question->options))],
            'revision' => ['required', 'string', Rule::in([$question->revision()])],
        ], ['option.required' => 'Pilih jawaban terlebih dahulu.', 'option.in' => 'Pilihan jawaban tidak tersedia.',
            'revision.in' => 'Soal telah diperbarui. Baca soal terbaru sebelum menjawab lagi.']);

        return redirect()->route('question-bank.show', ['question' => $question, ...$filters])->with('question_feedback', [
            'question_id' => $question->id, 'user_id' => $request->user()->id,
            'revision' => $question->revision(), 'option' => (int) $data['option'],
        ]);
    }

    public function image(Request $request, Question $question): StreamedResponse
    {
        $this->ensureVisible($request, $question);
        abort_unless($question->image_path && Storage::disk('local')->exists($question->image_path), 404);

        return Storage::disk('local')->response($question->image_path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function video(Request $request, Question $question): BinaryFileResponse
    {
        $this->ensureVisible($request, $question);
        $disk = Storage::disk('local');
        abort_unless($question->video_path && $disk->exists($question->video_path), 404);

        return response()->file($disk->path($question->video_path), [
            'Content-Type' => str_ends_with($question->video_path, '.webm') ? 'video/webm' : 'video/mp4',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }

    private function ensureVisible(Request $request, Question $question): void
    {
        abort_unless($request->user()->isAdmin() || Question::query()->visibleToMembers()->whereKey($question->id)->exists(), 404);
    }

    /** @return array{q?: string, section_id?: string|int, difficulty?: string, page?: string|int} */
    private function validatedFilters(Request $request): array
    {
        return array_filter($request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'section_id' => ['nullable', 'integer'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]), fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array{q?: string, section_id?: string|int, difficulty?: string, page?: string|int}  $filters
     * @return Builder<Question>
     */
    private function filteredQuestions(array $filters): Builder
    {
        return Question::query()->visibleToMembers()->search($filters['q'] ?? null)
            ->when($filters['section_id'] ?? null, fn (Builder $query, string|int $id) => $query->where('section_id', $id))
            ->when($filters['difficulty'] ?? null, fn (Builder $query, string $difficulty) => $query->where('difficulty', $difficulty));
    }

    /**
     * @param  array{q?: string, section_id?: string|int, difficulty?: string, page?: string|int}  $filters
     * @return array{position: ?int, total: int, previous: ?Question, next: ?Question}
     */
    private function navigation(Question $question, array $filters): array
    {
        $questions = $this->filteredQuestions($filters);
        if (! (clone $questions)->whereKey($question->id)->exists()) {
            return ['position' => null, 'total' => 0, 'previous' => null, 'next' => null];
        }

        $newer = (clone $questions)->where(function (Builder $query) use ($question): void {
            $query->where('published_at', '>', $question->published_at)
                ->orWhere(fn (Builder $sameDate) => $sameDate->where('published_at', $question->published_at)->where('id', '>', $question->id));
        });
        $older = (clone $questions)->where(function (Builder $query) use ($question): void {
            $query->where('published_at', '<', $question->published_at)
                ->orWhere(fn (Builder $sameDate) => $sameDate->where('published_at', $question->published_at)->where('id', '<', $question->id));
        });

        return [
            'position' => (clone $newer)->count() + 1,
            'total' => $questions->count(),
            'previous' => $newer->oldest('published_at')->oldest('id')->first(['id']),
            'next' => $older->latest('published_at')->latest('id')->first(['id']),
        ];
    }
}
