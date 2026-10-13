<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\ArticleContent;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
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
            'options.*' => ['required', 'string', 'max:2000', 'distinct:strict'],
            'correct_option' => ['required', 'integer', 'min:0', 'max:9'],
            'explanation' => ['nullable', 'string', 'max:50000'],
            'difficulty' => ['required', Rule::enum(QuestionDifficulty::class)],
            'status' => ['required', Rule::enum(QuestionStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'extensions:mp4,webm', 'max:51200'],
            'remove_video' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('body') && ArticleContent::text(ArticleContent::sanitize($this->input('body'))) === '') {
                $validator->errors()->add('body', 'Pertanyaan harus berisi teks yang dapat dibaca.');
            }
            if (! $validator->errors()->hasAny(['options', 'correct_option']) && is_array($this->input('options')) && ! array_key_exists($this->integer('correct_option'), $this->input('options'))) {
                $validator->errors()->add('correct_option', 'Pilih kunci dari pilihan jawaban yang tersedia.');
            }
            if (! $validator->errors()->has('explanation') && $this->input('status') === 'published' && ArticleContent::text(ArticleContent::sanitize($this->input('explanation'))) === '') {
                $validator->errors()->add('explanation', 'Lengkapi pembahasan sebelum menerbitkan soal.');
            }
        }];
    }

    public function messages(): array
    {
        return ['options.min' => 'Soal membutuhkan minimal 2 pilihan jawaban.', 'options.max' => 'Soal dapat memiliki maksimal 10 pilihan jawaban.',
            'options.list' => 'Urutan pilihan jawaban tidak valid.', 'options.*.required' => 'Setiap pilihan jawaban harus diisi.',
            'options.*.distinct' => 'Pilihan jawaban tidak boleh sama.', 'correct_option.required' => 'Pilih satu jawaban yang benar.',
            'video.mimetypes' => 'Video soal harus berupa file MP4 atau WebM.',
            'video.extensions' => 'Video soal harus menggunakan ekstensi .mp4 atau .webm.',
            'video.max' => 'Ukuran video soal maksimal 50 MB.'];
    }

    public function attributes(): array
    {
        return ['title' => 'judul soal', 'section_id' => 'bidang', 'body' => 'pertanyaan', 'difficulty' => 'tingkat kesulitan',
            'status' => 'status', 'explanation' => 'pembahasan', 'image' => 'gambar soal', 'video' => 'video soal', 'correct_option' => 'kunci jawaban'];
    }

    /** @return array<string, mixed> */
    public function questionAttributes(): array
    {
        $data = $this->safe()->only(['title', 'section_id', 'body', 'options', 'correct_option', 'explanation', 'difficulty', 'status']);
        $data['title'] = trim($data['title']);
        $data['body'] = ArticleContent::sanitize($data['body']);
        $data['explanation'] = ArticleContent::sanitize($data['explanation'] ?? '');

        return $data;
    }
}
