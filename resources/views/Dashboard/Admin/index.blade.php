@extends('layouts.admin.main')

@section('title', 'Admin Dashboard — Ganesha Nusa')

@section('content')
    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Ringkasan Platform</span>
        <h1 style="margin-top:.5rem">Admin Dashboard</h1>
        <p>Data peserta, materi, artikel, dan sesi ujian yang tercatat sampai {{ now()->translatedFormat('d M Y') }}.</p>
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

    <div class="panes panes--wide" style="--gap:1.5rem">
      <div class="stack" style="--stack:1.5rem">
        <section class="card">
          <div class="card__head"><div><div class="card__title">Sesi Selesai per Bulan</div><div class="card__sub">Quiz dan tryout yang dikumpulkan selama 6 bulan terakhir</div></div></div>
          @if (array_sum($monthlyChart['values']) > 0)
            <div style="height:250px" data-chart='@json($monthlyChart)'></div>
          @else
            <p class="text-sm text-soft">Belum ada sesi yang selesai dalam enam bulan terakhir.</p>
          @endif
        </section>

        <section class="card card--pad-0">
          <div class="flex items-center justify-between" style="padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)">
            <div><div class="card__title">Pengerjaan Terbaru</div><div class="card__sub">Sesi peserta yang benar-benar selesai</div></div>
          </div>
          <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
            <table class="table" style="min-width:620px">
              <thead><tr><th>Peserta</th><th>Aktivitas</th><th>Tanggal</th><th class="num">Nilai</th></tr></thead>
              <tbody>
                @forelse ($recentAttempts as $attempt)
                  <tr><td><strong style="color:var(--ink)">{{ $attempt->user?->name ?? 'Peserta terhapus' }}</strong></td><td>{{ $attempt->title }} <span class="badge badge--{{ $attempt->kind === 'tryout' ? 'primary' : 'mint' }} badge--flat">{{ $attempt->kind === 'tryout' ? 'Tryout' : 'Latihan' }}</span></td><td>{{ $attempt->submitted_at->translatedFormat('d M Y H:i') }}</td><td class="num">{{ $attempt->score }} / {{ $attempt->max_score }}</td></tr>
                @empty
                  <tr><td colspan="4" class="text-soft">Belum ada hasil ujian peserta.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="card">
          <div class="card__head"><div><div class="card__title">Partisipasi per Bank Soal</div><div class="card__sub">Jumlah peserta unik yang menyelesaikan sesi</div></div></div>
          <div class="grid" style="--gap:1rem">
            @foreach ($bankActivity as $bank)
              <div class="scorebar"><span class="fw-700" style="color:var(--ink)">{{ $bank['title'] }}</span><div class="scorebar__track"><div class="scorebar__fill" data-pct="{{ $bankActivity->max('participants') > 0 ? round($bank['participants'] / $bankActivity->max('participants') * 100) : 0 }}"></div></div><span class="scorebar__val">{{ $bank['participants'] }}</span></div>
            @endforeach
          </div>
        </section>
      </div>

      <aside class="stack" style="--stack:1.5rem">
        <section class="card card--pop card--amber">
          <span class="card__icon ico--coral" style="position:static"><svg data-icon="alert"></svg></span>
          <h2 class="card__title" style="margin-top:.75rem">Perlu Perhatian</h2>
          <div class="grid" style="--gap:.75rem;margin-top:1rem">
            <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.materials.index', ['status' => 'draft']) }}">
              <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="file"></svg></span>
              <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $draftMaterialCount }} materi draft</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Tinjau sebelum diterbitkan</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
            </a>
            <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.materials.quizzes.index') }}">
              <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="pencil"></svg></span>
              <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $quizCount }} latihan materi</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Kelola soal latihan dan hubungkan dengan topik pembelajaran.</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
            </a>
            <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.exams.index') }}">
              <span class="price__check" style="background:var(--warning);color:var(--pop-ink)"><svg data-icon="timer"></svg></span>
              <div style="flex:1;min-width:0"><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">{{ $tryoutCount }} tryout</strong><span class="text-xs text-soft" style="display:block;margin-top:.35rem">Atur simulasi CAT, durasi, dan penilaian peserta.</span></div><svg data-icon="chevron-right" style="width:18px;height:18px;flex-shrink:0;color:var(--text-soft)"></svg>
            </a>
          </div>
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

        <section class="card card--dashed"><div class="card__title">Data transaksi belum tersedia</div><p class="text-sm text-soft" style="margin-top:.5rem">Pendapatan dan transaksi akan ditampilkan setelah modul pembayaran menyimpan transaksi nyata.</p></section>
      </aside>
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
      <a class="card card--tight card--dashed flex items-center gap-3" href="{{ route('admin.questions.create') }}" data-modal-close><span class="tile__glyph" style="width:44px;height:44px;border-radius:13px;flex:0 0 44px;background:var(--pop-amber-050);color:#B4830C"><svg data-icon="layers"></svg></span><div><strong style="display:block;color:var(--ink);font-size:var(--fs-sm)">Soal Baru</strong><span class="text-xs text-soft">Kelola bank soal</span></div></a>
    </div>
  </div>
</div>
@endsection
