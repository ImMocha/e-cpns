<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true && $this->user()->isActive();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['is_visible' => ['required', 'boolean']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'is_visible.required' => 'Status visibilitas wajib dipilih.',
            'is_visible.boolean' => 'Status visibilitas tidak valid.',
        ];
    }
}
