<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\TypographySettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypographyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(TypographySettings $typography): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'heading_font' => ['required', 'string', Rule::in(array_keys($typography->fonts()))],
            'body_font' => ['required', 'string', Rule::in(array_keys($typography->fonts()))],
            'landing' => ['required', 'boolean'],
            'member' => ['required', 'boolean'],
            'admin' => ['required', 'boolean'],
            'current_password' => ['required', 'string', 'current_password:web'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'heading_font.in' => 'Pilih font judul dari daftar yang tersedia.',
            'body_font.in' => 'Pilih font teks dari daftar yang tersedia.',
            'current_password.required' => 'Masukkan password akun admin untuk mengonfirmasi perubahan font.',
            'current_password.current_password' => 'Password akun admin tidak cocok.',
        ];
    }
}
