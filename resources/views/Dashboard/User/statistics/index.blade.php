@extends('layouts.user.main')

@section('title', 'Statistik — LONTAR CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Statistik</span></nav>

    <header class="page-head page-head__row">
      <div><span class="eyebrow">Analisis Belajar</span><h1 style="margin-top:.5rem">Statistik Nilai</h1><p>Perkembangan dari sesi yang benar-benar sudah kamu kumpulkan.</p></div>
      <div class="btn-row" style="flex-wrap:wrap">
        <div class="pills" aria-label="Jenis aktivitas">
          <a class="pill {{ $kind === 'tryout' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => 'tryout', 'period' => $period]) }}">Tryout</a>
          <a class="pill {{ $kind === 'quiz' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => 'quiz', 'period' => $period]) }}">Latihan</a>
        </div>
        <div class="pills" aria-label="Rentang waktu">
          <a class="pill {{ $period === '30' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => '30']) }}">30 hari</a>
          <a class="pill {{ $period === '90' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => '90']) }}">3 bulan</a>
          <a class="pill {{ $period === 'all' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => 'all']) }}">Semua</a>
        </div>
      </div>
    </header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat reveal"><span class="stat__icon"><svg data-icon="trending-up"></svg></span><div><div class="stat__label">Nilai Terbaru</div><div class="stat__value">{{ $summary['latest']?->score ?? '—' }}</div><div class="stat__delta text-soft">{{ $summary['latest'] ? 'Maks. '.$summary['latest']->max_score : 'Belum ada sesi' }}</div></div></article>
      <article class="stat reveal" data-delay="70"><span class="stat__icon ico--amber"><svg data-icon="crown"></svg></span><div><div class="stat__label">Nilai Tertinggi</div><div class="stat__value">{{ $summary['highest']?->score ?? '—' }}</div><div class="stat__delta text-soft">{{ $summary['highest']?->title ?? 'Belum ada sesi' }}</div></div></article>
      <article class="stat reveal" data-delay="140"><span class="stat__icon ico--pink"><svg data-icon="gauge"></svg></span><div><div class="stat__label">Rata-rata Nilai</div><div class="stat__value">{{ $summary['averageScore'] ?? '—' }}</div><div class="stat__delta text-soft">{{ $summary['count'] }} sesi selesai</div></div></article>
      <article class="stat reveal" data-delay="210"><span class="stat__icon ico--mint"><svg data-icon="target"></svg></span><div><div class="stat__label">Akurasi Jawaban</div><div class="stat__value">{{ $summary['accuracy'] !== null ? $summary['accuracy'].'%' : '—' }}</div><div class="stat__delta text-soft">{{ $summary['correct'] }} benar dari {{ $summary['correct'] + $summary['wrong'] }} dijawab</div></div></article>
    </section>

    @if ($summary['count'] === 0)
      <section class="card card--pop" style="text-align:center;padding:2.5rem">
        <span class="card__icon ico--primary" style="position:static;margin:0 auto 1rem"><svg data-icon="chart"></svg></span>
        <h2 class="card__title">Belum ada data {{ $kind === 'quiz' ? 'latihan' : 'tryout' }} pada periode ini</h2>
        <p class="text-sm text-soft" style="margin:.5rem 0 1.25rem">Selesaikan sesi untuk mulai melihat grafik dan rincian nilai.</p>
        <a class="btn btn--primary" href="{{ route($kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}">{{ $kind === 'quiz' ? 'Buka Latihan' : 'Buka Tryout' }}</a>
      </section>
    @else
      <section class="card" style="margin-bottom:1.5rem">
        <div class="card__head"><div><div class="card__title">Grafik Perkembangan Nilai</div><div class="card__sub">{{ count($trendChart['values']) }} sesi terbaru dalam rentang yang dipilih</div></div></div>
        <div style="height:300px" data-chart='@json($trendChart)'></div>
        <p class="text-xs text-soft" style="margin-top:.85rem">Garis merah muda menandai rata-rata nilai sesi yang ditampilkan.</p>
      </section>

      <div class="panes" style="--gap:1.5rem;margin-bottom:1.5rem">
        <section class="card"><div class="card__head"><div><div class="card__title">Akurasi per Bagian</div><div class="card__sub">Persentase benar dari soal yang dijawab</div></div></div><div style="height:250px" data-chart='@json($sectionChart)'></div></section>
        <section class="card">
          <div class="card__head"><div><div class="card__title">Komposisi Jawaban</div><div class="card__sub">{{ $summary['count'] }} sesi pada periode ini</div></div></div>
          <div style="width:200px;height:200px;margin:0 auto 1.25rem" data-chart='@json($compositionChart)'></div>
          <div class="legend"><div class="legend__item"><span class="legend__swatch" style="background:var(--pop-mint)"></span> Benar — {{ $summary['correct'] }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--pop-coral)"></span> Salah — {{ $summary['wrong'] }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--border-strong)"></span> Kosong — {{ $summary['blank'] }}</div></div>
        </section>
      </div>

      <section class="card card--pad-0" style="margin-bottom:1.5rem">
        <div style="padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)"><div class="card__title">Rincian per Bagian</div><div class="card__sub">Akumulasi jawaban dalam rentang yang dipilih</div></div>
        <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none"><table class="table"><thead><tr><th>Bagian</th><th class="num">Soal</th><th class="num">Benar</th><th class="num">Salah</th><th class="num">Kosong</th><th class="num">Akurasi</th><th class="num">Skor</th></tr></thead><tbody>
          @foreach ($summary['sections'] as $section)
            <tr><td><strong style="color:var(--ink)">{{ $section['section'] }}</strong></td><td class="num">{{ $section['total'] }}</td><td class="num">{{ $section['correct'] }}</td><td class="num">{{ $section['wrong'] }}</td><td class="num">{{ $section['blank'] }}</td><td class="num">{{ $section['correct'] + $section['wrong'] > 0 ? round($section['correct'] / ($section['correct'] + $section['wrong']) * 100) : 0 }}%</td><td class="num">{{ $section['score'] }} / {{ $section['max'] }}</td></tr>
          @endforeach
        </tbody></table></div>
      </section>

      <section class="card">
        <div class="card__head"><div><div class="card__title">Sesi Terbaru</div><div class="card__sub">Nilai yang tersimpan pada akunmu</div></div><a class="btn btn--ghost btn--sm" href="{{ route('history.index', ['kind' => $kind]) }}">Riwayat lengkap<svg data-icon="arrow-right"></svg></a></div>
        <div class="stack" style="--stack:.75rem">
          @foreach ($summary['recent'] as $attempt)
            <a class="card card--tight card--dashed flex items-center justify-between" href="{{ $attempt->resultUrl() }}"><span><strong style="color:var(--ink)">{{ $attempt->title }}</strong><span class="text-xs text-soft" style="display:block">{{ $attempt->submitted_at->translatedFormat('d M Y H:i') }}</span></span><strong style="color:var(--primary-700)">{{ $attempt->score }} / {{ $attempt->max_score }}</strong></a>
          @endforeach
        </div>
      </section>
    @endif
@endsection
