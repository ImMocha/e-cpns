<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MaterialType;
use App\ExamCatalog;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Materi untuk peserta (§10).
 *
 * Dua aturan yang dipegang controller ini:
 *
 * 1. **Daftar hanya berisi materi yang benar-benar ada.** Query-nya memakai
 *    `scopeVisibleToMembers()`, jadi draft, bidang nonaktif, dan materi
 *    soft-deleted tidak pernah masuk HTML — bukan sekadar disembunyikan dengan
 *    CSS yang bisa dilewati lewat devtools.
 * 2. **Akses decide di server.** `Material::isAccessibleBy()` satu-satunya
 *    penentu. Peserta tetap dapat melihat halaman dan daftar materi, sedangkan
 *    pembaca serta berkasnya hanya tersedia bagi pemilik akses.
 */
class MaterialController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): View
    {
        $request->validate(['topic_id' => ['nullable', 'integer', 'min:1']]);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'section_id' => $request->query('section_id'),
            'type' => $request->query('type'),
            'topic_id' => $request->query('topic_id'),
        ];

        $user = $request->user();
        $user->load('userPackages');
        $showDrafts = $user->isAdmin() && $request->boolean('show_drafts');
        $filters['show_drafts'] = $showDrafts;

        $lessonQuery = function (Builder|Relation $query) use ($filters, $user, $showDrafts): void {
            $query->inActiveTopics()->when(! $showDrafts, fn (Builder $query) => $query->published())
                ->with(['userPackages' => fn (Builder|Relation $grants) => $grants->active()->whereBelongsTo($user)])
                ->when($filters['type'], fn (Builder $query, $type) => $query->where('type', $type))
                ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                    $query->search($filters['q'])->orWhereHas('topic', fn (Builder $topic) => $topic->where('name', 'like', '%'.$filters['q'].'%'));
                }))
                ->orderBy('position')->orderBy('id');
        };

        $topics = Topic::query()->active()->with('section')
            ->withWhereHas('materials', $lessonQuery)
            ->when($filters['section_id'], fn (Builder $query, $id) => $query->where('section_id', $id))
            ->when($filters['topic_id'], fn (Builder $query, $id) => $query->whereKey($id))
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $materialIds = $topics->getCollection()
            ->flatMap(fn (Topic $topic) => $topic->materials->pluck('id'))
            ->all();
        $completedMaterialIds = MaterialProgress::query()->whereBelongsTo($user)
            ->whereIn('material_id', $materialIds)
            ->whereNotNull('completed_at')
            ->pluck('material_id')
            ->flip()
            ->all();

        return view('Dashboard.User.materials.index', [
            'topics' => $topics,
            'filters' => $filters,
            'sections' => $this->sectionsWithCounts($showDrafts),
            'types' => MaterialType::cases(),
            'counts' => $this->counts($user, $showDrafts),
            'completedMaterialIds' => $completedMaterialIds,
        ]);
    }

    public function topic(Request $request, Topic $topic, ExamCatalog $catalog): View
    {
        $user = $request->user();
        $user->load('userPackages');
        abort_unless($topic->is_active && $topic->section?->is_active, 404);
        $showDrafts = $user->isAdmin() && $request->boolean('show_drafts');
        $lessons = $topic->materials()->inActiveTopics()
            ->when(! $showDrafts, fn (Builder $query) => $query->published())
            ->with(['userPackages' => fn (Builder|Relation $grants) => $grants->active()->whereBelongsTo($user)])
            ->orderBy('position')->orderBy('id')->get();
        abort_if($lessons->isEmpty(), 404);
        $request->validate(['material' => ['nullable', 'integer', 'min:1']]);
        $material = $request->filled('material')
            ? $lessons->firstWhere('id', (int) $request->query('material'))
            : $lessons->first(fn (Material $lesson): bool => $lesson->isAccessibleBy($user)) ?? $lessons->first();
        abort_unless($material, 404);

        return $this->show($request, $material, $catalog);
    }

    /** Halaman baca: PDF di-embed, video diputar. */
    public function show(Request $request, Material $material, ExamCatalog $catalog): View
    {
        $user = $request->user();
        $user->load('userPackages');

        // Materi yang tidak tayang: 404. Keberadaannya juga tidak perlu
        // terungkap ke peserta yang tidak berhak. Admin tetap boleh
        // mempratinjau draft supaya bisa memeriksa sebelum diterbitkan.
        if (! $this->mayOpen($material, $user)) {
            abort(404);
        }

        $material->load(['section', 'topic']);
        $progress = null;

        if (! $user->isAdmin() && $material->isAccessibleBy($user) && $material->hasAvailableContent()) {
            $progress = MaterialProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'material_id' => $material->id],
                ['last_viewed_at' => now()],
            );
        }

        $showDrafts = $user->isAdmin() && ($request->boolean('show_drafts') || ! $material->isPublished());
        $lessons = $material->topic
            ? $material->topic->materials()->inActiveTopics()
                ->when(! $showDrafts, fn (Builder $query) => $query->published())
                ->with(['userPackages' => fn (Builder|Relation $grants) => $grants->active()->whereBelongsTo($user)])
                ->orderBy('position')->orderBy('id')->get()
            : collect();
        $completedMaterialIds = MaterialProgress::query()->whereBelongsTo($user)
            ->whereIn('material_id', $lessons->pluck('id')->all())->whereNotNull('completed_at')
            ->pluck('material_id')->flip()->all();
        $lessonIndex = $lessons->search(fn (Material $lesson): bool => $lesson->is($material));
        $previousMaterial = $lessonIndex !== false && $lessonIndex > 0 ? $lessons->get($lessonIndex - 1) : null;
        $nextMaterial = $lessonIndex !== false ? $lessons->get($lessonIndex + 1) : null;
        $isLastMaterial = $lessonIndex !== false && $nextMaterial === null;
        $nextTopic = null;
        if ($isLastMaterial && $material->topic) {
            $currentTopic = $material->topic;
            $nextTopic = Topic::query()->active()->where('section_id', $material->section_id)
                ->whereHas('materials', fn (Builder $query) => $query->inActiveTopics()
                    ->when(! $showDrafts, fn (Builder $query) => $query->published()))
                ->where(fn (Builder $query) => $query->where('position', '>', $currentTopic->position)
                    ->orWhere(fn (Builder $query) => $query->where('position', $currentTopic->position)->where('id', '>', $currentTopic->id)))
                ->ordered()->first();
        }

        return view('Dashboard.User.materials.show', [
            'lessons' => $lessons,
            'completedMaterialIds' => $completedMaterialIds,
            'showDrafts' => $showDrafts,
            'previousMaterial' => $previousMaterial,
            'nextMaterial' => $nextMaterial,
            'isLastMaterial' => $isLastMaterial,
            'nextTopic' => $nextTopic,
            'lessonNumber' => $lessonIndex !== false ? $lessonIndex + 1 : null,
            'canOpen' => fn (Material $lesson): bool => $lesson->isAccessibleBy($user),
            'contentAccessible' => $material->isAccessibleBy($user),
            'material' => $material,
            'progress' => $progress,
            'topicQuizzes' => $material->topic ? $catalog->listing($user, 'quiz', [$material->topic->id])['banks'] : collect(),
        ]);
    }

    /**
     * Alirkan berkas PDF.
     *
     * Route terpisah, bukan symlink `public/storage`, karena inilah satu-satunya
     * tempat akses dicek. Kalau PDF bisa diambil langsung dari URL, materi premium
     * hanya terkunci secara visual.
     */
    public function file(Request $request, Material $material): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();
        $user->load('userPackages');

        if (! $this->mayOpen($material, $user)) {
            abort(404);
        }

        if (! $material->isAccessibleBy($user)) {
            abort(403, 'Materi ini butuh paket belajar yang aktif.');
        }

        if (! $material->fileExists()) {
            abort(404, 'Berkas materi tidak ditemukan.');
        }

        return Storage::disk(Material::PDF_DISK)->response(
            $material->file_path,
            $material->slug.'.pdf',
            [
                'Content-Type' => 'application/pdf',
                // Dipakai oleh pdf.js lewat fetch(), jadi tetap inline.
                'Content-Disposition' => 'inline',
                // Jangan sampai salinan PDF tertinggal di cache browser atau
                // proxy setelah peserta logout.
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ],
        );
    }

    /**
     * Bidang yang punya materi terbit, lengkap dengan jumlahnya.
     *
     * `having` disembunyikan supaya bidang kosong tidak muncul sebagai pilihan
     * yang leads ke halaman kosong.
     *
     * @return Collection<int, Section>
     */
    private function sectionsWithCounts(bool $showDrafts = false): Collection
    {
        return Section::query()
            ->active()
            ->ordered()
            ->withCount(['materials as published_materials_count' => fn (Builder $query) => $query->inActiveTopics()->when(! $showDrafts, fn (Builder $query) => $query->published())])
            ->get()
            ->filter(fn (Section $section) => $section->published_materials_count > 0)
            ->values();
    }

    /** @return array{total:int, pdf:int, video:int, locked:int} */
    private function counts(User $user, bool $showDrafts = false): array
    {
        return [
            'total' => $this->visibleQuery($showDrafts)->count(),
            'pdf' => $this->visibleQuery($showDrafts)->where('type', MaterialType::Pdf->value)->count(),
            'video' => $this->visibleQuery($showDrafts)->where('type', MaterialType::Video->value)->count(),
            'locked' => $user->isAdmin() ? 0 : $this->visibleQuery()->count() - $this->visibleQuery()->accessibleTo($user)->count(),
        ];
    }

    /**
     * Query dasar untuk menghitung.
     *
     * `withoutEagerLoads()` dipakai supaya penghitungan tidak ikut menarik relasi
     * yang tidak dipakai.
     *
     * @return Builder<Material>
     */
    private function visibleQuery(bool $showDrafts = false): Builder
    {
        return Material::query()->inActiveTopics()->when(! $showDrafts, fn (Builder $query) => $query->published())->withoutEagerLoads();
    }

    /**
     * Apakah materi boleh dibuka oleh pengguna ini sama sekali.
     *
     * Admin lolos apa adanya: mereka perlu pratinjau draft dan materi premium
     * untuk verifikasi sebelum diterbitkan.
     */
    private function mayOpen(Material $material, ?User $user): bool
    {
        return $user?->isAdmin() === true || $material->isVisibleToMember();
    }
}
