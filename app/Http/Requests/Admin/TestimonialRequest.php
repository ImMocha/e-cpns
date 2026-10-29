<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\StoreTestimonialRequest;

class TestimonialRequest extends StoreTestimonialRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true && $this->user()->isActive();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [...parent::rules(), 'is_visible' => ['required', 'boolean']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'is_visible.required' => 'Status visibilitas wajib dipilih.',
            'is_visible.boolean' => 'Status visibilitas tidak valid.',
        ];
    }
}
