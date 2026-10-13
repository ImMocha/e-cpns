<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Material;
use Illuminate\Validation\Rule;

/**
 * Ubah materi yang sudah ada.
 *
 * Bedanya dari `StoreMaterialRequest`:
 *
 * - keunikan nama & slug mengabaikan materi itu sendiri;
 * - `hasSource()` menghitung berkas lama, jadi materi PDF yang sudah punya
 *   berkas tetap bisa disimpan tanpa mengunggah ulang.
 */
class UpdateMaterialRequest extends MaterialRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var Material $material */
        $material = $this->route('material');

        return array_merge($this->sharedRules(), [
            'section_id' => [
                'required',
                'integer',
                Rule::exists('sections', 'id')->whereNull('deleted_at'),
            ],
            'title' => [
                'required',
                'string',
                'min:5',
                'max:180',
                Rule::unique('materials', 'title')
                    ->where(fn ($query) => $query->where('section_id', $this->input('section_id')))
                    ->ignore($material),
            ],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('materials', 'slug')->ignore($material)],
            'summary' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:'.Material::MAX_PDF_KILOBYTES],
        ]);
    }

    protected function hasSource(bool $isVideo): bool
    {
        if ($isVideo) {
            return $this->filled('video_url');
        }

        /** @var Material $material */
        $material = $this->route('material');

        return $this->hasFile('file') || $material->file_path !== null;
    }

    protected function materialId(): ?int
    {
        /** @var Material $material */
        $material = $this->route('material');

        return $material->id;
    }
}
