<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:120', Rule::unique('topics', 'name')
                ->where('section_id', $this->input('section_id'))->ignore($this->route('topic'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama topik sudah digunakan di bidang ini.',
            'section_id.exists' => 'Bidang tidak ditemukan atau sudah dihapus.',
        ];
    }
}
