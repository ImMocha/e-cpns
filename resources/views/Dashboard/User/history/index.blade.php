@extends('layouts.user.main')

@section('title', 'Riwayat — Ganesha Nusa CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Riwayat</span></nav>
    <header class="page-head page-head__row"><div><span class="eyebrow">Semua Aktivitas</span><h1 style="margin-top:.5rem">Riwayat Pengerjaan</h1><p>Lanjutkan sesi aktif atau buka hasil dan kunci jawaban. Pembahasan tersedia dengan paket berbayar aktif.</p></div></header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat"><span class="stat__icon"><svg data-icon="timer"></svg></span><div><div class="stat__label">Total Tryout</div><div class="stat__value">{{ $summary['tryoutCount'] }}</div></div></article>
      <article class="stat"><span class="stat__icon ico--pink"><svg data-icon="pen-line"></svg></span><div><div class="stat__label">Total Latihan</div><div class="stat__value">{{ $summary['quizCount'] }}</div></div></article>
      <article class="stat"><span class="stat__icon ico--amber"><svg data-icon="gauge"></svg></span><div><div class="stat__label">Total Waktu</div><div class="stat__value">{{ floor($summary['totalSeconds'] / 60) }} mnt</div></div></article>
      <article class="stat"><span class="stat__icon ico--mint"><svg data-icon="target"></svg></span><div><div class="stat__label">Akurasi</div><div class="stat__value">{{ $summary['accuracy'] !== null ? $summary['accuracy'].'%' : '—' }}</div></div></article>
    </section>

    <section class="card card--pad-0">
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)">
        <div class="pills" aria-label="Filter jenis aktivitas">
          <a class="pill {{ $kind === null ? 'is-active' : '' }}" href="{{ route('history.index', ['status' => $status]) }}">Semua</a>
          <a class="pill {{ $kind === 'tryout' ? 'is-active' : '' }}" href="{{ route('history.index', ['kind' => 'tryout', 'status' => $status]) }}">Tryout</a>
          <a class="pill {{ $kind === 'quiz' ? 'is-active' : '' }}" href="{{ route('history.index', ['kind' => 'quiz', 'status' => $status]) }}">Latihan</a>
        </div>
        <span class="text-xs text-soft">{{ $attempts->total() }} sesi ditemukan</span>
        <form method="GET" action="{{ route('history.index') }}">
          @if ($kind)<input type="hidden" name="kind" value="{{ $kind }}">@endif
          <select name="status" class="input" aria-label="Status pengerjaan" onchange="this.form.submit()">
            <option value="">Semua status</option><option value="in_progress" @selected($status === 'in_progress')>Sedang dikerjakan</option><option value="completed" @selected($status === 'completed')>Selesai</option>
          </select>
        </form>
      </div>
      <div class="history-list">
          @forelse ($attempts as $attempt)
            <article class="history-item">
              <div class="history-item-main">
                <span class="history-item-icon"><svg data-icon="{{ $attempt->kind === 'quiz' ? 'pen-line' : 'timer' }}"></svg></span>
                <div class="history-item-info">
                  <div class="history-item-labels"><span class="history-kind">{{ $attempt->kind === 'quiz' ? 'Latihan' : 'Tryout CAT' }}</span><span class="badge badge--{{ $attempt->status === 'completed' && ($attempt->kind === 'quiz' || $attempt->hasPassed()) ? 'mint' : 'amber' }} badge--flat">{{ $attempt->status !== 'completed' ? 'Berjalan' : ($attempt->kind === 'tryout' ? ($attempt->hasPassed() ? 'Lulus' : 'Belum Lulus') : 'Selesai') }}</span></div>
                  <h2>{{ $attempt->title }}</h2>
                  <p class="history-item-meta"><span>{{ ($attempt->submitted_at ?? $attempt->started_at)->translatedFormat('d M Y · H:i') }}</span><span>{{ count($attempt->question_snapshot['questions']) }} soal</span><span>{{ $attempt->status === 'completed' ? floor($attempt->duration_seconds / 60).' menit' : 'Sedang dikerjakan' }}</span></p>
                </div>
                <div class="history-item-score"><small>{{ $attempt->status === 'completed' ? 'Nilai akhir' : 'Progres' }}</small><strong>{{ $attempt->status === 'completed' ? $attempt->score.' / '.$attempt->max_score : count($attempt->answers ?? []).' dijawab' }}</strong></div>
                <a class="btn {{ $attempt->status === 'completed' ? 'btn--ghost' : 'btn--primary' }} btn--sm" href="{{ $attempt->status === 'completed' ? $attempt->resultUrl() : $attempt->attemptUrl() }}">{{ $attempt->status === 'completed' ? 'Lihat Hasil' : 'Lanjutkan Sesi' }}<svg data-icon="arrow-right"></svg></a>
              </div>
              <details class="history-item-details">
                <summary>Rincian sesi <svg data-icon="chevron-down" aria-hidden="true"></svg></summary>
                <div class="history-item-breakdown">
                  <p><strong>Bidang</strong><span>{{ implode(' · ', array_unique(array_column($attempt->question_snapshot['questions'], 'section'))) }}</span></p>
                  @if ($attempt->status === 'completed')
                    <p><strong>Benar / Salah</strong><span>{{ $attempt->correct_count.' / '.$attempt->wrong_count }}</span></p>
                    <p><strong>Kosong</strong><span>{{ $attempt->blank_count }} soal</span></p>
                    @if ($attempt->weighted_answered_count)<p><strong>Soal berbobot</strong><span>{{ $attempt->weighted_answered_count }} berbobot dijawab</span></p>@endif
                    @if ($attempt->kind === 'tryout')
                      @foreach ($attempt->sectionGrades() as $grade)<p><strong>{{ $grade['section'] }}</strong><span>{{ $grade['score'] }} · {{ $grade['passed'] ? 'PASS' : 'FAIL' }}</span></p>@endforeach
                    @endif
                  @else
                    <p><strong>Status</strong><span>Jawaban tersimpan. Lanjutkan sesi untuk menyelesaikannya.</span></p>
                  @endif
                </div>
              </details>
            </article>
          @empty
            <div class="empty"><span class="empty__icon"><svg data-icon="history"></svg></span><strong>Belum ada sesi pada filter ini.</strong><p><a href="{{ route('quizzes.index') }}">Mulai latihan</a> atau <a href="{{ route('tryouts.index') }}">coba tryout</a> untuk mencatat perkembanganmu.</p></div>
          @endforelse
      </div>
      @if ($attempts->hasPages())<div style="padding:1rem 1.5rem;border-top:2px solid var(--border)"><x-pager :paginator="$attempts" /></div>@endif
    </section>
@endsection
