<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:filter', 'max:160', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(UserRole::options())],
            'status' => ['required', Rule::in(UserStatus::options())],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:180'],
            'institution' => ['nullable', 'string', 'max:120'],
            'email_verified' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.min' => 'Nama minimal 3 karakter.',
            'email.unique' => 'Email ini sudah dipakai peserta lain.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, dan tanda + - ( ).',
            'role.in' => 'Role tidak dikenal.',
            'status.in' => 'Status tidak dikenal.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'password' => 'password',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'institution' => 'instansi',
        ];
    }

    /** @return array<string, mixed> */
    protected function prepareForValidation(): void
    {
        if (is_string($this->phone)) {
            $this->merge(['phone' => trim($this->phone) ?: null]);
        }

        $this->merge(['email_verified' => $this->boolean('email_verified')]);
    }
}
