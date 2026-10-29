@extends('layouts.user.main')
@use('App\Enums\MaterialType')
@section('title', 'Materi — Ganesha Nusa CPNS Online')
@section('content')
    @include('components.flash')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Materi</span>
    </nav>
    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Konten Belajar</span>
        <h1 style="margin-top:.5rem">Materi Pembelajaran</h1>
        <p>Pilih bidang, buka topik, lalu pelajari materi sesuai urutan belajarmu.</p>
      </div>
      <div class="card card--tight">
        <div class="flex" style="gap:1.25rem">
          <div><div class="stat__label">Tersedia</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">{{ number_format($counts['total'], 0, ',', '.') }}</div></div>
        </div>
      </div>
    </header>
    @if(auth()->user()->isAdmin())
      <div class="card card--tight stack" style="--stack:.75rem;margin-bottom:1.5rem">
        <strong>Khusus admin · Pengelolaan materi</strong>
        <p class="text-sm text-soft">Aktifkan tampilan draft untuk memeriksa materi sebelum terbit. Peserta tetap hanya melihat materi terbit. Bidang dan topik nonaktif dapat diperiksa melalui Kelola Materi.</p>
        <div class="flex flex-wrap items-center" style="gap:.75rem">
          <x-material-publish scope="all" label="Terbitkan semua materi" />
          @if($filters['section_id'])<x-material-publish scope="section" :section-id="$filters['section_id']" label="Terbitkan semua draft bidang ini" />@endif
          <a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.index', array_filter(['section_id' => $filters['section_id'], 'topic_id' => $filters['topic_id']])) }}">Kelola Materi</a>
        </div>
        @if($filters['show_drafts'])<p class="text-sm text-soft">Pratinjau admin aktif: jumlah dan daftar materi mencakup draft. Draft belum terlihat oleh peserta.</p>@endif
      </div>
    @endif
    @if ($counts['locked'] > 0)
      <div class="alert alert--info" style="margin-bottom:1.5rem">
        <span class="alert__icon"><svg data-icon="lock"></svg></span>
        <div><strong>{{ $counts['locked'] }} materi Premium terkunci</strong> Aktifkan paket belajar untuk membuka isinya.</div>
      </div>
    @endif
    <form method="GET" action="{{ route('materials.index') }}" class="card card--tight">
      @if ($filters['topic_id'])<input type="hidden" name="topic_id" value="{{ $filters['topic_id'] }}">@endif
      @if ($filters['section_id'])
        <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
      @endif
      <div class="stack" style="--stack:1rem">
        <div class="pills material-field-pills">
          <a class="pill {{ $filters['section_id'] === null ? 'is-active' : '' }}" href="{{ route('materials.index', array_filter(['q' => $filters['q'], 'type' => $filters['type'], 'show_drafts' => $filters['show_drafts']])) }}">Semua bidang</a>
          @foreach ($sections as $section)
            <a class="pill {{ (string) $filters['section_id'] === (string) $section->id ? 'is-active' : '' }}"
               href="{{ route('materials.index', array_filter(['q' => $filters['q'], 'type' => $filters['type'], 'section_id' => $section->id, 'show_drafts' => $filters['show_drafts']])) }}">{{ $section->name }} ({{ $section->published_materials_count }})</a>
          @endforeach
        </div>
        <div class="flex items-center flex-wrap" style="gap:.75rem">
          <div class="input-group" style="flex:1;min-width:180px">
            <span class="input-group__icon"><svg data-icon="search"></svg></span>
            <input class="input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari topik atau materi..." aria-label="Cari topik atau materi">
          </div>
          <button class="btn btn--ghost" type="submit">Terapkan</button>
          @if(auth()->user()->isAdmin())
            <label class="flex items-center text-sm" style="gap:.5rem"><input type="checkbox" name="show_drafts" value="1" @checked($filters['show_drafts'])> Tampilkan draft (admin only)</label>
          @endif
          @if ($filters['q'] || $filters['section_id'] || $filters['type'] || $filters['topic_id'])
            <a class="btn btn--ghost" href="{{ route('materials.index') }}">Reset</a>
          @endif
        </div>
      </div>
    </form>

    @forelse ($topics->getCollection()->groupBy('section_id') as $sectionTopics)
      @php($section = $sectionTopics->first()->section)
      <section class="lesson-section" aria-label="{{ $section->name }}">
        <header class="lesson-section__head">
          <span class="badge {{ $section->resolvedTone()->badgeClass() }} badge--flat"><svg data-icon="{{ $section->resolvedIcon() }}"></svg> {{ $section->code }}</span>
          <h2>{{ $section->name }}</h2>
        </header>
        @foreach ($sectionTopics as $topic)
          <a class="card card--pop lesson-topic lesson-topic--link" id="topic-{{ $topic->id }}"
             href="{{ route('materials.topic', ['topic' => $topic, 'show_drafts' => $filters['show_drafts'] ? 1 : null]) }}">
            <span class="lesson-topic__number">{{ $loop->iteration }}</span>
            <div class="lesson-topic__heading">
              <h3>{{ $topic->name }}</h3>
              @php($lockedCount = $topic->materials->filter(fn ($lesson) => ! $lesson->isAccessibleBy(auth()->user()))->count())
              @if ($lockedCount > 0)<span class="badge badge--amber badge--flat"><svg data-icon="lock" aria-hidden="true"></svg> {{ $lockedCount }} materi terkunci</span>@endif
              <p class="text-xs text-soft">{{ $topic->materials->count() }} materi · Buka topik</p>
              @if ($topic->description)<p class="text-sm text-soft">{{ $topic->description }}</p>@endif
            </div>
            <span class="lesson-topic__toggle" aria-hidden="true"><svg data-icon="arrow-right"></svg></span>
          </a>
        @endforeach
      </section>
    @empty
      <section class="card" style="margin-top:1.5rem">
        <div class="empty">
          <span class="empty__icon"><svg data-icon="book"></svg></span>
          <strong>{{ $counts['total'] > 0 ? 'Materi tidak ditemukan' : 'Belum ada materi yang tayang' }}</strong>
          <p class="text-sm text-soft">Coba bidang atau kata pencarian lain. Materi akan tersedia setelah diterbitkan.</p>
        </div>
      </section>
    @endforelse

    @if ($topics->hasPages())
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem;margin-top:1.5rem">
        <span class="text-xs text-soft">Menampilkan {{ $topics->firstItem() }}–{{ $topics->lastItem() }} dari {{ $topics->total() }} topik</span>
        <x-pager :paginator="$topics" />
      </div>
    @endif
@endsection
