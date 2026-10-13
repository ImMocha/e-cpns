<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'], 'section_id' => ['nullable', 'integer']]);

        return view('Dashboard.User.articles.index', [
            'articles' => Article::query()->visibleToMembers()->search($filters['q'] ?? null)
                ->when($filters['section_id'] ?? null, fn (Builder $query, string|int $section): Builder => $query->where('section_id', $section))
                ->with(['section', 'author'])->latest('published_at')->latest('id')->paginate(9)->withQueryString(),
            'sections' => Section::query()->active()->ordered()->get(),
        ]);
    }

    public function show(Request $request, Article $article): View
    {
        $user = $request->user();
        $isPreview = $user?->isAdmin() && $user->isActive() && $user->hasVerifiedEmail();
        abort_unless($isPreview || Article::query()->visibleToMembers()->whereKey($article->id)->exists(), 404);

        if ($request->isMethod('GET') && ! ($user?->isAdmin() ?? false)) {
            $readerIdentity = $user ? 'user:'.$user->getAuthIdentifier() : 'guest:'.$request->session()->getId();

            DB::table('article_views')->insert([
                'article_id' => $article->id,
                'reader_hash' => hash_hmac('sha256', $readerIdentity, (string) config('app.key')),
                'created_at' => now(),
            ]);
        }

        return view('Dashboard.User.articles.show', [
            'article' => $article->load(['section', 'author']),
            'isPreview' => $isPreview,
            'relatedArticles' => Article::query()->visibleToMembers()->whereKeyNot($article->id)
                ->with('section')->latest('published_at')->latest('id')->limit(3)
                ->get(['id', 'section_id', 'title', 'published_at']),
        ]);
    }
}
