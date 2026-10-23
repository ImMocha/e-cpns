<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/D', Rule::unique('vouchers')->ignore($this->route('voucher'))],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => ['required', 'integer', 'min:1', $this->input('type') === 'percentage' ? 'max:100' : 'max:4294967295'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('starts_on'), 'after_or_equal:starts_on')],
            'minimum_amount' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'package_id' => ['nullable', 'integer', Rule::exists('packages', 'id')->whereNull('deleted_at')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => is_string($this->input('code')) ? Str::upper(trim($this->input('code'))) : $this->input('code'),
            'is_active' => $this->boolean('is_active')]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['code.unique' => 'Kode voucher sudah digunakan.', 'code.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'value.max' => 'Nilai persentase maksimal 100%; nominal maksimal Rp4.294.967.295.',
            'ends_on.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.'];
    }
}
