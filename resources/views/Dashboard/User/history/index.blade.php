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
      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table" style="min-width:900px"><thead><tr><th>Aktivitas</th><th>Jenis</th><th class="num">Soal</th><th class="num">Benar / Salah</th><th>Tanggal</th><th class="num">Durasi</th><th class="num">Nilai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
          @forelse ($attempts as $attempt)
            <tr>
              <td><strong style="color:var(--ink)">{{ $attempt->title }}</strong><span class="text-xs text-soft" style="display:block">{{ implode(' · ', array_unique(array_column($attempt->question_snapshot['questions'], 'section'))) }}</span></td>
              <td>{{ $attempt->kind === 'quiz' ? 'Latihan' : 'Tryout' }}</td>
              <td class="num">{{ count($attempt->question_snapshot['questions']) }}</td>
              <td class="num">{{ $attempt->status === 'completed' ? $attempt->correct_count.' / '.$attempt->wrong_count : '—' }}@if ($attempt->status === 'completed' && $attempt->weighted_answered_count)<span class="text-xs text-soft" style="display:block">{{ $attempt->weighted_answered_count }} berbobot dijawab</span>@endif</td>
              <td>{{ ($attempt->submitted_at ?? $attempt->started_at)->translatedFormat('d M Y H:i') }}</td>
              <td class="num">{{ $attempt->status === 'completed' ? floor($attempt->duration_seconds / 60).' menit' : 'Berjalan' }}</td>
              <td class="num">{{ $attempt->status === 'completed' ? $attempt->score.' / '.$attempt->max_score : count($attempt->answers ?? []).' dijawab' }}
                @if ($attempt->kind === 'tryout' && $attempt->status === 'completed')
                  @foreach ($attempt->sectionGrades() as $grade)<span class="text-xs text-soft" style="display:block">{{ $grade['section'] }} {{ $grade['score'] }} · {{ $grade['passed'] ? 'PASS' : 'FAIL' }}</span>@endforeach
                @endif
              </td>
              <td><span class="badge badge--{{ $attempt->status === 'completed' && ($attempt->kind === 'quiz' || $attempt->hasPassed()) ? 'mint' : 'amber' }} badge--flat">{{ $attempt->status !== 'completed' ? 'Berjalan' : ($attempt->kind === 'tryout' ? ($attempt->hasPassed() ? 'Lulus' : 'Belum Lulus') : 'Selesai') }}</span></td>
              <td><a class="badge badge--primary badge--flat" href="{{ $attempt->status === 'completed' ? $attempt->resultUrl() : $attempt->attemptUrl() }}">{{ $attempt->status === 'completed' ? 'Lihat Hasil' : 'Lanjutkan Sesi' }}</a></td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-soft">Belum ada sesi pada filter ini. <a href="{{ route('quizzes.index') }}">Mulai latihan</a> atau <a href="{{ route('tryouts.index') }}">coba tryout</a>.</td></tr>
          @endforelse
        </tbody></table>
      </div>
      @if ($attempts->hasPages())<div style="padding:1rem 1.5rem;border-top:2px solid var(--border)"><x-pager :paginator="$attempts" /></div>@endif
    </section>
@endsection
