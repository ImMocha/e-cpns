<?php

declare(strict_types=1);

namespace App\Models;

use App\ArticleContent;
use App\Enums\ArticleStatus;
use App\UniqueSlug;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['section_id', 'title', 'slug', 'excerpt', 'body', 'cover_path', 'cover_source', 'cover_source_url', 'status', 'published_at', 'created_by', 'updated_by'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            if ($article->isDirty('body')) {
                $article->body = ArticleContent::sanitize($article->body);
            }
        });
    }

    protected function casts(): array
    {
        return ['status' => ArticleStatus::class, 'published_at' => 'datetime'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<Article> $query */
    public function scopeVisibleToMembers(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('section', fn (Builder $section) => $section->active());
    }

    /** @param Builder<Article> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (trim((string) $term) !== '') {
            $query->where(fn (Builder $search) => $search->where('title', 'like', '%'.$term.'%')->orWhere('excerpt', 'like', '%'.$term.'%'));
        }
    }

    public static function resolveSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        return UniqueSlug::resolve('articles', trim((string) $slug) ?: $title, $ignoreId);
    }

    public function readingMinutes(): int
    {
        $text = ArticleContent::text($this->body);
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / 200));
    }

    public function summary(): string
    {
        return $this->excerpt ?: Str::limit(ArticleContent::text($this->body), 180);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function safeBody(): string
    {
        return ArticleContent::sanitize($this->body);
    }
}
