@extends('layouts.admin.main')
@section('title', 'Kelola Topik — Ganesha Nusa')
@section('content')
  @include('components.flash')
  <header class="page-head content-page-head">
    <div><span class="eyebrow">Ruang Kerja Konten</span><h1>Materi &amp; Topik</h1><p>Susun topik dalam setiap bidang, lalu kelola materi dan latihan di dalamnya.</p></div>
    <a class="btn btn--primary" href="{{ route('admin.topics.create', array_filter(['section_id' => $selectedSection?->id, 'workspace' => 1])) }}"><svg data-icon="plus"></svg> Tambah Topik</a>
  </header>
  @include('Dashboard.Admin.materials._navigation')
  <section class="card content-directory">
    <div class="content-panel-heading content-panel-heading--main"><div><div class="content-location">{{ $selectedSection?->name ?? 'Semua bidang' }}</div><h2>Topik Pembelajaran</h2><p>{{ $counts['total'] }} topik · {{ $counts['inactive'] }} nonaktif · {{ $counts['empty'] }} belum berisi materi</p></div><a class="btn btn--ghost btn--sm" href="{{ route('admin.sections.index') }}">Kelola Bidang <svg data-icon="external"></svg></a></div>
    <form method="GET" action="{{ route('admin.topics.index') }}" class="content-material-filters">
      @if($selectedSection)<input type="hidden" name="section_id" value="{{ $selectedSection->id }}">@endif
      <div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari nama atau deskripsi topik..." aria-label="Cari topik"></div>
      <select class="select" name="state" aria-label="Filter topik"><option value="">Semua topik</option><option value="active" @selected($filters['state'] === 'active')>Aktif</option><option value="inactive" @selected($filters['state'] === 'inactive')>Nonaktif</option><option value="empty" @selected($filters['state'] === 'empty')>Belum ada materi</option></select>
      <button class="btn btn--ghost btn--sm" type="submit">Cari</button>
      @if($filters['q'] || $filters['state'])<a class="content-filter-reset" href="{{ route('admin.topics.index', array_filter(['section_id' => $selectedSection?->id])) }}">Reset filter</a>@endif
    </form>
    <div class="content-topic-grid">
      @forelse($topics as $topic)
        <article class="content-topic-card">
          <div class="content-topic-card__top"><span class="badge {{ $topic->section->resolvedTone()->badgeClass() }} badge--flat">{{ $topic->section->name }}</span>
            <details class="content-menu"><summary class="content-icon-button" aria-label="Tindakan untuk topik {{ $topic->name }}"><svg data-icon="more"></svg></summary><div class="content-menu__items">
              <a href="{{ route('admin.topics.edit', [$topic, 'workspace' => 1]) }}"><svg data-icon="pencil"></svg> Edit Topik</a>
              <a href="{{ route('admin.materials.create', ['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'workspace' => 1]) }}"><svg data-icon="plus"></svg> Tambah Materi</a>
              <a href="{{ route('admin.materials.quizzes.index', ['topic_id' => $topic->id]) }}"><svg data-icon="list-checks"></svg> Kelola Latihan</a>
              <a href="{{ route('admin.materials.quizzes.create', ['topic_id' => $topic->id]) }}"><svg data-icon="plus"></svg> Tambah Latihan</a>
              @if($topic->materials_count === 0 && $topic->quizzes_count === 0)<form method="POST" action="{{ route('admin.topics.destroy', $topic) }}" data-content-confirm data-confirm-title="Hapus topik?" data-confirm-body="Topik &quot;{{ $topic->name }}&quot; akan dihapus jika tidak memiliki materi atau latihan, termasuk konten arsip." data-confirm-ok="Ya, hapus">@csrf @method('DELETE')<input type="hidden" name="workspace" value="1"><button type="submit" class="is-danger"><svg data-icon="trash"></svg> Hapus Topik</button></form>@endif
            </div></details>
          </div>
          <a class="content-topic-card__title" href="{{ route('admin.materials.index', ['section_id' => $topic->section_id, 'topic_id' => $topic->id]) }}">{{ $topic->name }}</a>
          <p class="content-topic-card__description">{{ $topic->description ?: 'Belum ada deskripsi topik.' }}</p>
          <div class="content-topic-card__counts"><span><strong>{{ $topic->materials_count }}</strong> materi · {{ $topic->quizzes_count }} latihan</span><span>{{ $topic->published_materials_count }} terbit · {{ $topic->materials_count - $topic->published_materials_count }} draft</span></div>
          <div class="content-topic-card__footer"><span class="content-topic-state {{ $topic->is_active ? '' : 'is-inactive' }}">{{ $topic->is_active ? 'Aktif' : 'Nonaktif' }}</span><a href="{{ route('admin.materials.index', ['section_id' => $topic->section_id, 'topic_id' => $topic->id]) }}">Kelola Materi <svg data-icon="arrow-right"></svg></a></div>
        </article>
      @empty
        <div class="empty content-empty"><span class="empty__icon"><svg data-icon="layers"></svg></span><strong>{{ $filters['q'] || $filters['state'] ? 'Topik tidak ditemukan' : 'Belum ada topik' }}</strong><p>{{ $filters['q'] || $filters['state'] ? 'Coba kata pencarian atau filter lain.' : 'Buat topik terlebih dahulu, lalu tambahkan PDF dan video.' }}</p><a class="btn btn--primary btn--sm" href="{{ route('admin.topics.create', array_filter(['section_id' => $selectedSection?->id, 'workspace' => 1])) }}">Tambah Topik</a></div>
      @endforelse
    </div>
    @if($topics->total() > 0)<footer class="content-list-footer"><span>Menampilkan {{ $topics->firstItem() }}–{{ $topics->lastItem() }} dari {{ $topics->total() }} topik</span><x-pager :paginator="$topics" /></footer>@endif
  </section>
@endsection
