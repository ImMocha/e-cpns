@extends('layouts.admin.main')
@section('title', 'Kelola Artikel — Ganesha Nusa')
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Artikel</span></nav>
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Konten</span><h1 style="margin-top:.5rem">Kelola Artikel</h1><p>Tulis artikel, lihat preview langsung, lalu terbitkan untuk peserta.</p></div>
        <a class="btn btn--primary" href="{{ route('admin.articles.create') }}"><svg data-icon="plus"></svg> Tulis Artikel</a>
    </header>
    <div class="article-stats-summary">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach (['total' => ['Total Artikel', 'quote', ''], 'published' => ['Terbit', 'check', 'ico--mint'], 'draft' => ['Draft', 'pencil', 'ico--amber'], 'readers' => ['Pembaca Unik', 'users', 'ico--pink'], 'views' => ['Tayangan', 'eye', '']] as $key => [$label, $icon, $tone])
                <article class="stat"><span class="stat__icon {{ $tone }}"><svg data-icon="{{ $icon }}"></svg></span><div><div class="stat__label">{{ $label }}</div><div class="stat__value">{{ number_format($stats[$key], 0, ',', '.') }}</div></div></article>
            @endforeach
        </section>
        <p class="text-sm leading-relaxed text-soft">Pembaca unik dihitung per akun atau sesi pengunjung. Baca ulang menambah tayangan, tetapi tidak menambah pembaca unik.</p>
    </div>
    <form method="GET" class="card flex flex-wrap items-end gap-4 article-search-form">
        <div class="field flex-1 min-w-48"><label class="label" for="article-search">Cari artikel</label><input class="input" id="article-search" name="q" value="{{ request('q') }}" placeholder="Judul atau ringkasan"></div>
        <div class="field"><label class="label" for="article-field">Bidang</label><select class="select" id="article-field" name="section_id"><option value="">Semua bidang</option>@foreach ($sections as $section)<option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>@endforeach</select></div>
        <div class="field"><label class="label" for="article-status">Status</label><select class="select" id="article-status" name="status"><option value="">Semua status</option>@foreach (\App\Enums\ArticleStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        <button class="btn btn--primary">Cari</button><a class="btn btn--ghost" href="{{ route('admin.articles.index') }}">Reset</a>
    </form>
    <section class="card">
        <div class="card__head"><div class="card__title">Daftar artikel</div><span class="text-sm text-soft">{{ $articles->total() }} artikel ditemukan</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Artikel</th><th>Bidang</th><th>Status</th><th class="num">Pembaca</th><th class="num">Tayangan</th><th>Diperbarui</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($articles as $article)
                        <tr>
                            <td><a class="font-bold text-primary" href="{{ route('admin.articles.edit', $article) }}">{{ $article->title }}</a><div class="text-xs text-soft">{{ $article->author?->name ?? 'Tim Ganesha Nusa' }} · {{ $article->readingMinutes() }} menit baca</div></td>
                            <td>{{ $article->section?->name ?? 'Bidang dihapus' }}</td>
                            <td><span class="badge {{ $article->status->badgeClass() }}">{{ $article->published_at?->isFuture() ? 'Terjadwal' : $article->status->label() }}</span>@if ($article->published_at?->isFuture())<div class="text-xs text-soft"><x-local-time :value="$article->published_at" format="d M Y, H:i" /></div>@endif</td>
                            <td class="num">{{ number_format((int) ($articleViews[$article->id]->readers_count ?? 0), 0, ',', '.') }}</td>
                            <td class="num">{{ number_format((int) ($articleViews[$article->id]->views_count ?? 0), 0, ',', '.') }}</td>
                            <td><x-local-time :value="$article->updated_at" format="d M Y, H:i" /></td>
                            <td><div class="btn-row">
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.articles.edit', $article) }}" aria-label="Edit {{ $article->title }}"><svg data-icon="pencil"></svg> Edit</a>
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.articles.show', $article) }}" aria-label="Preview {{ $article->title }}"><svg data-icon="eye"></svg> Preview</a>
                                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" data-content-confirm data-confirm-title="Hapus artikel?" data-confirm-body="Artikel ini akan disimpan sebagai arsip.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm text-danger" type="submit" aria-label="Hapus {{ $article->title }}"><svg data-icon="trash"></svg> Hapus</button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="article-empty"><svg data-icon="quote"></svg><h2>Belum ada artikel</h2><p class="text-soft">Mulai dari draft pertama, atau ubah filter pencarian.</p><a class="btn btn--primary" href="{{ route('admin.articles.create') }}">Tulis artikel pertama</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$articles" />
    </section>
@endsection
