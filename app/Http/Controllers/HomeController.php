<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\ExamCatalog;
use App\Leaderboard;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Leaderboard $leaderboard, Request $request, ExamCatalog $catalog): View
    {
        return view('LandingPage.index', [
            'metaDescription' => 'Platform belajar dan simulasi tryout CAT CPNS: materi PDF, video pembelajaran, latihan soal, pembahasan, statistik nilai, dan leaderboard.',
            'participantCount' => User::query()->active()->role(UserRole::User)->whereNotNull('email_verified_at')->count(),
            'questionCount' => Question::query()->visibleToMembers()->count(),
            'tryoutCount' => Exam::query()->visibleToMembers()->where('kind', 'tryout')->count(),
            'materialCount' => Material::query()->visibleToMembers()->count(),
            'featuredExams' => $catalog->featured($request->user()),
            'referenceTryout' => $leaderboard->referenceTryout(),
            'testimonials' => Testimonial::query()->visible()->latest('id')->get(),
            'canWriteTestimonial' => $request->user()?->canWriteTestimonial() === true,
            'latestArticles' => Article::query()->visibleToMembers()->with('section')
                ->latest('published_at')->latest('id')->limit(3)->get(),
            'packages' => Package::query()->active()->withContentAvailability()->with([
                'materials' => fn ($query) => $query->visibleToMembers(),
                'exams' => fn ($query) => $query->visibleToMembers(),
            ])->orderBy('position')->orderBy('id')->get(),
        ]);
    }
}
