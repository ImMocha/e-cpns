@extends('layouts.admin.main')
@section('title', 'Kelola Materi — Ganesha Nusa')
@section('content')
  @include('components.flash')
  <header class="page-head content-page-head">
    <div><span class="eyebrow">Ruang Kerja Konten</span><h1>Materi &amp; Topik</h1><p>Kelola materi dan latihan dalam topik agar alur belajar peserta tersusun rapi.</p></div>
    <a class="btn btn--primary" href="{{ route('admin.materials.create', array_filter(['section_id' => $selectedSection?->id, 'topic_id' => $selectedTopic?->id, 'workspace' => 1])) }}"><svg data-icon="plus"></svg> Tambah Materi</a>
  </header>
  @if($sections->isEmpty())
    <div class="alert alert--warn"><div><strong>Belum ada bidang.</strong> <a href="{{ route('admin.sections.create') }}">Buat bidang terlebih dahulu</a> sebelum menambahkan topik dan materi.</div></div>
  @endif
  @include('Dashboard.Admin.materials._navigation')
  <div class="content-workspace">
    <aside class="content-topic-panel card" aria-label="Navigasi topik">
      <div class="content-panel-heading"><div><h2>Topik</h2><p>{{ $selectedSection?->name ?? 'Semua bidang' }}</p></div><a class="content-icon-button" href="{{ route('admin.topics.create', array_filter(['section_id' => $selectedSection?->id, 'workspace' => 1])) }}" aria-label="Tambah topik" title="Tambah topik"><svg data-icon="plus"></svg></a></div>
      <form method="GET" action="{{ route('admin.materials.index') }}" class="content-topic-field">
        <label for="topic-section">Bidang</label>
        <select id="topic-section" name="section_id" class="select" onchange="this.form.requestSubmit()">
          <option value="">Semua bidang</option>
          @foreach($sections as $section)
            <option value="{{ $section->id }}" @selected($selectedSection?->id === $section->id)>{{ $section->name }} · {{ $section->topics_count }} topik{{ $section->is_active ? '' : ' · nonaktif' }}</option>
          @endforeach
        </select>
        <button class="btn btn--ghost btn--sm" type="submit">Tampilkan</button>
      </form>
      <form method="GET" action="{{ route('admin.materials.index') }}" class="content-topic-search">
        @foreach(['section_id', 'topic_id', 'q', 'type', 'status'] as $key)
          @if($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
        @endforeach
        <div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" type="search" name="topic_q" value="{{ $filters['topic_q'] }}" placeholder="Cari topik..." aria-label="Cari topik"></div>
        <button class="content-icon-button" type="submit" aria-label="Cari dalam topik"><svg data-icon="arrow-right"></svg></button>
      </form>
      <nav class="content-topic-list" aria-label="Pilih topik">
        <a class="content-topic-link {{ ! $selectedTopic ? 'is-active' : '' }}" href="{{ route('admin.materials.index', array_filter(['section_id' => $selectedSection?->id])) }}" @unless($selectedTopic) aria-current="page" @endunless><svg data-icon="folder"></svg><span><strong>Semua materi</strong><small>{{ $selectedSection ? 'Dalam bidang ini' : 'Lintas bidang dan topik' }}</small></span></a>
        @if($selectedTopic && ! $topics->getCollection()->contains('id', $selectedTopic->id))
          <a class="content-topic-link is-active" href="{{ route('admin.materials.index', ['section_id' => $selectedTopic->section_id, 'topic_id' => $selectedTopic->id]) }}" aria-current="page"><svg data-icon="layers"></svg><span><strong>{{ $selectedTopic->name }}</strong><small>Sedang dibuka</small></span></a>
        @endif
        @foreach($topics as $topic)
          <a class="content-topic-link {{ $selectedTopic?->id === $topic->id ? 'is-active' : '' }}" href="{{ route('admin.materials.index', array_filter(['section_id' => $topic->section_id, 'topic_id' => $topic->id, 'topic_page' => $topics->currentPage() > 1 ? $topics->currentPage() : null, 'topic_q' => $filters['topic_q']])) }}" @if($selectedTopic?->id === $topic->id) aria-current="page" @endif>
            <svg data-icon="{{ $topic->is_active ? 'layers' : 'pause' }}"></svg><span><strong>{{ $topic->name }}</strong><small>{{ $selectedSection ? ($topic->is_active ? 'Aktif' : 'Nonaktif') : $topic->section->name }} · {{ $topic->materials_count }} materi · {{ $topic->quizzes_count }} latihan</small></span>
          </a>
        @endforeach
      </nav>
      @if($topics->isEmpty())<p class="content-panel-note">{{ $filters['topic_q'] ? 'Topik tidak ditemukan. Coba kata lain.' : 'Belum ada topik pada bidang ini.' }}</p>@endif
      @if($topics->hasPages())<div class="content-topic-pager"><span>{{ $topics->currentPage() }} / {{ $topics->lastPage() }} halaman topik</span><div>
        @if($topics->previousPageUrl())<a class="content-icon-button" href="{{ $topics->previousPageUrl() }}" aria-label="Topik sebelumnya"><svg data-icon="chevron-left"></svg></a>@endif
        @if($topics->nextPageUrl())<a class="content-icon-button" href="{{ $topics->nextPageUrl() }}" aria-label="Topik berikutnya"><svg data-icon="chevron-right"></svg></a>@endif
      </div></div>@endif
      <a class="content-directory-link" href="{{ route('admin.topics.index', array_filter(['section_id' => $selectedSection?->id])) }}">Kelola semua topik <svg data-icon="arrow-right"></svg></a>
    </aside>
    <section class="content-material-panel card">
      <div class="content-panel-heading content-panel-heading--main">
        <div><div class="content-location">{{ $selectedSection?->name ?? 'Semua bidang' }} @if($selectedTopic)<svg data-icon="chevron-right"></svg> Topik @endif</div><h2>{{ $selectedTopic?->name ?? 'Semua materi' }}</h2><p>{{ $stats['total'] }} materi · {{ $stats['published'] }} terbit · {{ $stats['draft'] }} draft · {{ $stats['premium'] }} premium</p></div>
        @if($selectedTopic)<a class="btn btn--ghost btn--sm" href="{{ route('admin.topics.edit', [$selectedTopic, 'workspace' => 1]) }}"><svg data-icon="pencil"></svg> Edit Topik</a>@endif
      </div>
      @if($selectedTopic && ! $selectedTopic->is_active || $selectedSection && ! $selectedSection->is_active)
        <div class="content-visibility-note"><svg data-icon="info"></svg> {{ $selectedTopic && ! $selectedTopic->is_active ? 'Topik ini nonaktif.' : 'Bidang ini nonaktif.' }} Materi di dalamnya tidak terlihat oleh peserta, meskipun berstatus Terbit.</div>
      @endif
      <form method="GET" action="{{ route('admin.materials.index') }}" class="content-material-filters">
        @foreach(['section_id', 'topic_id', 'topic_q'] as $key)
          @if($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
        @endforeach
        <div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari judul atau ringkasan..." aria-label="Cari materi"></div>
        <select class="select" name="type" aria-label="Filter format"><option value="">Semua format</option>@foreach($types as $type)<option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->shortLabel() }}</option>@endforeach</select>
        <select class="select" name="status" aria-label="Filter status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        <button class="btn btn--ghost btn--sm" type="submit">Cari</button>
        @if($filters['q'] || $filters['type'] || $filters['status'])<a class="content-filter-reset" href="{{ route('admin.materials.index', array_filter(['section_id' => $selectedSection?->id, 'topic_id' => $selectedTopic?->id])) }}">Reset filter</a>@endif
      </form>
      <div class="content-material-list">
        @forelse($materials as $material)
          <article class="content-material-row">
            <span class="content-material-icon {{ $material->type->value === 'video' ? 'is-video' : '' }}"><svg data-icon="{{ $material->type->icon() }}"></svg></span>
            <div class="content-material-info"><a class="content-material-title" href="{{ route('admin.materials.edit', [$material, 'workspace' => 1]) }}">{{ $material->title }}</a>
              <div class="content-material-meta"><span>{{ $material->type->shortLabel() }}</span><span>{{ $material->type->value === 'pdf' ? ($material->page_count ? $material->page_count . ' halaman' : ($material->humanFileSize() ?? 'Belum ada berkas')) : ($material->humanDuration() ?? 'Durasi belum diisi') }}</span><span>{{ $material->access_level->label() }}</span></div>
              @unless($selectedTopic)<div class="content-material-path">{{ $material->section?->name ?? 'Bidang terhapus' }} / {{ $material->topic?->name ?? 'Topik terhapus' }}</div>@endunless
            </div>
            <span class="badge {{ $material->isPublished() ? 'badge--mint' : 'badge--amber' }} badge--flat">{{ $material->isPublished() ? 'Terbit' : 'Draft' }}</span>
            <div class="content-row-actions">
              <a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.edit', [$material, 'workspace' => 1]) }}" aria-label="Edit {{ $material->title }}">Edit</a>
              <details class="content-menu"><summary class="content-icon-button" aria-label="Tindakan untuk {{ $material->title }}" title="Tindakan lainnya"><svg data-icon="more"></svg></summary><div class="content-menu__items">
                <a href="{{ route('materials.show', $material) }}" target="_blank" rel="noopener"><svg data-icon="external"></svg> Pratinjau</a>
                <form method="POST" action="{{ route('admin.materials.status', $material) }}">@csrf @method('PATCH')<input type="hidden" name="workspace" value="1"><button type="submit"><svg data-icon="{{ $material->isPublished() ? 'pause' : 'send' }}"></svg>{{ $material->isPublished() ? 'Jadikan draft' : 'Terbitkan' }}</button></form>
                <form method="POST" action="{{ route('admin.materials.destroy', $material) }}" data-content-confirm data-confirm-title="Arsipkan materi?" data-confirm-body="Materi &quot;{{ $material->title }}&quot; akan disembunyikan. Berkas PDF tetap disimpan." data-confirm-ok="Ya, arsipkan">@csrf @method('DELETE')<input type="hidden" name="workspace" value="1"><button type="submit" class="is-danger"><svg data-icon="trash"></svg> Arsipkan materi</button></form>
              </div></details>
            </div>
          </article>
        @empty
          <div class="empty content-empty"><span class="empty__icon"><svg data-icon="book"></svg></span><strong>{{ $filters['q'] || $filters['type'] || $filters['status'] ? 'Materi tidak ditemukan' : 'Belum ada materi' }}</strong><p>{{ $filters['q'] || $filters['type'] || $filters['status'] ? 'Coba kata pencarian atau filter lain.' : 'Tambahkan PDF atau video ke topik ini untuk mulai menyusun pembelajaran.' }}</p>
            <a class="btn btn--primary btn--sm" href="{{ route('admin.materials.create', array_filter(['section_id' => $selectedSection?->id, 'topic_id' => $selectedTopic?->id, 'workspace' => 1])) }}">Tambah Materi</a>
          </div>
        @endforelse
      </div>
      @if($materials->total() > 0)<footer class="content-list-footer"><span>Menampilkan {{ $materials->firstItem() }}–{{ $materials->lastItem() }} dari {{ $materials->total() }} materi</span><x-pager :paginator="$materials" /></footer>@endif
      @if($selectedTopic)
        <section class="content-topic-quizzes" aria-label="Latihan pada {{ $selectedTopic->name }}">
          <div class="content-panel-heading content-panel-heading--main"><div><div class="content-location"><svg data-icon="list-checks"></svg> Evaluasi pembelajaran</div><h3>Latihan Topik</h3><p>{{ $selectedTopic->quizzes_count }} latihan · Atur soal dan syarat penyelesaian materi.</p></div><a class="btn btn--primary btn--sm" href="{{ route('admin.materials.quizzes.create', ['topic_id' => $selectedTopic->id]) }}"><svg data-icon="plus"></svg> Tambah Latihan</a></div>
          <div class="content-exam-list">
            @forelse($topicQuizzes as $exam)
              @include('Dashboard.Admin.exams._item', ['exam' => $exam])
            @empty
              <p class="content-panel-note">Belum ada latihan pada topik ini. Tambahkan latihan untuk menguji pemahaman peserta.</p>
            @endforelse
          </div>
          @if($selectedTopic->quizzes_count > 0)<a class="content-directory-link" href="{{ route('admin.materials.quizzes.index', ['topic_id' => $selectedTopic->id]) }}">Kelola semua latihan topik <svg data-icon="arrow-right"></svg></a>@endif
        </section>
      @endif
    </section>
  </div>
@endsection
