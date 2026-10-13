@extends('layouts.admin.main')
@section('title', 'Soal & Pembahasan — LONTAR')
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Soal &amp; Pembahasan</span></nav>
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Bank Soal</span><h1 style="margin-top:.5rem">Soal &amp; Pembahasan</h1><p>Kelola pertanyaan, pilihan jawaban, dan pembahasan untuk peserta.</p></div>
        <a class="btn btn--primary" href="{{ route('admin.questions.create') }}"><svg data-icon="plus"></svg> Tambah Soal</a>
    </header>
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 question-section">
        @foreach (['total' => ['Total Soal', 'layers', ''], 'published' => ['Terbit', 'check', 'ico--mint'], 'draft' => ['Draft', 'pencil', 'ico--amber'], 'sections' => ['Bidang Terisi', 'folder', 'ico--pink']] as $key => [$label, $icon, $tone])
            <article class="stat"><span class="stat__icon {{ $tone }}"><svg data-icon="{{ $icon }}"></svg></span><div><div class="stat__label">{{ $label }}</div><div class="stat__value">{{ number_format($stats[$key], 0, ',', '.') }}</div></div></article>
        @endforeach
    </section>
    <form method="GET" class="card question-filter question-section">
        <div class="field question-filter-search"><label class="label" for="question-search">Cari soal</label><input class="input" id="question-search" name="q" value="{{ request('q') }}" placeholder="Judul atau teks pertanyaan"></div>
        <div class="field"><label class="label" for="question-field">Bidang</label><select class="select" id="question-field" name="section_id"><option value="">Semua bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="question-difficulty">Kesulitan</label><select class="select" id="question-difficulty" name="difficulty"><option value="">Semua level</option>@foreach ($difficulties as $difficulty)<option value="{{ $difficulty->value }}" @selected(request('difficulty') === $difficulty->value)>{{ $difficulty->label() }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="question-status">Status</label><select class="select" id="question-status" name="status"><option value="">Semua status</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Terbit</option></select></div>
        <div class="btn-row"><button class="btn btn--primary">Cari</button><a class="btn btn--ghost" href="{{ route('admin.questions.index') }}">Reset</a></div>
    </form>
    <section class="card">
        <div class="card__head"><div class="card__title">Daftar Soal</div><span class="text-sm text-soft">{{ $questions->total() }} soal ditemukan</span></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Soal</th><th>Bidang</th><th>Kesulitan</th><th>Kunci</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($questions as $question)
                    <tr>
                        <td class="question-title-cell"><a class="font-bold text-primary" href="{{ route('admin.questions.edit', $question) }}">{{ $question->title }}</a><div class="text-sm text-soft">{{ \Illuminate\Support\Str::limit(\App\ArticleContent::text($question->body), 95) }}</div><div class="text-xs text-soft">{{ $question->author?->name ?? 'Admin' }} · {{ $question->updated_at->format('d M Y') }}</div></td>
                        <td>{{ $question->section?->name ?? 'Bidang dihapus' }}</td><td>{{ $question->difficulty->label() }}</td>
                        <td><span class="badge badge--primary badge--flat">{{ chr(65 + $question->correct_option) }}</span></td>
                        <td><span class="badge {{ $question->status->badgeClass() }}">{{ $question->status->label() }}</span></td>
                        <td><div class="btn-row question-actions">
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.questions.edit', $question) }}" aria-label="Edit {{ $question->title }}"><svg data-icon="pencil"></svg> Edit</a>
                            <a class="btn btn--ghost btn--square btn--sm" href="{{ route('admin.questions.show', $question) }}" aria-label="Preview {{ $question->title }}"><svg data-icon="eye"></svg></a>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" data-content-confirm data-confirm-title="Hapus soal?" data-confirm-body="Soal ini akan diarsipkan dan hilang dari bank soal peserta.">@csrf @method('DELETE')<button class="btn btn--ghost btn--square btn--sm" aria-label="Hapus {{ $question->title }}"><svg data-icon="trash"></svg></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="question-empty"><svg data-icon="clipboard"></svg><strong>Belum ada soal yang sesuai</strong><p class="text-soft">Tambahkan soal baru atau ubah pencarian dan filter.</p></div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <x-pager :paginator="$questions" />
    </section>
@endsection

