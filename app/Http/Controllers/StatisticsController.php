<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatisticsController extends SliceController
{
    public function __invoke(Request $request): View
    {
        $kind = in_array($request->query('kind'), ['tryout', 'quiz'], true)
            ? $request->query('kind') : 'tryout';
        $period = in_array($request->query('period'), ['30', '90', 'all'], true)
            ? $request->query('period') : 'all';
        $since = $period === 'all' ? null : now()->subDays((int) $period);
        $user = $request->user();
        ExamAttempt::finalizeExpiredFor($user);
        $summary = ExamAttempt::summaryFor($user, $kind, $since);
        $trend = $summary['trend'];
        $sections = array_values($summary['sections']);

        return view('Dashboard.User.statistics.index', [
            'summary' => $summary,
            'kind' => $kind,
            'period' => $period,
            'trendChart' => count($trend) > 0 ? [
                'type' => 'line',
                'labels' => array_map(fn (int $index): string => 'S'.($index + 1), array_keys($trend)),
                'values' => array_map(fn (ExamAttempt $attempt): int => $attempt->score, $trend),
                'avg' => $summary['averageScore'],
                'height' => 300,
                'aria' => 'Grafik nilai '.($kind === 'quiz' ? 'latihan' : 'tryout'),
            ] : null,
            'sectionChart' => count($sections) > 0 ? [
                'type' => 'bar',
                'labels' => array_column($sections, 'section'),
                'values' => array_column($sections, 'score'),
                'max' => max(1, max(array_column($sections, 'max'))),
                'height' => 250,
                'aria' => 'Skor poin per bagian',
            ] : null,
            'compositionChart' => $summary['count'] > 0 ? [
                'type' => 'donut', 'size' => 210,
                'segments' => [
                    ['value' => $summary['correct'], 'color' => '#2DD4A7'],
                    ['value' => $summary['wrong'], 'color' => '#FF7A5C'],
                    ['value' => $summary['weighted'], 'color' => '#0191ED'],
                    ['value' => $summary['blank'], 'color' => '#B9CFE0'],
                ],
                'aria' => 'Komposisi jawaban',
            ] : null,
        ]);
    }
}
