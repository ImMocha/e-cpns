@extends('layouts.admin.main')
@section('title', $kind->label().' — Admin Ganesha Nusa')
@section('content')
  @include('components.flash')
  <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route($isQuiz ? 'admin.materials.index' : 'admin.dashboard') }}">{{ $isQuiz ? 'Materi' : 'Admin Dashboard' }}</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $kind->label() }}</span></nav>
  <header class="page-head content-page-head">
    <div><span class="eyebrow">{{ $isQuiz ? 'Ruang Kerja Konten' : $kind->label() }}</span><h1>{{ $isQuiz ? 'Materi & Topik' : $kind->label() }}</h1><p>{{ $isQuiz ? 'Kelola latihan sesuai topik, pilih soal, dan atur kapan peserta bisa mulai mengerjakan.' : ($kind === \App\Enums\ExamKind::Practice ? 'Susun latihan soal, atur durasi dan penilaian, lalu terbitkan untuk peserta.' : 'Susun simulasi CAT, atur durasi dan penilaian, lalu terbitkan untuk peserta.') }}</p></div>
    <a class="btn btn--primary" href="{{ route($routePrefix.'.create', $isQuiz && $selectedTopic ? ['topic_id' => $selectedTopic->id] : []) }}"><svg data-icon="plus"></svg> Tambah {{ $kind->label() }}</a>
  </header>
  @if($isQuiz) @include('Dashboard.Admin.materials._navigation') @endif
  @unless($isQuiz)<p class="text-sm text-soft question-section"><strong>Gratis Bersyarat</strong> berarti peserta wajib memenuhi syarat dan upload bukti untuk diverifikasi admin. Pilih level akses ini di pengaturan sesi. Label <strong>Gratis</strong> berarti sesi dapat langsung dikerjakan.</p>@endunless
  <section class="card content-directory">
    <div class="content-panel-heading content-panel-heading--main"><div><div class="content-location">{{ $isQuiz ? ($selectedSection?->name ?? 'Semua bidang') : $kind->label() }}</div><h2>{{ $isQuiz ? ($selectedTopic?->name ?? 'Semua latihan') : 'Daftar '.$kind->label() }}</h2><p>{{ $exams->total() }} {{ strtolower($kind->label()) }} ditemukan{{ $isQuiz ? ' · Latihan Soal umum tetap bisa dihubungkan ke topik melalui pengaturan.' : '' }}</p></div>
      @if($isQuiz && $selectedTopic)<a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.index', ['section_id' => $selectedTopic->section_id, 'topic_id' => $selectedTopic->id]) }}"><svg data-icon="book"></svg> Materi Topik</a>@endif
    </div>
    <form class="content-material-filters content-exam-filters" method="GET" action="{{ route($routePrefix.'.index') }}">
      <div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama {{ strtolower($kind->label()) }}..." aria-label="Cari {{ strtolower($kind->label()) }}"></div>
      @if($isQuiz)
        <select class="select" name="section_id" aria-label="Filter bidang" onchange="this.form.elements.topic_id.value = ''"><option value="">Semua bidang</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected($selectedSection?->id === $section->id)>{{ $section->name }}</option>@endforeach</select>
        <select class="select content-exam-topic-filter" name="topic_id" aria-label="Filter topik"><option value="">Semua topik</option>@foreach($topics as $topic)<option value="{{ $topic->id }}" @selected($selectedTopic?->id === $topic->id)>{{ $selectedSection ? '' : $topic->section->name.' · ' }}{{ $topic->name }}</option>@endforeach</select>
      @endif
      <select class="select" name="status" aria-label="Filter status"><option value="">Semua status</option><option value="published" @selected(request('status') === 'published')>Terbit</option><option value="draft" @selected(request('status') === 'draft')>Draft</option></select>
      <button class="btn btn--ghost btn--sm" type="submit">Cari</button>
      @if(request('q') || request('status'))<a class="content-filter-reset" href="{{ route($routePrefix.'.index', array_filter(['section_id' => $selectedSection?->id, 'topic_id' => $selectedTopic?->id])) }}">Reset filter</a>@endif
    </form>
    <div class="content-exam-list">
      @forelse($exams as $exam)
        @include('Dashboard.Admin.exams._item', ['exam' => $exam])
      @empty
        <div class="empty content-empty"><span class="empty__icon"><svg data-icon="{{ $isQuiz ? 'list-checks' : 'timer' }}"></svg></span><strong>Belum ada {{ strtolower($kind->label()) }} yang sesuai</strong><p>Coba filter lain atau tambahkan {{ strtolower($kind->label()) }} baru.</p></div>
      @endforelse
    </div>
    @if($exams->total() > 0)<footer class="content-list-footer"><span>Menampilkan {{ $exams->firstItem() }}–{{ $exams->lastItem() }} dari {{ $exams->total() }} {{ strtolower($kind->label()) }}</span><x-pager :paginator="$exams" /></footer>@endif
  </section>
@endsection
