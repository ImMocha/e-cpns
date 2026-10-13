<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\ArticleContent;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->whereNull('deleted_at')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('body') && $this->input('status') === ArticleStatus::Published->value && ArticleContent::text(ArticleContent::sanitize($this->input('body'))) === '') {
                $validator->errors()->add('body', 'Isi artikel tidak boleh kosong saat diterbitkan.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['title' => 'judul', 'slug' => 'slug', 'section_id' => 'bidang', 'excerpt' => 'ringkasan', 'body' => 'isi artikel', 'cover' => 'gambar cover', 'status' => 'status'];
    }

    /** @return array{title: string, slug: string, section_id: int|string, excerpt: ?string, body: string, status: string} */
    public function articleAttributes(): array
    {
        $data = $this->safe()->only(['title', 'slug', 'section_id', 'excerpt', 'body', 'status']);
        $article = $this->route('article');
        $data['title'] = trim($data['title']);
        $data['slug'] = Article::resolveSlug($data['slug'] ?? null, $data['title'], $article instanceof Article ? $article->id : null);
        $data['body'] = ArticleContent::sanitize($data['body'] ?? '');
        $data['excerpt'] = trim($data['excerpt'] ?? '') ?: null;

        return $data;
    }
}
