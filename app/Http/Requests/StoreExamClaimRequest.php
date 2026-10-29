<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'instagram_username' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/D'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'proofs' => ['required', 'array', 'list', 'min:1', 'max:5'],
            'proofs.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'terms_version' => ['required', 'string', 'size:64'],
            'accept_terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['instagram_username' => 'username Instagram', 'proofs' => 'bukti', 'proofs.*' => 'file bukti', 'accept_terms' => 'persetujuan syarat', 'customer_note' => 'catatan'];
    }
}
