<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MaterialAccess;
use App\Enums\MaterialType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Section;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends SliceController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        ExamAttempt::finalizeExpiredFor($user);
        $summary = ExamAttempt::summaryFor($user);
        $tryoutSummary = ExamAttempt::summaryFor($user, 'tryout');
        $trend = $tryoutSummary['trend'];

        $materialQuery = Material::query()->visibleToMembers()
            ->where('access_level', MaterialAccess::Free->value);
        $materialSections = Section::query()->active()->ordered()
            ->withCount([
                'materials as available_materials_count' => fn (Builder $query) => $query->visibleToMembers()
                    ->where('access_level', MaterialAccess::Free->value),
                'materials as completed_materials_count' => fn (Builder $query) => $query->visibleToMembers()
                    ->where('access_level', MaterialAccess::Free->value)
                    ->whereHas('progressRecords', fn (Builder $progress) => $progress->whereBelongsTo($user)->whereNotNull('completed_at')),
            ])->get()->filter(fn (Section $section): bool => $section->available_materials_count > 0)->values();
        $materialCount = $materialSections->sum('available_materials_count');
        $completedMaterialCount = $materialSections->sum('completed_materials_count');
        $resumeMaterials = MaterialProgress::query()->whereBelongsTo($user)->whereNull('completed_at')
            ->whereHas('material', fn (Builder $query) => $query->visibleToMembers()
                ->where('access_level', MaterialAccess::Free->value))
            ->with(['material.section', 'material.topic'])
            ->latest('last_viewed_at')->limit(3)->get()
            ->pluck('material');
        $resumeMaterialIds = array_fill_keys($resumeMaterials->pluck('id')->all(), true);
        $recentMaterials = $resumeMaterials->concat(
            (clone $materialQuery)->whereNotIn('id', array_keys($resumeMaterialIds))
                ->whereDoesntHave('progressRecords', fn (Builder $query) => $query->whereBelongsTo($user)->whereNotNull('completed_at'))
                ->latest('published_at')->latest('id')
                ->limit(3 - $resumeMaterials->count())->get(),
        );

        return view('Dashboard.User.index', [
            'user' => $user,
            'summary' => $summary,
            'tryoutSummary' => $tryoutSummary,
            'tryoutChart' => count($trend) > 0 ? [
                'type' => 'line',
                'labels' => array_map(fn (int $index): string => 'S'.($index + 1), array_keys($trend)),
                'values' => array_map(fn (ExamAttempt $attempt): int => $attempt->score, $trend),
                'avg' => $tryoutSummary['averageScore'],
                'height' => 240,
                'aria' => 'Grafik perkembangan nilai tryout',
            ] : null,
            'materialCount' => $materialCount,
            'completedMaterialCount' => $completedMaterialCount,
            'materialProgressPercent' => $materialCount > 0 ? (int) round($completedMaterialCount / $materialCount * 100) : 0,
            'completedVideoCount' => MaterialProgress::query()->whereBelongsTo($user)->whereNotNull('completed_at')
                ->whereHas('material', fn (Builder $query) => $query->visibleToMembers()
                    ->where('access_level', MaterialAccess::Free->value)
                    ->where('type', MaterialType::Video->value))->count(),
            'materialSections' => $materialSections,
            'recentMaterials' => $recentMaterials,
            'resumeMaterialIds' => $resumeMaterialIds,
            'openAttempts' => ExamAttempt::query()->whereBelongsTo($user)
                ->where('status', ExamAttempt::IN_PROGRESS)->latest('id')->limit(2)->get(),
            'featuredTryout' => Exam::query()->published()->where('kind', 'tryout')->latest('id')->first(),
        ]);
    }
}
