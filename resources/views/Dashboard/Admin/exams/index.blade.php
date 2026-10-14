@extends('layouts.admin.main')
@section('title', ($isQuiz ? 'Latihan Materi' : 'Tryout').' — Admin Ganesha Nusa')
@section('content')
  @include('components.flash')
  <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route($isQuiz ? 'admin.materials.index' : 'admin.dashboard') }}">{{ $isQuiz ? 'Materi' : 'Admin Dashboard' }}</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ $isQuiz ? 'Latihan' : 'Tryout' }}</span></nav>
  <header class="page-head content-page-head">
    <div><span class="eyebrow">{{ $isQuiz ? 'Ruang Kerja Konten' : 'Simulasi Ujian' }}</span><h1>{{ $isQuiz ? 'Materi & Topik' : 'Tryout' }}</h1><p>{{ $isQuiz ? 'Kelola latihan sesuai topik, pilih soal, dan atur kapan peserta bisa mulai mengerjakan.' : 'Susun simulasi CAT, atur durasi dan penilaian, lalu terbitkan untuk peserta.' }}</p></div>
    <a class="btn btn--primary" href="{{ route($routePrefix.'.create', $isQuiz && $selectedTopic ? ['topic_id' => $selectedTopic->id] : []) }}"><svg data-icon="plus"></svg> {{ $isQuiz ? 'Tambah Latihan' : 'Tambah Tryout' }}</a>
  </header>
  @if($isQuiz) @include('Dashboard.Admin.materials._navigation') @endif
  <section class="card content-directory">
    <div class="content-panel-heading content-panel-heading--main"><div><div class="content-location">{{ $isQuiz ? ($selectedSection?->name ?? 'Semua bidang') : 'Simulasi CAT' }}</div><h2>{{ $isQuiz ? ($selectedTopic?->name ?? 'Semua latihan') : 'Daftar Tryout' }}</h2><p>{{ $exams->total() }} {{ $isQuiz ? 'latihan' : 'tryout' }} ditemukan{{ $isQuiz ? ' · Latihan umum tetap bisa dihubungkan ke topik melalui pengaturan.' : '' }}</p></div>
      @if($isQuiz && $selectedTopic)<a class="btn btn--ghost btn--sm" href="{{ route('admin.materials.index', ['section_id' => $selectedTopic->section_id, 'topic_id' => $selectedTopic->id]) }}"><svg data-icon="book"></svg> Materi Topik</a>@endif
    </div>
    <form class="content-material-filters content-exam-filters" method="GET" action="{{ route($routePrefix.'.index') }}">
      <div class="input-group"><span class="input-group__icon"><svg data-icon="search"></svg></span><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $isQuiz ? 'Cari nama latihan...' : 'Cari nama tryout...' }}" aria-label="{{ $isQuiz ? 'Cari latihan' : 'Cari tryout' }}"></div>
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
        <div class="empty content-empty"><span class="empty__icon"><svg data-icon="{{ $isQuiz ? 'list-checks' : 'timer' }}"></svg></span><strong>{{ $isQuiz ? 'Belum ada latihan yang sesuai' : 'Belum ada tryout yang sesuai' }}</strong><p>Coba filter lain atau tambahkan {{ $isQuiz ? 'latihan' : 'tryout' }} baru.</p></div>
      @endforelse
    </div>
    @if($exams->total() > 0)<footer class="content-list-footer"><span>Menampilkan {{ $exams->firstItem() }}–{{ $exams->lastItem() }} dari {{ $exams->total() }} {{ $isQuiz ? 'latihan' : 'tryout' }}</span><x-pager :paginator="$exams" /></footer>@endif
  </section>
@endsection