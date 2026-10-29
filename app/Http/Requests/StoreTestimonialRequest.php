<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWriteTestimonial() === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:180'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 120 karakter.',
            'institution.string' => 'Instansi harus berupa teks.',
            'institution.max' => 'Instansi maksimal 180 karakter.',
            'content.required' => 'Testimoni wajib diisi.',
            'content.string' => 'Testimoni harus berupa teks.',
            'content.min' => 'Testimoni minimal 10 karakter.',
            'content.max' => 'Testimoni maksimal 2000 karakter.',
        ];
    }
}
