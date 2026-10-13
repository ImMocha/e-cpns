<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MaterialStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\SliceController;
use App\Models\Article;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Question;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends SliceController
{
    public function __invoke(): View
    {
        $periodStart = now()->startOfMonth()->subMonths(5);
        $monthlyCounts = [];

        for ($month = 0; $month < 6; $month++) {
            $date = $periodStart->copy()->addMonths($month);
            $monthlyCounts[$date->format('Y-m')] = ['label' => $date->translatedFormat('M y'), 'count' => 0];
        }

        foreach (ExamAttempt::query()->completed()->where('submitted_at', '>=', $periodStart)
            ->orderBy('submitted_at')->cursor() as $attempt) {
            $month = $attempt->submitted_at->format('Y-m');

            if (isset($monthlyCounts[$month])) {
                $monthlyCounts[$month]['count']++;
            }
        }

        $participants = ExamAttempt::query()->completed()->select('bank_slug')
            ->selectRaw('COUNT(DISTINCT user_id) AS participants')->groupBy('bank_slug')->pluck('participants', 'bank_slug');
        $banks = Exam::query()->get()->map(function (Exam $bank) use ($participants): array {
            return [
                'title' => $bank->title,
                'kind' => $bank->kind,
                'questions' => $bank->question_count,
                'participants' => (int) $participants->get($bank->slug, 0),
            ];
        });

        $participantCount = User::role(UserRole::User)->count();
        $activeParticipantCount = User::role(UserRole::User)->where('status', UserStatus::Active->value)->count();
        $inactiveParticipantCount = User::role(UserRole::User)->where('status', UserStatus::Inactive->value)->count();
        $articleActivity = DB::table('article_views')->join('articles', 'articles.id', '=', 'article_views.article_id')
            ->whereNull('articles.deleted_at');
        $articleTotals = (clone $articleActivity)
            ->selectRaw('COUNT(*) AS views_count, COUNT(DISTINCT reader_hash) AS readers_count')->first();
        $mostReadArticles = (clone $articleActivity)->select('articles.id', 'articles.title')
            ->selectRaw('COUNT(*) AS views_count')->groupBy('articles.id', 'articles.title')
            ->orderByDesc('views_count')->limit(3)->get();

        return view('Dashboard.Admin.index', [
            'participantCount' => $participantCount,
            'activeParticipantCount' => $activeParticipantCount,
            'inactiveParticipantCount' => $inactiveParticipantCount,
            'participantStatusChart' => [
                'type' => 'donut',
                'size' => 190,
                'segments' => [
                    ['value' => $activeParticipantCount, 'color' => '#0191ED'],
                    ['value' => $inactiveParticipantCount, 'color' => '#FFC53D'],
                ],
                'aria' => 'Status akun peserta',
            ],
            'questionCount' => Question::count(),
            'publishedQuestionCount' => Question::where('status', 'published')->count(),
            'draftQuestionCount' => Question::where('status', 'draft')->count(),
            'bankCount' => $banks->count(),
            'completedTryoutCount' => ExamAttempt::query()->completed()->where('kind', 'tryout')->count(),
            'completedQuizCount' => ExamAttempt::query()->completed()->where('kind', 'quiz')->count(),
            'publishedMaterialCount' => Material::query()->where('status', MaterialStatus::Published->value)->count(),
            'draftMaterialCount' => Material::query()->where('status', MaterialStatus::Draft->value)->count(),
            'publishedArticleCount' => Article::query()->visibleToMembers()->count(),
            'articleViewCount' => (int) $articleTotals->views_count,
            'articleReaderCount' => (int) $articleTotals->readers_count,
            'mostReadArticles' => $mostReadArticles,
            'monthlyChart' => [
                'type' => 'bar',
                'labels' => array_column($monthlyCounts, 'label'),
                'values' => array_column($monthlyCounts, 'count'),
                'max' => 4 * (int) ceil(max(1, ...array_column($monthlyCounts, 'count')) / 4),
                'height' => 250,
                'aria' => 'Jumlah sesi quiz dan tryout selesai dalam enam bulan terakhir',
            ],
            'bankActivity' => $banks->sortByDesc('participants')->values(),
            'recentAttempts' => ExamAttempt::query()->with('user')->completed()
                ->latest('submitted_at')->latest('id')->limit(6)->get(),
            'recentMaterials' => Material::query()->with('section')->latest('id')->limit(4)->get(),
        ]);
    }
}
