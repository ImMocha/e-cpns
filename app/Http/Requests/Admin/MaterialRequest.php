<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Material;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aturan bersama untuk simpan materi PDF / video.
 *
 * Dua hal dijaga di sini:
 *
 * 1. **Sumber mengikuti format.** URL video tidak ada artinya untuk PDF, dan
 *    berkas PDF tidak ada artinya untuk video. Masing-masing dinormalisasi
 *    menjadi null supaya tidak ada kolom setengah terisi.
 * 2. **Terbit berarti punya sumber.** Materi `published` tanpa PDF maupun URL
 *    video akan tampil di daftar tapi tidak bisa dibuka peserta — itu ditolak
 *    di server, bukan diserahkan ke peserta saat mereka mengeluh.
 *
 * Subclass hanya menambahkan aturan yang benar-benar berbeda: berkas `file`
 * dan pengabaian keunikan saat update.
 */
abstract class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    protected function sharedRules(): array
    {
        return [
            'topic_id' => ['required', 'integer', Rule::exists('topics', 'id')
                ->where('section_id', $this->input('section_id'))->whereNull('deleted_at')],
            'type' => ['required', Rule::in(MaterialType::options())],
            'access_level' => ['required', Rule::in([MaterialAccess::Premium->value])],
            'status' => ['required', Rule::in(MaterialStatus::options())],
            'video_url' => ['nullable', 'url', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'page_count' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'section_id.exists' => 'Bidang tidak ditemukan. Mungkin bidang tersebut sudah dihapus.',
            'topic_id.exists' => 'Pilih topik yang berada di bidang tersebut dan belum dihapus.',
            'title.unique' => 'Sudah ada materi dengan judul tersebut di bidang ini.',
            'slug.unique' => 'Slug ini sudah dipakai materi lain.',
            'type.in' => 'Format materi tidak dikenal.',
            'access_level.in' => 'Semua materi menggunakan akses Premium.',
            'status.in' => 'Status tidak dikenal.',
            'file.mimes' => 'Berkas harus berformat PDF.',
            'file.max' => 'Ukuran PDF maksimal 20 MB.',
            'video_url.url' => 'URL video harus berupa tautan yang valid.',
            'duration_minutes.max' => 'Durasi video maksimal 600 menit.',
            'page_count.max' => 'Jumlah halaman maksimal 2.000.',
            'position.max' => 'Urutan tampil maksimal 999.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'section_id' => 'bidang',
            'topic_id' => 'topik',
            'title' => 'judul materi',
            'slug' => 'slug',
            'summary' => 'ringkasan',
            'type' => 'format materi',
            'access_level' => 'level akses',
            'status' => 'status',
            'file' => 'berkas PDF',
            'video_url' => 'URL video',
            'duration_minutes' => 'durasi video',
            'page_count' => 'jumlah halaman',
            'position' => 'urutan tampil',
        ];
    }

    /**
     * Bersihkan input sebelum validasi.
     *
     * Normalisasi kolom sumber dilakukan di sini, bukan di `after()`: kalau
     * dibuang setelah validasi, nilainya sudah terlanjur dipakai.
     */
    protected function prepareForValidation(): void
    {
        $isVideo = (string) $this->input('type') === MaterialType::Video->value;

        $this->merge([
            'access_level' => $this->input('access_level', MaterialAccess::Premium->value),
            'title' => trim((string) $this->title),
            'summary' => trim((string) $this->summary) ?: null,
            'video_url' => $isVideo ? (trim((string) $this->video_url) ?: null) : null,
            'duration_minutes' => $isVideo ? $this->input('duration_minutes') : null,
            'page_count' => $isVideo ? null : $this->input('page_count'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isVideo = (string) $this->input('type') === MaterialType::Video->value;

            if ($isVideo && $this->hasFile('file')) {
                $validator->errors()->add(
                    'file',
                    'Format video tidak menyimpan berkas PDF. Kosongkan berkas atau ganti formatnya.',
                );
            }

            if ($this->input('status') === MaterialStatus::Published->value && ! $this->hasSource($isVideo)) {
                $validator->errors()->add(
                    $isVideo ? 'video_url' : 'file',
                    $isVideo
                        ? 'Materi yang berstatus Terbit harus punya URL video.'
                        : 'Materi yang berstatus Terbit harus punya berkas PDF.',
                );
            }
        });
    }

    /**
     * Apakah materi punya sumber yang bisa dibuka.
     *
     * Subclass update meng-override ini supaya berkas lama tetap dihitung —
     * admin tidak wajib mengunggah ulang setiap kali menyimpan.
     */
    protected function hasSource(bool $isVideo): bool
    {
        return $isVideo
            ? $this->filled('video_url')
            : $this->hasFile('file');
    }

    /**
     * Data siap simpan, sudah dinormalisasi mengikuti format.
     *
     * @return array<string, mixed>
     */
    public function materialAttributes(): array
    {
        $validated = $this->validated();
        $title = (string) $validated['title'];

        return array_merge($validated, [
            'title' => $title,
            'slug' => Material::resolveSlug($validated['slug'] ?? null, $title, $this->materialId()),
            'position' => $validated['position'] ?? 0,
        ]);
    }

    /** ID materi saat update; null saat create. */
    protected function materialId(): ?int
    {
        return null;
    }
}
