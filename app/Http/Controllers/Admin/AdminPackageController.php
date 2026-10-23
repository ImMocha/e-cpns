<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageRequest;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPackageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', 'in:active,inactive,archived']]);

        return view('Dashboard.Admin.packages.index', [
            'packages' => Package::query()
                ->withContentAvailability()
                ->when(($filters['status'] ?? null) === 'archived', fn ($query) => $query->onlyTrashed())
                ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
                ->when(in_array($filters['status'] ?? null, ['active', 'inactive'], true),
                    fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
                ->withCount(['materials', 'exams', 'userPackages as active_members_count' => fn ($query) => $query->active()])
                ->orderBy('position')->latest('id')->paginate(12)->withQueryString(),
            'filters' => $filters,
            'activeCount' => Package::query()->active()->count(),
            'memberCount' => UserPackage::query()->active()->distinct()->count('user_id'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Package(['is_active' => false, 'position' => 0]));
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $package = new Package;
        $this->persist($request, $package);

        return redirect()->route('admin.packages.edit', $package)->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(Package $package): View
    {
        return $this->form($package);
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $this->persist($request, $package);

        return redirect()->route('admin.packages.edit', $package)->with('success', 'Paket diperbarui. Hak peserta yang sudah aktif tetap mengikuti ketentuan saat aktivasi.');
    }

    public function deactivate(Request $request, Package $package): RedirectResponse
    {
        DB::transaction(function () use ($request, $package): void {
            Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail()->update([
                'is_active' => false,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Paket dinonaktifkan. Pembelian dan aktivasi baru dihentikan. Akses peserta yang sudah aktif tetap berlaku.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        DB::transaction(function () use ($package): void {
            Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail()->delete();
        });

        return redirect()->route('admin.packages.index')->with('success', 'Paket diarsipkan dari katalog. Akses peserta yang sudah aktif tetap berlaku.');
    }

    private function form(Package $package): View
    {
        $materials = Material::query()->with(['section', 'topic.section'])->ordered()->get();

        return view('Dashboard.Admin.packages.form', [
            'package' => $package,
            'contentAvailable' => $package->exists && $package->hasAvailableContent(),
            'materials' => $materials,
            'materialTopics' => $materials->pluck('topic')->filter()->unique('id')->sortBy('name'),
            'availableMaterialIds' => array_fill_keys(Material::query()->visibleToMembers()->pluck('materials.id')->all(), true),
            'availableExamIds' => array_fill_keys(Exam::query()->published()->pluck('exams.id')->all(), true),
            'exams' => Exam::query()->orderBy('kind')->orderBy('title')->get(),
            'selectedMaterials' => $package->exists ? $package->materials()->pluck('materials.id')->all() : [],
            'selectedExams' => $package->exists ? $package->exams()->pluck('exams.id')->all() : [],
            'grants' => $package->exists ? $package->userPackages()->with('user')->latest('id')->paginate(10)->withQueryString() : null,
        ]);
    }

    private function persist(PackageRequest $request, Package $package): void
    {
        DB::transaction(function () use ($request, $package): void {
            if ($package->exists) {
                $package = Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
            }
            $data = $request->safe()->except(['material_ids', 'exam_ids']);
            $data['updated_by'] = $request->user()->id;
            if (! $package->exists) {
                $data['created_by'] = $request->user()->id;
            }
            $package->fill($data)->save();
            $package->materials()->sync($request->validated('material_ids'));
            $package->exams()->sync($request->validated('exam_ids'));
        });
    }
}
