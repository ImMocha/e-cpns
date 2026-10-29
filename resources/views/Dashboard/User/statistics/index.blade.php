@extends('layouts.user.main')

@section('title', 'Statistik — Ganesha Nusa CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Statistik</span></nav>

    <header class="page-head page-head__row">
      <div><span class="eyebrow">Analisis Belajar</span><h1 style="margin-top:.5rem">Statistik Nilai</h1><p>Perkembangan dari sesi yang benar-benar sudah kamu kumpulkan.</p></div>
      <div class="btn-row" style="flex-wrap:wrap">
        <div class="pills" aria-label="Jenis aktivitas">
          <a class="pill {{ $kind === 'tryout' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => 'tryout', 'period' => $period]) }}">Tryout</a>
          <a class="pill {{ $kind === 'quiz' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => 'quiz', 'period' => $period]) }}">Latihan Topik</a>
          <a class="pill {{ $kind === 'practice' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => 'practice', 'period' => $period]) }}">Latihan Soal</a>
        </div>
        <div class="pills" aria-label="Rentang waktu">
          <a class="pill {{ $period === '30' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => '30']) }}">30 hari</a>
          <a class="pill {{ $period === '90' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => '90']) }}">3 bulan</a>
          <a class="pill {{ $period === 'all' ? 'is-active' : '' }}" href="{{ route('statistics.index', ['kind' => $kind, 'period' => 'all']) }}">Semua</a>
        </div>
      </div>
    </header>

    @if (! $contentAccessible)
        <x-feature-lock title="Analisa pembelajaran dan statistik terkunci" description="Halaman statistik dapat kamu lihat. Aktifkan Premium untuk membuka grafik perkembangan nilai, rata-rata skor, akurasi jawaban, dan analisa per bidang.">
            <div class="grid grid-cols-1 sm:grid-cols-2" style="--gap:.75rem">
                @foreach (['Perkembangan nilai', 'Rata-rata skor', 'Akurasi jawaban', 'Analisa per bidang'] as $feature)
                    <div class="card card--tight card--dashed flex items-center gap-3"><svg data-icon="lock" aria-hidden="true"></svg><span class="text-sm">{{ $feature }}</span></div>
                @endforeach
            </div>
        </x-feature-lock>
    @else
    <section class="statistics-summary" aria-label="Ringkasan nilai">
        <div class="statistics-summary__grid">
            <article class="statistics-metric reveal">
                <div class="statistics-metric__heading"><span>Nilai Terbaru</span><span class="statistics-metric__icon" aria-hidden="true"><svg data-icon="trending-up"></svg></span></div>
                <strong class="statistics-metric__value">{{ $summary['latest']?->score ?? '—' }}</strong>
                <p class="statistics-metric__detail">{{ $summary['latest'] ? 'Maks. '.$summary['latest']->max_score : 'Belum ada sesi' }}</p>
            </article>
            <article class="statistics-metric statistics-metric--highest reveal" data-delay="70">
                <div class="statistics-metric__heading"><span>Nilai Tertinggi</span><span class="statistics-metric__icon" aria-hidden="true"><svg data-icon="crown"></svg></span></div>
                <strong class="statistics-metric__value">{{ $summary['highest']?->score ?? '—' }}</strong>
                <p class="statistics-metric__detail">{{ $summary['highest']?->title ?? 'Belum ada sesi' }}</p>
            </article>
            <article class="statistics-metric statistics-metric--average reveal" data-delay="140">
                <div class="statistics-metric__heading"><span>Rata-rata Nilai</span><span class="statistics-metric__icon" aria-hidden="true"><svg data-icon="gauge"></svg></span></div>
                <strong class="statistics-metric__value">{{ $summary['averageScore'] ?? '—' }}</strong>
                <p class="statistics-metric__detail">{{ $summary['count'] }} sesi selesai</p>
            </article>
            <article class="statistics-metric statistics-metric--accuracy reveal" data-delay="210">
                <div class="statistics-metric__heading"><span>Akurasi Jawaban</span><span class="statistics-metric__icon" aria-hidden="true"><svg data-icon="target"></svg></span></div>
                <strong class="statistics-metric__value">{{ $summary['accuracy'] !== null ? $summary['accuracy'].'%' : '—' }}</strong>
                <p class="statistics-metric__detail">{{ $summary['correct'] }} benar dari {{ $summary['correct'] + $summary['wrong'] }} jawaban · akumulasi {{ $summary['count'] }} sesi</p>
            </article>
        </div>
        <p class="statistics-summary__note"><svg data-icon="info" aria-hidden="true"></svg><span>Akurasi dihitung dari soal benar/salah. Tidak termasuk soal berbobot seperti TKP.</span></p>
    </section>

    @if ($summary['count'] === 0)
      <section class="card card--pop" style="text-align:center;padding:2.5rem">
        <span class="card__icon ico--primary" style="position:static;margin:0 auto 1rem"><svg data-icon="chart"></svg></span>
        <h2 class="card__title">Belum ada data {{ strtolower(\App\Enums\ExamKind::from($kind)->label()) }} pada periode ini</h2>
        <p class="text-sm text-soft" style="margin:.5rem 0 1.25rem">Selesaikan sesi untuk mulai melihat grafik dan rincian nilai.</p>
        <a class="btn btn--primary" href="{{ route(\App\Enums\ExamKind::from($kind)->catalogRoute()) }}">{{ 'Buka '.\App\Enums\ExamKind::from($kind)->label() }}</a>
      </section>
    @else
      <section class="card" style="margin-bottom:1.5rem">
        <div class="card__head"><div><div class="card__title">Grafik Perkembangan Nilai</div><div class="card__sub">{{ count($trendChart['values']) }} sesi terbaru dalam rentang yang dipilih</div></div></div>
        <div style="height:300px" data-chart='@json($trendChart)'></div>
        <p class="text-xs text-soft" style="margin-top:.85rem">Garis merah muda menandai rata-rata nilai sesi yang ditampilkan.</p>
      </section>

      <div class="panes panes--equal" style="--gap:1.5rem;margin-bottom:1.5rem">
        <section class="card"><div class="card__head"><div><div class="card__title">Skor per Bagian</div><div class="card__sub">Akumulasi poin yang diperoleh pada setiap bagian.</div></div></div><div style="height:250px" data-chart='@json($sectionChart)'></div></section>
        <section class="card">
          <div class="card__head"><div><div class="card__title">Komposisi Jawaban</div><div class="card__sub">{{ $summary['count'] }} sesi pada periode ini</div></div></div>
          <div style="width:200px;height:200px;margin:0 auto 1.25rem" data-chart='@json($compositionChart)'></div>
          <div class="legend"><div class="legend__item"><span class="legend__swatch" style="background:var(--pop-mint)"></span> Benar — {{ $summary['correct'] }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--pop-coral)"></span> Salah — {{ $summary['wrong'] }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--primary)"></span> Berbobot dijawab — {{ $summary['weighted'] }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--border-strong)"></span> Kosong — {{ $summary['blank'] }}</div></div>
        </section>
      </div>

      <section class="card card--pad-0" style="margin-bottom:1.5rem">
        <div style="padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)"><div class="card__title">Rincian per Bagian</div><div class="card__sub">Akumulasi jawaban dalam rentang yang dipilih</div></div>
        <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none"><table class="table"><thead><tr><th>Bagian</th><th class="num">Soal</th><th class="num">Benar</th><th class="num">Salah</th><th class="num">Berbobot dijawab</th><th class="num">Kosong</th><th class="num">Akurasi soal biasa</th><th class="num">Skor</th></tr></thead><tbody>
          @foreach ($summary['sections'] as $section)
            <tr><td><strong style="color:var(--ink)">{{ $section['section'] }}</strong></td><td class="num">{{ $section['total'] }}</td><td class="num">{{ $section['correct'] }}</td><td class="num">{{ $section['wrong'] }}</td><td class="num">{{ $section['weighted'] }}</td><td class="num">{{ $section['blank'] }}</td><td class="num">{{ $section['correct'] + $section['wrong'] > 0 ? round($section['correct'] / ($section['correct'] + $section['wrong']) * 100).'%' : '—' }}</td><td class="num">{{ $section['score'] }} / {{ $section['max'] }}</td></tr>
          @endforeach
        </tbody></table></div>
      </section>

      <section class="card">
        <div class="card__head"><div><div class="card__title">Sesi Terbaru</div><div class="card__sub">Nilai yang tersimpan pada akunmu</div></div><a class="btn btn--ghost btn--sm" href="{{ route('history.index', ['kind' => $kind]) }}">Riwayat lengkap<svg data-icon="arrow-right"></svg></a></div>
        <div class="stack" style="--stack:.75rem">
          @foreach ($summary['recent'] as $attempt)
            <a class="card card--tight card--dashed flex items-center justify-between" href="{{ $attempt->resultUrl() }}"><span><strong style="color:var(--ink)">{{ $attempt->title }}</strong><span class="text-xs text-soft" style="display:block"><x-local-time :value="$attempt->submitted_at" format="d M Y H:i" /></span></span><strong style="color:var(--primary-700)">{{ $attempt->score }} / {{ $attempt->max_score }}</strong></a>
          @endforeach
        </div>
      </section>
    @endif
    @endif
@endsection
