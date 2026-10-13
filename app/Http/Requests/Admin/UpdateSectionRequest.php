<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ColorTone;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Section $section */
        $section = $this->route('section');

        return [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('sections', 'name')->ignore($section)],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('sections', 'slug')->ignore($section)],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:30',
                'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/',
                Rule::unique('sections', 'code')->ignore($section),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'icon' => ['nullable', Rule::in(Section::ICONS)],
            'tone' => ['nullable', Rule::in(ColorTone::options())],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Sudah ada bidang dengan nama tersebut.',
            'slug.unique' => 'Slug ini sudah dipakai bidang lain.',
            'code.unique' => 'Kode bidang sudah dipakai.',
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan tanda hubung. Contoh: TWK.',
            'icon.in' => 'Ikon tidak dikenal.',
            'tone.in' => 'Warna tidak dikenal.',
            'position.max' => 'Urutan tampil maksimal 999.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama bidang',
            'slug' => 'slug',
            'code' => 'kode bidang',
            'description' => 'deskripsi',
            'position' => 'urutan tampil',
            'icon' => 'ikon',
            'tone' => 'warna',
        ];
    }

    /** @return array<string, mixed> */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'code' => Str::upper(trim((string) $this->code)),
            'description' => trim((string) $this->description) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function sectionAttributes(): array
    {
        /** @var Section $section */
        $section = $this->route('section');
        $validated = $this->validated();

        return array_merge($validated, [
            'slug' => Section::resolveSlug($validated['slug'] ?? null, $validated['name'], $section->id),
            'position' => $validated['position'] ?? $section->position,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);
    }
}
