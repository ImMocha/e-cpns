<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Package;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('LandingPage.index', [
            'metaDescription' => 'Platform belajar dan simulasi tryout CAT CPNS: materi PDF, video pembelajaran, bank soal, latihan, pembahasan, statistik nilai, dan leaderboard.',
            'latestArticles' => Article::query()->visibleToMembers()->with('section')
                ->latest('published_at')->latest('id')->limit(3)->get(),
            'packages' => Package::query()->active()->with([
                'materials' => fn ($query) => $query->visibleToMembers(),
                'exams' => fn ($query) => $query->visibleToMembers(),
            ])->orderBy('position')->orderBy('id')->get(),
        ]);
    }
}
