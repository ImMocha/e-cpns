<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TopicRequest;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TopicController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'section_id' => ['nullable', 'integer'],
            'state' => ['nullable', Rule::in(['active', 'inactive', 'empty'])],
        ]);
        $selectedSection = $request->filled('section_id') ? Section::findOrFail($request->integer('section_id')) : null;
        $filters = ['section_id' => $selectedSection?->id, 'q' => $request->string('q')->trim()->toString(), 'state' => $request->query('state')];
        $query = Topic::query()->whereHas('section')
            ->when($selectedSection, fn (Builder $query) => $query->where('section_id', $selectedSection->id));

        return view('Dashboard.Admin.topics.index', [
            'topics' => (clone $query)->with('section')->withCount([
                'materials',
                'materials as published_materials_count' => fn (Builder $query) => $query->published(),
            ])
                ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                    $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%');
                }))
                ->when($filters['state'] === 'active', fn (Builder $query) => $query->active())
                ->when($filters['state'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
                ->when($filters['state'] === 'empty', fn (Builder $query) => $query->doesntHave('materials'))
                ->ordered()->paginate(9)->withQueryString(),
            'sections' => Section::query()->ordered()->withCount(['topics', 'materials'])->get(),
            'selectedSection' => $selectedSection,
            'filters' => $filters,
            'counts' => ['total' => (clone $query)->count(), 'inactive' => (clone $query)->where('is_active', false)->count(), 'empty' => (clone $query)->doesntHave('materials')->count()],
        ]);
    }

    public function create(Request $request): View
    {
        return view('Dashboard.Admin.topics.form', [
            'topic' => new Topic(['section_id' => $request->query('section_id'), 'position' => 0, 'is_active' => true]),
            'sections' => Section::query()->ordered()->get(),
        ]);
    }

    public function store(TopicRequest $request): RedirectResponse
    {
        Topic::create(array_merge($request->validated(), ['position' => $request->input('position') ?? 0]));

        return redirect()->route('admin.topics.index', $request->boolean('workspace') ? ['section_id' => $request->integer('section_id')] : [])->with('success', 'Topik berhasil dibuat.');
    }

    public function show(Topic $topic): RedirectResponse
    {
        return redirect()->route('admin.topics.edit', $topic);
    }

    public function edit(Topic $topic): View
    {
        return view('Dashboard.Admin.topics.form', [
            'topic' => $topic,
            'sections' => Section::query()->ordered()->get(),
        ]);
    }

    public function update(TopicRequest $request, Topic $topic): RedirectResponse
    {
        if ((int) $request->input('section_id') !== $topic->section_id && $topic->materials()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'section_id' => 'Topik yang memiliki materi tidak bisa dipindahkan bidang. Pindahkan materinya dahulu.',
            ]);
        }

        $topic->update(array_merge($request->validated(), ['position' => $request->input('position') ?? 0]));

        return redirect()->route('admin.topics.index', $request->boolean('workspace') ? ['section_id' => $topic->section_id] : [])->with('success', 'Topik berhasil diperbarui.');
    }

    public function destroy(Request $request, Topic $topic): RedirectResponse
    {
        if ($topic->materials()->withTrashed()->exists()) {
            return back()->with('error', 'Topik masih memiliki materi, termasuk materi arsip. Pindahkan materi atau nonaktifkan topik.');
        }

        $topic->delete();

        return redirect()->route('admin.topics.index', $request->boolean('workspace') ? ['section_id' => $topic->section_id] : [])->with('success', 'Topik berhasil dihapus.');
    }
}
