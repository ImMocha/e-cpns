@extends('layouts.admin.main')

@section('title', 'Admin Dashboard — Ganesha Nusa')

@section('content')
    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Ringkasan Platform</span>
        <h1 style="margin-top:.5rem">Admin Dashboard</h1>
        <p>Data peserta, materi, artikel, dan sesi ujian yang tercatat sampai <x-local-time :value="now()" format="d M Y" />.</p>
      </div>
      <div class="btn-row">
        <button class="btn btn--ghost" type="button" data-print><svg data-icon="printer"></svg> Cetak Laporan</button>
        <button class="btn btn--primary" type="button" data-modal-open="quickModal"><svg data-icon="plus"></svg> Tambah Konten</button>
      </div>
    </header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat reveal">
        <span class="stat__icon"><svg data-icon="users"></svg></span>
        <div><div class="stat__label">Total Peserta</div><div class="stat__value">{{ number_format($participantCount, 0, ',', '.') }}</div><div class="stat__delta text-soft">{{ $activeParticipantCount }} aktif · {{ $inactiveParticipantCount }} nonaktif</div></div>
      </article>
      <article class="stat reveal" data-delay="70">
        <span class="stat__icon ico--pink"><svg data-icon="layers"></svg></span>
        <div><div class="stat__label">Total Soal</div><div class="stat__value">{{ number_format($questionCount, 0, ',', '.') }}</div><div class="stat__delta text-soft">{{ $publishedQuestionCount }} terbit · {{ $draftQuestionCount }} draft</div></div>
      </article>
      <article class="stat reveal" data-delay="140">
        <span class="stat__icon ico--amber"><svg data-icon="timer"></svg></span>
        <div><div class="stat__label">Tryout Selesai</div><div class="stat__value">{{ number_format($completedTryoutCount, 0, ',', '.') }}</div><div class="stat__delta text-soft">{{ $completedQuizCount }} sesi latihan selesai</div></div>
      </article>
      <article class="stat reveal" data-delay="210">
        <span class="stat__icon ico--mint"><svg data-icon="book"></svg></span>
        <div><div class="stat__label">Materi Terbit</div><div class="stat__value">{{ number_format($publishedMaterialCount, 0, ',', '.') }}</div><div class="stat__delta text-soft">{{ $draftMaterialCount }} masih draft</div></div>
      </article>
    </section>

    <div class="panes panes--wide panes--equal dashboard-row">
      <section class="card admin-activity admin-activity--monthly" aria-labelledby="monthly-activity-title">
        <div class="admin-activity__head">
          <div class="admin-activity__heading">
            <span class="admin-activity__icon" aria-hidden="true"><svg data-icon="chart"></svg></span>
            <div><h2 class="card__title" id="monthly-activity-title">Sesi Selesai per Bulan</h2><p class="card__sub">Pantau aktivitas latihan soal dan tryout peserta.</p></div>
          </div>
          <span class="chip chip--primary"><svg data-icon="calendar" aria-hidden="true"></svg> 6 bulan terakhir</span>
        </div>
        <div class="admin-activity__summary">
          <strong>{{ number_format(array_sum($monthlyChart['values']), 0, ',', '.') }}</strong>
          <div><span>sesi selesai</span><small>Latihan soal &amp; tryout</small></div>
        </div>
        @if (array_sum($monthlyChart['values']) > 0)
          <div class="admin-activity__chart" data-chart='@json($monthlyChart)'></div>
        @else
          <div class="admin-activity__empty">
            <span class="admin-activity__empty-icon" aria-hidden="true"><svg data-icon="trending-up"></svg></span>
            <h3>Aktivitas belajar dimulai dari sini</h3>
            <p>Belum ada sesi yang selesai dalam enam bulan terakhir.<br>Grafik akan muncul setelah peserta menyelesaikan sesi.</p>
          </div>
          <div class="admin-activity__months" aria-label="Periode grafik">
            @foreach ($monthlyChart['labels'] as $label)
              <span>{{ $label }}</span>
            @endforeach
          </div>
        @endif
      </section>
      <section class="card card--pop card--amber">
        <span class="card__icon ico--coral" style="position:static"><svg data-icon="alert"></svg></span>
        <h2 class="card__title" style="margin-top:.75rem">Perlu Perhatian</h2>
        <div class="grid grid-cols-1" style="--gap:.75rem;margin-top:1rem">
          <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.exam-claims.index') }}"><svg data-icon="check"></svg><div><strong>{{ $pendingClaimCount }} unlock gratis menunggu</strong><p class="text-xs text-soft">Periksa bukti dan konfirmasi akses peserta.</p></div></a>
          <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.materials.index', ['status' => 'draft']) }}">
            <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="file"></svg></span>
            <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $draftMaterialCount }} materi draft</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Tinjau sebelum diterbitkan</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
          </a>
          <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.materials.quizzes.index') }}">
            <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="pencil"></svg></span>
            <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $quizCount }} latihan materi</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Kelola soal latihan dan hubungkan dengan topik pembelajaran.</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
          </a>
          <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.practices.index') }}">
            <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="pen-line"></svg></span>
            <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $practiceCount }} latihan soal</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Kelola sesi latihan mandiri, durasi, dan penilaian.</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
          </a>
          <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.exams.index') }}">
            <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="timer"></svg></span>
            <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $tryoutCount }} tryout</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Atur simulasi CAT, durasi, dan penilaian peserta.</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
          </a>
        </div>
      </section>
    </div>
    <div class="panes panes--wide panes--equal dashboard-row">
      <section class="card card--pad-0 admin-activity admin-activity--recent" aria-labelledby="recent-activity-title">
        <div class="admin-activity__head">
          <div class="admin-activity__heading">
            <span class="admin-activity__icon" aria-hidden="true"><svg data-icon="history"></svg></span>
            <div><h2 class="card__title" id="recent-activity-title">Pengerjaan Terbaru</h2><p class="card__sub">Hasil terbaru dari sesi yang telah diselesaikan.</p></div>
          </div>
          <span class="badge badge--mint badge--flat"><svg data-icon="check-circle" aria-hidden="true"></svg> Sesi selesai</span>
        </div>
        @if ($recentAttempts->isNotEmpty())
        <div class="table-wrap admin-activity__table-wrap">
          <table class="table admin-activity__table">
            <caption class="sr-only">Hasil pengerjaan peserta terbaru</caption>
            <thead><tr><th>Peserta</th><th>Aktivitas</th><th>Tanggal</th><th class="num">Nilai</th></tr></thead>
            <tbody>
              @foreach ($recentAttempts as $attempt)
                <tr><td><div class="admin-activity__participant"><span class="admin-activity__avatar" aria-hidden="true"><svg data-icon="user"></svg></span><strong>{{ $attempt->user?->name ?? 'Peserta terhapus' }}</strong></div></td><td><div class="admin-activity__session"><strong>{{ $attempt->title }}</strong><span class="badge badge--{{ $attempt->kind === 'tryout' ? 'primary' : 'mint' }} badge--flat">{{ \App\Enums\ExamKind::from($attempt->kind)->label() }}</span></div></td><td><x-local-time :value="$attempt->submitted_at" format="d M Y H:i" /></td><td class="num"><span class="admin-activity__score">{{ $attempt->score }} <small>/ {{ $attempt->max_score }}</small></span></td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
          <div class="admin-activity__empty admin-activity__empty--recent">
            <span class="admin-activity__empty-icon" aria-hidden="true"><svg data-icon="file"></svg><span><svg data-icon="clock"></svg></span></span>
            <h3>Menunggu hasil pertama</h3>
            <p>Belum ada hasil ujian peserta.<br>Nama peserta, aktivitas, dan nilai akan tampil di sini setelah sesi selesai.</p>
            <span class="admin-activity__note"><svg data-icon="check-circle" aria-hidden="true"></svg> Hanya sesi yang sudah dikumpulkan</span>
          </div>
        @endif
      </section>
      <section class="card">
        <div class="card__head"><div><div class="card__title">Status Peserta</div><div class="card__sub">Akun aktif dan nonaktif</div></div></div>
        @if ($participantCount > 0)
          <div style="width:180px;height:180px;margin:0 auto 1.25rem" data-chart='@json($participantStatusChart)'></div>
          <div class="legend"><div class="legend__item"><span class="legend__swatch" style="background:var(--primary)"></span> Aktif · {{ $activeParticipantCount }}</div><div class="legend__item"><span class="legend__swatch" style="background:var(--pop-amber)"></span> Nonaktif · {{ $inactiveParticipantCount }}</div></div>
        @else
          <p class="text-sm text-soft">Belum ada akun peserta.</p>
        @endif
      </section>
    </div>
    <div class="panes panes--wide panes--equal dashboard-row">
      <section class="card">
        <div class="card__head"><div><div class="card__title">Partisipasi per Sesi</div><div class="card__sub">Jumlah peserta unik yang menyelesaikan sesi</div></div></div>
        <div class="grid" style="--gap:1rem">
          @foreach ($bankActivity as $bank)
            <div class="scorebar"><span class="fw-700" style="color:var(--ink)">{{ $bank['title'] }}</span><div class="scorebar__track"><div class="scorebar__fill" data-pct="{{ $bankActivity->max('participants') > 0 ? round($bank['participants'] / $bankActivity->max('participants') * 100) : 0 }}"></div></div><span class="scorebar__val">{{ $bank['participants'] }}</span></div>
          @endforeach
        </div>
      </section>
      <section class="card">
        <div class="card__head"><div><div class="card__title">Materi Terbaru</div><div class="card__sub">Konten yang baru ditambahkan</div></div></div>
        <div class="stack" style="--stack:.75rem">
          @forelse ($recentMaterials as $material)
            <a class="flex items-center gap-3" href="{{ route('admin.materials.edit', $material) }}"><span class="tile__glyph" style="width:38px;height:38px;border-radius:11px;flex:0 0 38px"><svg data-icon="{{ $material->type->icon() }}"></svg></span><div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $material->title }}</strong><span class="text-xs text-soft">{{ $material->section?->name ?? 'Tanpa bidang' }} · {{ $material->status->label() }}</span></div></a>
          @empty
            <p class="text-sm text-soft">Belum ada materi yang ditambahkan.</p>
          @endforelse
        </div>
        <a class="btn btn--ghost btn--block" href="{{ route('admin.materials.index') }}" style="margin-top:1.25rem">Kelola materi<svg data-icon="arrow-right"></svg></a>
      </section>
    </div>
    <div class="panes panes--wide panes--equal dashboard-row">
      <section class="card">
        <div class="card__head"><div><div class="card__title">Aktivitas Artikel</div><div class="card__sub">{{ $publishedArticleCount }} artikel dapat dibaca pengunjung</div></div></div>
        <div class="grid grid-cols-2 gap-3">
          <div><div class="stat__label">Pembaca Unik</div><div class="stat__value">{{ number_format($articleReaderCount, 0, ',', '.') }}</div></div>
          <div><div class="stat__label">Tayangan</div><div class="stat__value">{{ number_format($articleViewCount, 0, ',', '.') }}</div></div>
        </div>
        @if ($mostReadArticles->isNotEmpty())
          <div class="stack" style="--stack:.7rem;margin-top:1.25rem">
            <span class="text-xs text-soft">Paling banyak dibaca</span>
            @foreach ($mostReadArticles as $popularArticle)
              <a class="flex items-center justify-between gap-3 text-sm" href="{{ route('admin.articles.edit', $popularArticle->id) }}"><strong class="text-primary">{{ $popularArticle->title }}</strong><span class="text-xs text-soft whitespace-nowrap">{{ number_format($popularArticle->views_count, 0, ',', '.') }} tayangan</span></a>
            @endforeach
          </div>
        @endif
        <a class="btn btn--ghost btn--block" href="{{ route('admin.articles.index') }}" style="margin-top:1.25rem">Lihat statistik artikel<svg data-icon="arrow-right"></svg></a>
      </section>
      <section class="card"><h2 class="card__title">Ringkasan Pembayaran</h2><p class="text-sm text-soft" style="margin-top:.5rem">Periksa pesanan, status pembayaran, dan aktivasi paket peserta.</p><a class="btn btn--ghost btn--block" style="margin-top:1rem" href="{{ route('admin.transactions.index') }}">Kelola Transaksi<svg data-icon="arrow-right"></svg></a></section>
    </div>
@endsection

@section('modals')
<div class="modal" id="quickModal" role="dialog" aria-modal="true" aria-labelledby="quickTitle" aria-hidden="true">
  <div class="modal__backdrop"></div>
  <div class="modal__panel">
    <button class="modal__close" type="button" data-modal-close aria-label="Tutup"><svg data-icon="close"></svg></button>
    <h2 class="modal__title" id="quickTitle">Tambah Konten</h2>
    <p class="text-soft text-sm" style="margin-top:.4rem">Pilih jenis konten yang ingin kamu tambahkan.</p>
    <div class="grid grid-2" style="--gap:.75rem;margin-top:1.25rem">
      <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.materials.create') }}" data-modal-close><span class="tile__glyph" style="width:44px;height:44px;border-radius:13px;flex:0 0 44px"><svg data-icon="file"></svg></span><div><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">Materi PDF / Video</strong><span class="text-xs text-soft">Tambah materi baru</span></div></a>
      <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.questions.create') }}" data-modal-close><span class="tile__glyph" style="width:44px;height:44px;border-radius:13px;flex:0 0 44px;background:var(--pop-amber-050);color:#B4830C"><svg data-icon="layers"></svg></span><div><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">Soal Baru</strong><span class="text-xs text-soft">Kelola soal latihan dan tryout</span></div></a>
    </div>
  </div>
</div>
@endsection
