<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\IntegrationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(IntegrationSettings $settings): array
    {
        $rules = ['current_password' => ['required', 'string', 'current_password:web']];
        if ($this->isMethod('post')) {
            if ($this->route('integration') === 'midtrans') {
                $rules['is_production'] = ['required', 'boolean'];
            }

            return $rules;
        }
        $rules += ['version' => ['required', 'integer', 'min:0'], 'enabled' => ['required', 'boolean']];

        if ($this->route('integration') === 'midtrans') {
            $rules += ['is_production' => ['required', 'boolean']];
            foreach (['sandbox_server_key', 'production_server_key'] as $field) {
                $rules[$field] = ['nullable', 'string', 'max:255', 'regex:/^[\x21-\x7E]+$/D', 'prohibited_if:remove_'.$field.',1'];
                $rules['remove_'.$field] = ['sometimes', 'boolean'];
            }
        } else {
            $rules += [
                'smtp_host' => ['nullable', 'required_if:enabled,1', 'string', Rule::in($settings->smtpHosts())],
                'smtp_port' => ['nullable', 'required_if:enabled,1', 'integer', Rule::in([465, 587])],
                'smtp_username' => ['nullable', 'string', 'max:255', 'not_regex:/[\\x00-\\x1F\\x7F]/'],
                'smtp_password' => ['nullable', 'string', 'max:1024', 'not_regex:/[\\x00-\\x1F\\x7F]/', 'prohibited_if:remove_smtp_password,1'],
                'remove_smtp_password' => ['sometimes', 'boolean'],
                'from_address' => ['nullable', 'required_if:enabled,1', 'email:filter', 'max:255'],
                'from_name' => ['nullable', 'required_if:enabled,1', 'string', 'max:160', 'not_regex:/[\\x00-\\x1F\\x7F]/'],
            ];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Masukkan password akun admin untuk mengonfirmasi tindakan ini.',
            'current_password.current_password' => 'Password akun admin tidak cocok.',
            'smtp_host.in' => 'Pilih server SMTP yang diizinkan.',
            'smtp_port.in' => 'Gunakan port 465 (TLS) atau 587 (STARTTLS).',
            'sandbox_server_key.regex' => 'Format server key tidak valid.',
            'production_server_key.regex' => 'Format server key tidak valid.',
            '*.prohibited_if' => 'Pilih mengganti atau menghapus kredensial, jangan keduanya.',
        ];
    }
}
