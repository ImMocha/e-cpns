<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LeaderboardAccess;
use App\Models\Exam;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['material_ids' => $this->input('material_ids', []), 'exam_ids' => $this->input('exam_ids', [])]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'leaderboard_access' => ['sometimes', 'required', Rule::enum(LeaderboardAccess::class)],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'tryout_quota' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'material_ids' => ['present', 'array', 'list', 'max:2000'],
            'material_ids.*' => ['required', 'integer', 'distinct', Rule::exists('materials', 'id')->whereNull('deleted_at')],
            'exam_ids' => ['present', 'array', 'list', 'max:2000'],
            'exam_ids.*' => ['required', 'integer', 'distinct', Rule::exists('exams', 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $materials = $this->input('material_ids', []);
            $exams = $this->input('exam_ids', []);
            if ($this->boolean('is_active')) {
                if ($materials === [] && $exams === []) {
                    $validator->errors()->add('material_ids', 'Pilih setidaknya satu materi atau sesi sebelum mengaktifkan paket.');
                }
                if (Material::query()->visibleToMembers()->whereKey($materials)->count() !== count($materials)) {
                    $validator->errors()->add('material_ids', 'Paket aktif hanya boleh berisi materi terbit pada bidang dan topik aktif.');
                }
                if (Exam::query()->published()->whereKey($exams)->count() !== count($exams)) {
                    $validator->errors()->add('exam_ids', 'Paket aktif hanya boleh berisi sesi terbit.');
                }
            }
            if ($this->filled('tryout_quota') && $this->integer('tryout_quota') === 0
                && Exam::query()->whereKey($exams)->where('kind', 'tryout')->exists()) {
                $validator->errors()->add('tryout_quota', 'Paket berisi tryout perlu kuota minimal 1 atau tanpa batas.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['name' => 'nama paket', 'price' => 'harga', 'duration_days' => 'masa aktif',
            'leaderboard_access' => 'akses leaderboard',
            'tryout_quota' => 'kuota tryout', 'material_ids' => 'materi paket', 'exam_ids' => 'sesi paket'];
    }
}
