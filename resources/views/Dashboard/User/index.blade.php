@extends('layouts.user.main')

@section('title', 'Dashboard — Ganesha Nusa CPNS Online')

@section('content')
    @php($learningAllowed = $user->canAccessLearningFeatures())
    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow"><x-local-time :value="now()" format="l, d F Y" /></span>
        <h1 style="margin-top:.5rem">Halo, {{ $user->name }}! <span class="float" style="display:inline-block">&#128075;</span></h1>
        <p>{{ $summary['count'] > 0 ? 'Lihat perkembangan dari aktivitas belajar yang sudah kamu selesaikan.' : 'Mulai latihan atau tryout pertamamu untuk melihat perkembangan nilai.' }}</p>
      </div>
      <div class="btn-row">
        @if ($learningAllowed)<a class="btn btn--ghost" href="{{ route('materials.index') }}"><svg data-icon="book"></svg> Belajar</a>@endif
        <a class="btn btn--primary" href="{{ route('tryouts.index') }}"><svg data-icon="timer"></svg> Mulai Tryout</a>
      </div>
    </header>

    <section class="grid grid-4 member-dashboard-stats" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat reveal">
        <span class="stat__icon"><svg data-icon="gauge"></svg></span>
        <div><div class="stat__label">Nilai Terakhir</div><div class="stat__value">{{ $summary['latest']?->score ?? '—' }}</div><div class="stat__delta text-soft">{{ $summary['latest'] ? $summary['latest']->title.' · maks. '.$summary['latest']->max_score : 'Belum ada hasil' }}</div></div>
      </article>
      <article class="stat reveal" data-delay="70">
        <span class="stat__icon ico--pink"><svg data-icon="timer"></svg></span>
        <div><div class="stat__label">Tryout Selesai</div><div class="stat__value">{{ $summary['tryoutCount'] }}</div><div class="stat__delta text-soft">Sesi yang sudah dikumpulkan</div></div>
      </article>
      @if ($learningAllowed)
      <article class="stat reveal" data-delay="140">
        <span class="stat__icon ico--amber"><svg data-icon="list-checks"></svg></span>
        <div><div class="stat__label">Latihan Soal Dikerjakan</div><div class="stat__value">{{ $summary['practiceCount'] }}</div><div class="stat__delta text-soft">Sesi yang sudah dikumpulkan</div></div>
      </article>
      <article class="stat reveal" data-delay="210">
        <span class="stat__icon ico--mint"><svg data-icon="book"></svg></span>
        <div><div class="stat__label">Materi Selesai</div><div class="stat__value">{{ $completedMaterialCount }} / {{ $materialCount }}</div><div class="stat__delta text-soft">{{ $completedVideoCount }} video ditandai selesai</div></div>
      </article>
      @else
      <article class="stat reveal" data-delay="140">
        <span class="stat__icon ico--amber"><svg data-icon="key"></svg></span>
        <div><div class="stat__label">Token Pilihan Tryout</div><div class="stat__value">{{ $activePackages->sum('tryout_tokens') }}</div><div class="stat__delta text-soft">Tersedia untuk membuka tryout pilihanmu</div></div>
      </article>
      <article class="stat reveal" data-delay="210">
        <span class="stat__icon ico--mint"><svg data-icon="package"></svg></span>
        <div><div class="stat__label">Paket Aktif</div><div class="stat__value">{{ $activePackages->count() }}</div><div class="stat__delta text-soft">Paket belajar yang masih berlaku</div></div>
      </article>
      @endif
    </section>

    <div class="panes panes--wide member-dashboard">
        <div class="member-dashboard__column">
            @if ($learningAllowed)
      <section class="card">
        <div class="card__head">
          <div><div class="card__title">Perkembangan Nilai</div><div class="card__sub">{{ $tryoutSummary['count'] }} tryout selesai</div></div>
          <a class="btn btn--ghost btn--sm" href="{{ route('statistics.index') }}">Detail<svg data-icon="arrow-right"></svg></a>
        </div>
        @if ($tryoutChart)
          <div style="height:240px" data-chart='@json($tryoutChart)'></div>
        @else
          <div class="card card--tight card--dashed"><p class="text-sm text-soft">Belum ada nilai tryout. Setelah sesi pertama dikumpulkan, grafikmu akan muncul di sini.</p></div>
        @endif
      </section>
      <section class="card">
        <div class="card__head"><div><div class="card__title">Progres Materi</div><div class="card__sub">Materi yang sudah kamu tandai selesai</div></div><span class="badge badge--mint badge--flat">{{ $materialProgressPercent }}% selesai</span></div>
        @if ($materialCount > 0)
          <div class="result-hero" style="grid-template-columns:{{ $summary['accuracy'] !== null ? 'auto 1fr' : '1fr' }};gap:1.5rem;justify-items:start;margin-bottom:1.5rem">
            <div class="ring" data-pct="{{ $materialProgressPercent }}"><div class="ring__label"><div class="ring__value">{{ $materialProgressPercent }}%</div><div class="ring__unit">Materi</div></div></div>
            <div class="kv" style="width:100%">
              <div class="kv__row"><span class="kv__key">Materi selesai</span><span class="kv__val">{{ $completedMaterialCount }} / {{ $materialCount }}</span></div>
              <div class="kv__row"><span class="kv__key">Video selesai</span><span class="kv__val">{{ $completedVideoCount }}</span></div>
              <div class="kv__row"><span class="kv__key">Belum selesai</span><span class="kv__val">{{ $materialCount - $completedMaterialCount }}</span></div>
            </div>
          </div>
          <div class="grid" style="--gap:1rem">
            @foreach ($materialSections as $section)
              <div class="progress-wrap"><div class="progress__row"><span>{{ $section->name }}</span><span>{{ $section->completed_materials_count }} / {{ $section->available_materials_count }}</span></div><div class="progress"><div class="progress__bar" data-progress="{{ round($section->completed_materials_count / $section->available_materials_count * 100) }}"></div></div></div>
            @endforeach
          </div>
        @else
          <p class="text-sm text-soft">Belum ada materi yang dapat kamu pelajari.</p>
        @endif
      </section>
      <section class="card">
        <div class="card__head">
          <div><div class="card__title">Performa Belajar</div><div class="card__sub">Akurasi soal biasa dan perolehan skor soal berbobot</div></div>
          @if ($summary['accuracy'] !== null)<span class="badge badge--primary badge--flat">{{ $summary['correct'] + $summary['wrong'] > 0 ? $summary['accuracy'].'% akurasi' : 'Belum ada jawaban' }}</span>@endif
        </div>
        @if ($summary['count'] > 0)
          <div class="result-hero" style="grid-template-columns:auto 1fr;gap:1.5rem;justify-items:start;margin-bottom:1.5rem">
            @if ($summary['accuracy'] !== null)<div class="ring" data-pct="{{ $summary['accuracy'] }}"><div class="ring__label"><div class="ring__value">{{ $summary['accuracy'] }}%</div><div class="ring__unit">Akurasi soal biasa</div></div></div>@endif
            <div class="kv" style="width:100%">
              <div class="kv__row"><span class="kv__key">Soal benar</span><span class="kv__val">{{ $summary['correct'] }}</span></div>
              <div class="kv__row"><span class="kv__key">Soal salah</span><span class="kv__val">{{ $summary['wrong'] }}</span></div>
              <div class="kv__row"><span class="kv__key">Soal berbobot dijawab</span><span class="kv__val">{{ $summary['weighted'] }}</span></div>
              <div class="kv__row"><span class="kv__key">Belum dijawab</span><span class="kv__val">{{ $summary['blank'] }}</span></div>
              <div class="kv__row"><span class="kv__key">Total sesi</span><span class="kv__val">{{ $summary['count'] }}</span></div>
            </div>
          </div>
          <div class="grid" style="--gap:1rem">
            @foreach ($summary['sections'] as $section)
              @php($accuracy = $section['weighted_total'] > 0 ? round($section['score'] / max(1, $section['max']) * 100) : ($section['correct'] + $section['wrong'] > 0 ? round($section['correct'] / ($section['correct'] + $section['wrong']) * 100) : 0))
              <div class="progress-wrap">
                <div class="progress__row"><span>{{ $section['section'] }} · {{ $section['correct'] + $section['wrong'] + $section['weighted'] }}/{{ $section['total'] }} dijawab</span><span>{{ $section['weighted_total'] > 0 ? $section['score'].' / '.$section['max'].' poin' : ($section['correct'] + $section['wrong'] > 0 ? $accuracy.'% akurasi' : 'Belum dijawab') }}</span></div>
                <div class="progress"><div class="progress__bar" data-progress="{{ $accuracy }}"></div></div>
              </div>
            @endforeach
          </div>
        @else
          <div class="card card--tight card--dashed"><p class="text-sm text-soft">Belum ada jawaban yang dikumpulkan. Mulai latihan untuk melihat akurasi per bidang.</p></div>
        @endif
      </section>
      <section class="card">
        <div class="card__head"><div><div class="card__title">Lanjutkan Belajar</div><div class="card__sub">Materi yang belum selesai dan rekomendasi terbaru</div></div><a class="btn btn--ghost btn--sm" href="{{ route('materials.index') }}">Semua materi<svg data-icon="arrow-right"></svg></a></div>
        <div class="grid" style="--gap:1rem">
          @forelse ($recentMaterials as $material)
            <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('materials.show', $material) }}">
              <span class="tile__glyph" style="width:48px;height:48px;border-radius:14px;flex:0 0 48px"><svg data-icon="{{ $material->type->value === 'video' ? 'play-circle' : 'file' }}"></svg></span>
              <div style="flex:1;min-width:0"><strong style="display:block;font-family:var(--font-display);color:var(--ink)">{{ $material->title }}</strong><span class="text-xs text-soft">{{ $material->section->name }} · {{ strtoupper($material->type->value) }} · {{ $material->topic->name }}{{ isset($resumeMaterialIds[$material->id]) ? ' · Lanjutkan' : '' }}</span></div>
              <svg data-icon="chevron-right" style="width:20px;height:20px;color:var(--text-soft);flex:0 0 20px"></svg>
            </a>
          @empty
            <p class="text-sm text-soft">Belum ada materi yang dapat ditampilkan.</p>
          @endforelse
        </div>
      </section>
            @else
                @include('Dashboard.User._tryout-card')
                @include('Dashboard.User._open-attempts', ['openAttempts' => $openAttempts->where('kind', 'tryout')])
            @endif
      <section class="card card--pad-0">
        <div class="flex flex-wrap items-center justify-between" style="padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)"><div><div class="card__title">Riwayat Pengerjaan</div><div class="card__sub">Sesi terbaru yang sudah selesai</div></div><a class="btn btn--ghost btn--sm" href="{{ route('history.index') }}">Lihat semua<svg data-icon="arrow-right"></svg></a></div>
        <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none"><table class="table" style="min-width:530px"><thead><tr><th>Aktivitas</th><th>Jenis</th><th>Tanggal</th><th class="num">Nilai</th><th></th></tr></thead><tbody>
          @forelse (array_slice($summary['recent'], 0, 4) as $attempt)
            <tr><td><strong style="color:var(--ink)">{{ $attempt->title }}</strong></td><td>{{ \App\Enums\ExamKind::from($attempt->kind)->label() }}</td><td><x-local-time :value="$attempt->submitted_at" format="d M Y" /></td><td class="num">{{ $attempt->score }} / {{ $attempt->max_score }}</td><td><a class="badge badge--primary badge--flat" href="{{ $attempt->resultUrl() }}">Lihat Hasil</a></td></tr>
          @empty
            <tr><td colspan="5" class="text-soft">Belum ada sesi yang selesai.</td></tr>
          @endforelse
        </tbody></table></div>
      </section>
        </div>
        <aside class="member-dashboard__column" aria-label="Paket dan aktivitas belajar">
      <section class="card member-dashboard__packages">
        <div class="card__title">Paket Aktif</div>
        @forelse ($activePackages as $activePackage)
        <div class="card card--tight card--dashed"><strong>{{ $activePackage->name }}</strong><p class="text-xs text-soft" style="margin-top:.4rem">@if ($activePackage->expires_at)Berlaku sampai <x-local-time :value="$activePackage->expires_at" format="d M Y, H:i" />@else Tanpa batas waktu @endif · Kuota tryout: {{ $activePackage->remainingTryouts() ?? 'Tanpa batas' }}</p></div>
        @empty<p class="text-sm text-soft">Belum ada paket aktif. Kamu dapat melihat katalog dan memilih paket untuk membuka kontennya.</p>@endforelse
        <a class="btn btn--ghost btn--sm" href="{{ route('packages.index') }}">Lihat paket belajar</a>
      </section>
            @if ($learningAllowed)
                @include('Dashboard.User._tryout-card')
      <section class="card pattern-dots-primary member-dashboard__practice" style="text-align:center">
        <span class="tile__glyph" style="margin:0 auto 1rem;background:var(--pop-mint)"><svg data-icon="pen-line"></svg></span>
        <h3 class="card__title">Latihan Soal</h3>
        <p class="text-sm text-soft" style="margin:.5rem 0 1.25rem">Kerjakan sesi bertimer dengan autosave dan lihat hasil setelah selesai.</p>
        <a class="btn btn--dark btn--block" href="{{ route('quizzes.index') }}">Buka Latihan Soal</a>
      </section>
                @include('Dashboard.User._open-attempts')
            @else
<section class="card member-dashboard__upgrade">
    <div><span class="eyebrow">Paket Premium</span><h2 class="card__title">Belajar lebih lengkap</h2></div>
    <p class="text-sm text-soft">Buka materi SKD, latihan soal, analisa pembelajaran, statistik nilai, dan sertifikat dengan Premium.</p>
    <a class="btn btn--primary btn--sm" href="{{ route('packages.index') }}">Lihat paket Premium <svg data-icon="arrow-right" aria-hidden="true"></svg></a>
</section>
            @endif
        </aside>
    </div>
@endsection
