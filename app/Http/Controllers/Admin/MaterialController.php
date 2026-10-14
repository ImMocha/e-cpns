<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMaterialRequest;
use App\Http\Requests\Admin\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan materi (§33): PDF dan video milik satu bidang.
 */
class MaterialController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'topic_q' => ['nullable', 'string', 'max:120'],
            'section_id' => ['nullable', 'integer'],
            'topic_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in(MaterialType::options())],
            'status' => ['nullable', Rule::in(MaterialStatus::options())],
        ]);
        $selectedTopic = $request->filled('topic_id')
            ? Topic::with('section')->withCount('quizzes')->findOrFail($request->integer('topic_id')) : null;
        $sectionId = $request->filled('section_id') ? $request->integer('section_id') : $selectedTopic?->section_id;
        $selectedSection = $sectionId ? Section::findOrFail($sectionId) : null;
        abort_if($selectedTopic && $selectedTopic->section_id !== $sectionId, 404);

        $filters = [
            'q' => $request->string('q')->trim()->toString(),
            'topic_q' => $request->string('topic_q')->trim()->toString(),
            'section_id' => $sectionId,
            'topic_id' => $selectedTopic?->id,
            'type' => $request->query('type'),
            'status' => $request->query('status'),
        ];

        $contextQuery = Material::query()
            ->when($filters['section_id'], fn ($query, $id) => $query->where('section_id', $id))
            ->when($filters['topic_id'], fn ($query, $id) => $query->where('topic_id', $id));
        $materials = (clone $contextQuery)->search($filters['q'])
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->with(['section', 'topic'])
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $topics = Topic::query()->with('section')->withCount(['materials', 'quizzes'])
            ->whereHas('section')
            ->when($sectionId, fn (Builder $query) => $query->where('section_id', $sectionId))
            ->when($filters['topic_q'] !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$filters['topic_q'].'%'))
            ->ordered()->paginate(8, ['*'], 'topic_page')->withQueryString();

        return view('Dashboard.Admin.materials.index', [
            'materials' => $materials,
            'filters' => $filters,
            'stats' => $this->stats($contextQuery),
            'sections' => Section::query()->ordered()->withCount(['topics', 'materials'])->get(),
            'topics' => $topics,
            'selectedSection' => $selectedSection,
            'selectedTopic' => $selectedTopic,
            'topicQuizzes' => $selectedTopic ? $selectedTopic->quizzes()->with('topic.section')
                ->withCount(['questions as available_count' => fn (Builder $query): Builder => $query->visibleToMembers()])
                ->latest('id')->limit(5)->get() : collect(),
            'types' => MaterialType::cases(),
            'statuses' => MaterialStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('Dashboard.Admin.materials.create', [
            'material' => new Material([
                'section_id' => $request->query('section_id'),
                'topic_id' => $request->query('topic_id'),
                'type' => MaterialType::Pdf->value,
                'access_level' => MaterialAccess::Free->value,
                'status' => MaterialStatus::Draft->value,
                'position' => Material::max('position') + 1,
            ]),
            'sections' => $this->sections(),
            'topics' => Topic::query()->ordered()->get(),
            'returnUrl' => route('admin.materials.index', array_filter(['section_id' => $request->integer('section_id'), 'topic_id' => $request->integer('topic_id')])),
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $material = Material::create(array_merge(
            $request->materialAttributes(),
            $this->replacePdf($request->file('file'), null),
            [
                'published_at' => $this->publishedAt($request->string('status')->toString()),
                'created_by' => $request->user()?->id,
            ],
        ));

        return redirect()
            ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
            ->with('success', "Materi {$material->title} berhasil dibuat.");
    }

    public function show(Material $material): RedirectResponse
    {
        return redirect()->route('admin.materials.edit', $material);
    }

    public function edit(Material $material): View
    {
        return view('Dashboard.Admin.materials.edit', [
            'material' => $material->load(['section', 'topic']),
            'sections' => $this->sections(),
            'topics' => Topic::query()->ordered()->get(),
            'returnUrl' => route('admin.materials.index', ['section_id' => $material->section_id, 'topic_id' => $material->topic_id]),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        $previousPath = $material->file_path;

        $attributes = array_merge(
            $request->materialAttributes(),
            $this->replacePdf($request->file('file'), $previousPath),
            ['updated_by' => $request->user()?->id],
        );

        // Mode video: berkas PDF lama tidak lagi relevan, jadi dibuang.
        if ($attributes['type'] === MaterialType::Video->value) {
            if ($previousPath !== null) {
                Storage::disk(Material::PDF_DISK)->delete($previousPath);
            }

            $attributes['file_path'] = null;
            $attributes['file_size'] = null;
            $attributes['page_count'] = null;
        }

        $material->fill($attributes);
        $material->published_at = $this->publishedAt(
            $request->string('status')->toString(),
            $material->published_at,
        );
        $material->save();

        return redirect()
            ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
            ->with('success', "Perubahan pada materi {$material->title} disimpan.");
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        // Berkas sengaja tidak ikut dihapus: soft delete harus bisa dibatalkan
        // tanpa admin harus mengunggah PDF-nya lagi.
        $material->delete();

        return redirect()
            ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
            ->with('success', "Materi {$material->title} dihapus.");
    }

    /** Terbitkan atau tahan kembali tanpa membuka form edit. */
    public function toggleStatus(Request $request, Material $material): RedirectResponse
    {
        if ($material->isPublished()) {
            $material->update([
                'status' => MaterialStatus::Draft,
                'published_at' => null,
            ]);

            return redirect()
                ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
                ->with('success', "Materi {$material->title} dikembalikan ke draft.");
        }

        if (! $material->hasContentSource()) {
            return redirect()
                ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
                ->with('error', "Materi {$material->title} belum punya berkas PDF atau URL video, jadi belum bisa diterbitkan.");
        }

        $material->update([
            'status' => MaterialStatus::Published,
            'published_at' => $material->published_at ?? now(),
        ]);

        return redirect()
            ->route('admin.materials.index', $request->boolean('workspace') ? ['section_id' => $material->section_id, 'topic_id' => $material->topic_id] : [])
            ->with('success', "Materi {$material->title} diterbitkan.");
    }

    /** @return Collection<int, Section> */
    private function sections(): Collection
    {
        return Section::query()->ordered()->get();
    }

    /**
     * Simpan berkas PDF baru dan buang yang lama.
     *
     * Berkas lama dihapus hanya setelah yang baru benar-benar tersimpan, jadi
     * kegagalan di tengah tidak membuat materi kehilangan isinya.
     *
     * @return array<string, mixed>
     */
    private function replacePdf(?UploadedFile $file, ?string $previousPath): array
    {
        if ($file === null) {
            return $previousPath === null ? [] : ['file_path' => $previousPath];
        }

        $path = $file->store(Material::PDF_DIRECTORY, Material::PDF_DISK);

        if ($previousPath !== null) {
            Storage::disk(Material::PDF_DISK)->delete($previousPath);
        }

        return [
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ];
    }

    /**
     * `published_at` diisi saat pertama kali terbit, dan dikosongkan saat
     * kembali ke draft — supaya tidak ada materi "terbit" tanpa tanggal.
     */
    private function publishedAt(string $status, ?Carbon $current = null): ?Carbon
    {
        if ($status === MaterialStatus::Draft->value) {
            return null;
        }

        return $current ?? now();
    }

    /** @return array{total: int, published: int, draft: int, premium: int} */
    private function stats(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'published' => (clone $query)->published()->count(),
            'draft' => (clone $query)->where('status', MaterialStatus::Draft->value)->count(),
            'premium' => (clone $query)->where('access_level', MaterialAccess::Premium->value)->count(),
        ];
    }
}
