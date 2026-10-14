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
        <p>Pilih bidang, buka topik, lalu pelajari PDF atau video sesuai urutan belajarmu.</p>
      </div>
      <div class="card card--tight">
        <div class="flex" style="gap:1.25rem">
          <div><div class="stat__label">Tersedia</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">{{ number_format($counts['total'], 0, ',', '.') }}</div></div>
          <div><div class="stat__label">PDF</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">{{ number_format($counts['pdf'], 0, ',', '.') }}</div></div>
          <div><div class="stat__label">Video</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">{{ number_format($counts['video'], 0, ',', '.') }}</div></div>
        </div>
      </div>
    </header>
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
          <a class="pill {{ $filters['section_id'] === null ? 'is-active' : '' }}" href="{{ route('materials.index', array_filter(['q' => $filters['q'], 'type' => $filters['type']])) }}">Semua bidang</a>
          @foreach ($sections as $section)
            <a class="pill {{ (string) $filters['section_id'] === (string) $section->id ? 'is-active' : '' }}"
               href="{{ route('materials.index', array_filter(['q' => $filters['q'], 'type' => $filters['type'], 'section_id' => $section->id])) }}">{{ $section->name }} ({{ $section->published_materials_count }})</a>
          @endforeach
        </div>
        <div class="flex items-center flex-wrap" style="gap:.75rem">
          <div class="input-group" style="flex:1;min-width:180px">
            <span class="input-group__icon"><svg data-icon="search"></svg></span>
            <input class="input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari topik atau materi..." aria-label="Cari topik atau materi">
          </div>
          <select class="select" name="type" aria-label="Filter format" style="width:auto">
            <option value="">Semua format</option>
            @foreach ($types as $type)
              <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
            @endforeach
          </select>
          <button class="btn btn--ghost" type="submit">Terapkan</button>
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
          <details class="card card--pop lesson-topic" id="topic-{{ $topic->id }}" @if ($loop->first) open @endif>
            <summary>
              <span class="lesson-topic__number">{{ $loop->iteration }}</span>
              <div class="lesson-topic__heading">
                <h3>{{ $topic->name }}</h3>
                <p class="text-xs text-soft">{{ $topic->materials->count() }} materi</p>
              </div>
              <span class="lesson-topic__toggle" aria-hidden="true"><svg data-icon="chevron-down"></svg></span>
            </summary>
            <div class="lesson-topic__body">
              @if ($topic->description)
                <p class="text-sm text-soft lesson-topic__description">{{ $topic->description }}</p>
              @endif
              @foreach ($topic->materials as $material)
                @php($open = $canOpen($material))
                <a class="lesson-row" href="{{ $open ? route('materials.show', $material) : route('packages.index') }}"
                   @unless ($open) title="Materi Premium: pilih paket belajar untuk membuka" @endunless>
                  <span class="lesson-row__icon"><svg data-icon="{{ $open ? $material->type->icon() : 'lock' }}"></svg></span>
                  <span class="lesson-row__title">{{ $material->title }}</span>
                  <span class="lesson-row__meta">
                    @if (isset($completedMaterialIds[$material->id]))<span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Selesai</span>@endif
                    @unless ($open)<span class="badge badge--amber badge--flat">Terkunci</span>@endunless
                    <span class="badge {{ $material->type->badgeClass() }} badge--flat">{{ $material->type->shortLabel() }}</span>
                    <span class="lesson-row__length">
                      @if ($material->type === MaterialType::Video)
                        {{ $material->humanDuration() ?? 'Video' }}
                      @else
                        {{ $material->page_count ? $material->page_count . ' halaman' : ($material->humanFileSize() ?? 'PDF') }}
                      @endif
                    </span>
                  </span>
                </a>
              @endforeach
              @if ($topicQuizzes->has($topic->id))
                <x-topic-quizzes :topic="$topic" :banks="$topicQuizzes->get($topic->id)" />
              @endif
            </div>
          </details>
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
