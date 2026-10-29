<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', 'in:draft,published'], 'section_id' => ['nullable', 'integer']]);
        $query = Article::query()->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['section_id'] ?? null, fn (Builder $query, string|int $section): Builder => $query->where('section_id', $section));
        $articles = $query->with(['section', 'author'])->latest('updated_at')->latest('id')->paginate(10)->withQueryString();
        $articleViews = DB::table('article_views')->whereIn('article_id', $articles->getCollection()->pluck('id'))
            ->select('article_id')->selectRaw('COUNT(*) AS views_count, COUNT(DISTINCT reader_hash) AS readers_count')
            ->groupBy('article_id')->get()->keyBy('article_id');
        $viewTotals = DB::table('article_views')->join('articles', 'articles.id', '=', 'article_views.article_id')
            ->whereNull('articles.deleted_at')
            ->selectRaw('COUNT(*) AS views_count, COUNT(DISTINCT reader_hash) AS readers_count')->first();

        return view('Dashboard.Admin.articles.index', [
            'articles' => $articles,
            'articleViews' => $articleViews,
            'sections' => Section::query()->ordered()->get(),
            'stats' => [
                'total' => Article::count(),
                'published' => Article::where('status', ArticleStatus::Published)->count(),
                'draft' => Article::where('status', ArticleStatus::Draft)->count(),
                'readers' => (int) $viewTotals->readers_count,
                'views' => (int) $viewTotals->views_count,
            ],
        ]);
    }

    public function create(): View
    {
        return $this->form(new Article(['status' => ArticleStatus::Draft, 'body' => '']));
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $article = new Article;
        $this->persist($request, $article);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Artikel berhasil disimpan.');
    }

    public function show(Article $article): RedirectResponse
    {
        return redirect()->route('articles.show', $article);
    }

    public function edit(Article $article): View
    {
        return $this->form($article);
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->persist($request, $article);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Perubahan artikel berhasil disimpan.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dihapus.');
    }

    private function form(Article $article): View
    {
        if ($article->exists) {
            $article->load('author');
        }

        return view('Dashboard.Admin.articles.form', ['article' => $article, 'sections' => Section::query()->ordered()->get()]);
    }

    private function persist(ArticleRequest $request, Article $article): void
    {
        $oldPath = $article->cover_path;
        $newPath = $request->file('cover')?->store('articles/covers', 'public');
        abort_if($newPath === false, 500, 'Gambar cover gagal disimpan. Silakan coba lagi.');
        $attributes = $request->articleAttributes();
        $attributes['cover_path'] = $newPath ?: ($request->boolean('remove_cover') ? null : $oldPath);
        if (! $attributes['cover_path']) {
            $attributes['cover_source'] = null;
            $attributes['cover_source_url'] = null;
        }
        $publishedAt = $request->date('publish_at', 'Y-m-d\TH:i', 'Asia/Makassar')?->setTimezone(config('app.timezone'));
        $attributes['published_at'] = $attributes['status'] === ArticleStatus::Published->value
            ? ($publishedAt ?? ($article->published_at?->isPast() ? $article->published_at : now()))
            : null;
        $attributes['updated_by'] = $request->user()->id;
        if (! $article->exists) {
            $attributes['created_by'] = $request->user()->id;
        }

        try {
            $article->fill($attributes)->save();
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath && $oldPath !== $article->cover_path) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
