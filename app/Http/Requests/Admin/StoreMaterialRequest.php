<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Material;
use Illuminate\Validation\Rule;

/**
 * Tambah materi baru. Bedanya dari `UpdateMaterialRequest` hanya ada di aturan
 * `file`: saat create, berkas PDF boleh kosong selama statusnya Draft.
 */
class StoreMaterialRequest extends MaterialRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            'section_id' => [
                'required',
                'integer',
                // `deleted_at` ikut difilter: bidang yang sudah dihapus tidak
                // boleh dipilih, walau barisnya masih ada karena soft delete.
                Rule::exists('sections', 'id')->whereNull('deleted_at'),
            ],
            'title' => [
                'required',
                'string',
                'min:5',
                'max:180',
                // Judul unik per bidang, bukan global — dua bidang boleh punya
                // materi bernama sama tanpa saling mengganggu.
                Rule::unique('materials', 'title')->where(
                    fn ($query) => $query->where('section_id', $this->input('section_id'))
                ),
            ],
            'slug' => ['nullable', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:'.Material::MAX_PDF_KILOBYTES],
        ]);
    }
}
