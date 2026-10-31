<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\ArticleContent;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $question = $this->route('question');
        $this->merge(['scoring_type' => $this->input('scoring_type', $question instanceof Question ? $question->scoring_type->value : 'binary')]);
        if ($this->has('exam_ids') && $this->input('exam_ids') === null) {
            $this->merge(['exam_ids' => []]);
        }
        if (is_array($this->input('options'))) {
            $this->merge(['options' => array_map(fn (mixed $option): mixed => is_string($option) ? trim($option) : $option, $this->input('options'))]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->whereNull('deleted_at')],
            'body' => ['required', 'string', 'max:50000'],
            'options' => ['required', 'array', 'list', 'min:2', 'max:10'],
            'options.*' => ['nullable', 'string', 'max:2000'],
            'option_images' => ['sometimes', 'array:0,1,2,3,4,5,6,7,8,9', 'max:10'],
            'option_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'option_image_sources' => ['sometimes', 'array', 'list', 'max:10'],
            'option_image_sources.*' => ['nullable', 'integer', 'min:0', 'max:9'],
            'remove_option_images' => ['sometimes', 'array:0,1,2,3,4,5,6,7,8,9', 'max:10'],
            'remove_option_images.*' => ['boolean'],
            'scoring_type' => ['required', Rule::enum(QuestionScoring::class)],
            'correct_option' => ['exclude_if:scoring_type,weighted', 'required', 'integer', 'min:0', 'max:9'],
            'option_scores' => ['exclude_unless:scoring_type,weighted', 'required', 'array', 'list', 'min:2', 'max:10'],
            'option_scores.*' => ['required', 'integer', 'min:1', 'max:5'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:50000'],
            'difficulty' => ['required', Rule::enum(QuestionDifficulty::class)],
            'status' => ['required', Rule::enum(QuestionStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'explanation_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_explanation_image' => ['sometimes', 'boolean'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'extensions:mp4,webm', 'max:51200'],
            'remove_video' => ['sometimes', 'boolean'],
            'exam_ids' => ['sometimes', 'array', 'list', 'max:2000'],
            'exam_ids.*' => ['required', 'integer', 'distinct', Rule::exists('exams', 'id')->whereNull('deleted_at')],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['options', 'options.*', 'option_image_sources', 'option_image_sources.*', 'option_images', 'option_images.*', 'remove_option_images', 'remove_option_images.*'])) {
                $options = $this->input('options');
                if ($this->has('option_image_sources') && count($this->input('option_image_sources')) !== count($options)) {
                    $validator->errors()->add('option_image_sources', 'Urutan gambar pilihan tidak valid.');
                }
                $seen = [];
                foreach ($options as $index => $option) {
                    $imagePath = $this->retainedOptionImagePath($index);
                    $hasUpload = $this->hasFile('option_images.'.$index);
                    if (trim((string) $option) === '' && ! $hasUpload && ! $imagePath) {
                        $validator->errors()->add('options.'.$index, 'Setiap pilihan jawaban harus diisi.');
                    }
                    $signature = json_encode([(string) $option, $imagePath]);
                    if (! $hasUpload && isset($seen[$signature])) {
                        $validator->errors()->add('options.'.$index, 'Pilihan jawaban tidak boleh sama.');
                        $validator->errors()->add('options.'.$seen[$signature], 'Pilihan jawaban tidak boleh sama.');
                    }
                    if (! $hasUpload) {
                        $seen[$signature] = $index;
                    }
                }
                foreach (['option_images', 'remove_option_images'] as $field) {
                    foreach (array_keys($field === 'option_images' ? $this->file($field, []) : $this->input($field, [])) as $index) {
                        if (! array_key_exists($index, $options)) {
                            $validator->errors()->add($field.'.'.$index, 'Pilihan jawaban untuk gambar ini tidak tersedia.');
                        }
                    }
                }
            }
            if (! $validator->errors()->has('body') && ArticleContent::text(ArticleContent::sanitize($this->input('body'))) === '') {
                $validator->errors()->add('body', 'Pertanyaan harus berisi teks yang dapat dibaca.');
            }
            if ($this->input('scoring_type') === 'binary' && ! $validator->errors()->hasAny(['options', 'correct_option']) && is_array($this->input('options')) && ! array_key_exists($this->integer('correct_option'), $this->input('options'))) {
                $validator->errors()->add('correct_option', 'Pilih kunci dari pilihan jawaban yang tersedia.');
            }
            if ($this->input('scoring_type') === 'weighted') {
                if (! $validator->errors()->has('section_id') && ! Section::query()->whereKey($this->input('section_id'))->where('code', 'TKP')->exists()) {
                    $validator->errors()->add('scoring_type', 'Penilaian skor per pilihan hanya tersedia untuk bidang TKP.');
                }
                if (! $validator->errors()->hasAny(['options', 'options.*', 'option_scores', 'option_scores.*'])) {
                    $scores = $this->input('option_scores');
                    if (count($scores) !== count($this->input('options'))) {
                        $validator->errors()->add('option_scores', 'Isi satu skor untuk setiap pilihan jawaban.');
                    } elseif (max(array_map('intval', $scores)) !== 5) {
                        $validator->errors()->add('option_scores', 'Setidaknya satu pilihan harus bernilai 5.');
                    }
                }
            }
            $question = $this->route('question');
            $hasExplanationImage = $this->hasFile('explanation_image') || ($question instanceof Question && $question->explanation_image_path && ! $this->boolean('remove_explanation_image'));
            if (! $validator->errors()->has('explanation') && $this->input('status') === 'published' && ! $hasExplanationImage && ArticleContent::text(ArticleContent::sanitize($this->input('explanation'))) === '') {
                $validator->errors()->add('explanation', 'Lengkapi pembahasan sebelum menerbitkan soal.');
            }
            if ($question instanceof Question && $this->has('exam_ids') && ! $validator->errors()->hasAny(['exam_ids', 'exam_ids.*'])
                && Question::query()->visibleToMembers()->whereKey($question->id)->exists()) {
                $removedExams = $question->exams()->published()->whereNotIn('exams.id', $this->input('exam_ids'))
                    ->withCount(['questions as remaining_questions_count' => fn (Builder $query) => $query->visibleToMembers()->where('questions.id', '!=', $question->id)])->get();
                foreach ($removedExams as $exam) {
                    if ($exam->remaining_questions_count < $exam->question_count) {
                        $validator->errors()->add('exam_ids', 'Soal tidak dapat dilepas dari "'.$exam->title.'" karena jumlah soal terbit tidak mencukupi. Tambahkan soal pengganti atau ubah jumlah soal pada sesi terlebih dahulu.');
                    }
                }
            }
        }];
    }

    public function messages(): array
    {
        return ['options.min' => 'Soal membutuhkan minimal 2 pilihan jawaban.', 'options.max' => 'Soal dapat memiliki maksimal 10 pilihan jawaban.',
            'options.list' => 'Urutan pilihan jawaban tidak valid.', 'options.*.required' => 'Setiap pilihan jawaban harus diisi.',
            'options.*.distinct' => 'Pilihan jawaban tidak boleh sama.', 'correct_option.required' => 'Pilih satu jawaban yang benar.',
            'option_scores.required' => 'Isi skor 1–5 untuk setiap pilihan TKP.',
            'option_scores.*.required' => 'Setiap pilihan TKP harus memiliki skor.',
            'option_scores.*.integer' => 'Skor pilihan harus berupa bilangan bulat 1–5.',
            'option_scores.*.min' => 'Skor pilihan minimal 1.',
            'option_scores.*.max' => 'Skor pilihan maksimal 5.',
            'video.mimetypes' => 'Video soal harus berupa file MP4 atau WebM.',
            'video.extensions' => 'Video soal harus menggunakan ekstensi .mp4 atau .webm.',
            'video.max' => 'Ukuran video soal maksimal 50 MB.',
            'exam_ids.array' => 'Pilihan sesi tidak valid.', 'exam_ids.list' => 'Urutan pilihan sesi tidak valid.',
            'exam_ids.max' => 'Pilih maksimal 2000 sesi.', 'exam_ids.*.required' => 'Pilih sesi yang tersedia.',
            'exam_ids.*.integer' => 'Pilihan sesi tidak valid.', 'exam_ids.*.distinct' => 'Sesi yang sama tidak boleh dipilih dua kali.',
            'exam_ids.*.exists' => 'Sesi yang dipilih sudah dihapus atau tidak tersedia.'];
    }

    public function attributes(): array
    {
        return ['title' => 'judul soal', 'section_id' => 'bidang', 'body' => 'pertanyaan', 'difficulty' => 'tingkat kesulitan',
            'status' => 'status', 'explanation' => 'pembahasan', 'image' => 'gambar soal', 'video' => 'video soal', 'correct_option' => 'kunci jawaban',
            'scoring_type' => 'jenis penilaian', 'option_scores' => 'skor pilihan', 'exam_ids' => 'sesi tujuan',
            'option_images.*' => 'gambar pilihan', 'explanation_image' => 'gambar pembahasan'];
    }

    public function retainedOptionImagePath(int $index): ?string
    {
        $question = $this->route('question');
        $source = $this->has('option_image_sources') ? $this->input('option_image_sources.'.$index) : $index;

        return $question instanceof Question && $source !== null && ! $this->boolean('remove_option_images.'.$index)
            ? ($question->option_image_paths[$source] ?? null) : null;
    }

    /** @return array<string, mixed> */
    public function questionAttributes(): array
    {
        $data = $this->safe()->only(['title', 'section_id', 'body', 'options', 'correct_option', 'scoring_type', 'option_scores', 'shuffle_options', 'explanation', 'difficulty', 'status']);
        $data['option_scores'] = $data['scoring_type'] === 'weighted' ? array_map('intval', $data['option_scores']) : null;
        $data['correct_option'] = $data['scoring_type'] === 'weighted' ? null : (int) $data['correct_option'];
        $data['title'] = trim($data['title']);
        $data['options'] = array_map(fn (?string $option): string => trim((string) $option), $data['options']);
        $data['body'] = ArticleContent::sanitize($data['body']);
        $data['explanation'] = ArticleContent::sanitize($data['explanation'] ?? '');

        return $data;
    }
}
