<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ExamBank;
use App\Enums\MaterialAccess;
use App\Enums\QuestionStatus;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['question_ids' => $this->input('question_ids', []),
            'topic_id' => $this->input('topic_id', $this->route('exam')?->topic_id),
            'requires_topic_completion' => $this->input('requires_topic_completion', $this->route('exam')?->requires_topic_completion ?? false),
            'access_level' => $this->input('access_level', $this->route('exam')?->access_level?->value ?? 'free')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('exams', 'slug')->ignore($this->route('exam')), Rule::notIn(array_column(ExamBank::cases(), 'value'))],
            'kind' => ['required', Rule::in($this->routeIs('admin.materials.quizzes.*') ? ['quiz'] : ['tryout'])],
            'topic_id' => ['nullable', 'integer', 'prohibited_unless:kind,quiz', Rule::exists('topics', 'id')->whereNull('deleted_at')],
            'requires_topic_completion' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'question_count' => ['required', 'integer', 'min:1', 'max:200'],
            'duration_minutes' => ['required', 'integer', 'min:'.($this->input('kind') === 'tryout' ? '1' : '0'), 'max:240'],
            'points_per_correct' => ['required', 'integer', 'min:1', 'max:100'],
            'penalty_wrong' => ['required', 'integer', 'min:0', 'max:100'],
            'passing_score' => ['nullable', 'integer', 'min:0'],
            'random_questions' => ['required', 'boolean'], 'random_options' => ['required', 'boolean'],
            'status' => ['required', Rule::enum(QuestionStatus::class)],
            'access_level' => ['required', Rule::enum(MaterialAccess::class)],
            'question_ids' => ['present', 'array', 'list', 'max:2000'],
            'question_ids.*' => ['required', 'integer', 'distinct', Rule::exists('questions', 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->boolean('requires_topic_completion') && ($this->input('kind') !== 'quiz' || ! $this->filled('topic_id'))) {
                $validator->errors()->add('requires_topic_completion', 'Pilih topik pada sesi latihan sebelum mewajibkan penyelesaian materi.');
            }
            $exam = $this->route('exam');
            if ($exam && ($this->input('kind') !== $exam->kind || $this->input('slug') !== $exam->slug)) {
                $validator->errors()->add('slug', 'Jenis dan alamat sesi tidak dapat diubah setelah dibuat.');
            }
            if (! $validator->errors()->hasAny(['question_count', 'points_per_correct', 'passing_score', 'question_ids', 'question_ids.*'])) {
                $maximum = $this->integer('question_count') * $this->integer('points_per_correct');
                if ($this->input('question_ids') !== []) {
                    $availableQuestions = Question::query()->visibleToMembers()->whereIn('id', $this->input('question_ids'))
                        ->get(['id', 'scoring_type'])->keyBy('id');
                    $scores = collect($this->input('question_ids'))->map(fn (int|string $id): ?Question => $availableQuestions->get($id))
                        ->filter()->map(fn (Question $question): int => $question->isWeighted() ? 5 : $this->integer('points_per_correct'));
                    if ($this->boolean('random_questions')) {
                        $scores = $scores->sortDesc();
                    }
                    if ($scores->isNotEmpty()) {
                        $maximum = $scores->take($this->integer('question_count'))->sum();
                    }
                }
                if ($this->filled('passing_score') && $this->integer('passing_score') > $maximum) {
                    $validator->errors()->add('passing_score', 'Nilai ambang tidak boleh melebihi skor maksimal sesi.');
                }
            }
            if ($this->input('status') === 'published' && ! $validator->errors()->hasAny(['question_ids', 'question_count'])) {
                $ids = array_filter($this->input('question_ids', []), 'is_scalar');
                $available = Question::query()->visibleToMembers()->whereIn('id', $ids)->count();
                if ($available < $this->integer('question_count')) {
                    $validator->errors()->add('question_ids', 'Pilih soal terbit yang cukup untuk jumlah soal sesi sebelum menerbitkan.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return ['title' => 'nama sesi', 'slug' => 'alamat sesi', 'kind' => 'jenis sesi', 'question_count' => 'jumlah soal',
            'duration_minutes' => 'durasi', 'points_per_correct' => 'poin benar', 'penalty_wrong' => 'penalti salah',
            'passing_score' => 'nilai ambang', 'question_ids' => 'pilihan soal', 'topic_id' => 'topik latihan',
            'requires_topic_completion' => 'syarat penyelesaian materi'];
    }
}
