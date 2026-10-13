<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ColorTone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $filters = ['q' => trim((string) $request->query('q', ''))];

        $sections = Section::query()
            ->search($filters['q'])
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('Dashboard.Admin.sections.index', [
            'sections' => $sections,
            'filters' => $filters,
            'stats' => $this->stats(),
        ]);
    }

    public function create(): View
    {
        return view('Dashboard.Admin.sections.create', [
            'section' => new Section(['is_active' => true, 'position' => Section::max('position') + 1]),
            'icons' => Section::iconOptions(),
            'tones' => ColorTone::cases(),
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $section = Section::create(
            array_merge($request->sectionAttributes(), ['created_by' => $request->user()?->id])
        );

        return redirect()
            ->route('admin.sections.index')
            ->with('success', "Bidang {$section->name} berhasil dibuat.");
    }

    public function show(Section $section): RedirectResponse
    {
        return redirect()->route('admin.sections.edit', $section);
    }

    public function edit(Section $section): View
    {
        return view('Dashboard.Admin.sections.edit', [
            'section' => $section,
            'icons' => Section::iconOptions(),
            'tones' => ColorTone::cases(),
        ]);
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update(array_merge(
            $request->sectionAttributes(),
            ['updated_by' => $request->user()?->id],
        ));

        return redirect()
            ->route('admin.sections.index')
            ->with('success', "Perubahan pada bidang {$section->name} disimpan.");
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return redirect()
            ->route('admin.sections.index')
            ->with('success', "Bidang {$section->name} dihapus.");
    }

    /** @return array{total: int, active: int, inactive: int} */
    private function stats(): array
    {
        return [
            'total' => Section::count(),
            'active' => Section::active()->count(),
            'inactive' => Section::query()->where('is_active', false)->count(),
        ];
    }
}
