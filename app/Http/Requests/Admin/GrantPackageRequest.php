<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $this->merge(['email' => is_string($email) ? mb_strtolower(trim($email)) : $email]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::exists('users', 'email')->whereNull('deleted_at')
                ->where('role', UserRole::User->value)->where('status', UserStatus::Active->value)],
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['email.exists' => 'Email harus milik peserta aktif yang terdaftar.', 'note.required' => 'Tuliskan alasan pemberian paket untuk catatan admin.'];
    }
}
