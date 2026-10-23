@extends('layouts.public')

@section('title', 'Ganesha Nusa — Belajar & Tryout CPNS Online')

@section('content')
{{-- ===================== HERO ===================== --}}
<section class="hero">
  <div class="hero__bg deco"></div>
  <div class="hero__bg-2 deco"></div>
  <div class="deco deco--circle pattern-dots-primary" style="width:180px;height:180px;top:14%;right:6%;opacity:.5" aria-hidden="true"></div>

  <div class="container container--wide">
    <div class="split">
      <div>
        <span class="eyebrow">Seleksi CPNS 2026</span>
        <h1 style="margin:1rem 0 1.25rem">
          Belajar Lebih <span class="underline-squiggle">Cerdas</span>,<br>
          Hadapi Tryout <span style="color:var(--primary-700)">Tanpa Panik</span>.
        </h1>
        <p class="lead" style="margin-bottom:1.75rem">
          Materi PDF, video pembelajaran, bank soal latihan, dan simulasi Try Out CAT
          dengan timer, autosave, pembahasan, serta grafik perkembangan nilai.
          Semua dalam satu platform.
        </p>

        <div class="btn-row" style="margin-bottom:2rem">
          <a class="btn btn--primary btn--lg" href="{{ route('register') }}">
            Mulai Gratis Sekarang
            <span class="btn__icon"><svg data-icon="arrow-right"></svg></span>
          </a>
          <a class="btn btn--ghost btn--lg" href="{{ route('tryouts.index') }}">
            <svg data-icon="play-circle"></svg> Lihat Simulasi Tryout
          </a>
        </div>

        <div class="hero-stats">
          <div>
            <div class="hero-stat__num" data-count-to="{{ $participantCount }}">{{ number_format($participantCount, 0, ",", ".") }}</div>
            <div class="hero-stat__label">Peserta Aktif</div>
          </div>
          <div>
            <div class="hero-stat__num" data-count-to="{{ $questionCount }}">{{ number_format($questionCount, 0, ",", ".") }}</div>
            <div class="hero-stat__label">Bank Soal</div>
          </div>
          <div>
            <div class="hero-stat__num" data-count-to="{{ $tryoutCount }}">{{ $tryoutCount }}</div>
            <div class="hero-stat__label">Simulasi CAT</div>
          </div>
        </div>
      </div>

      <div class="split-media reveal">
        <div class="split-media__blob" aria-hidden="true"></div>
        <div class="split-media__img" style="aspect-ratio:4/3">
          <img src="{{ asset('images/hero-tryout.jpg') }}" alt="Ilustrasi peserta belajar prepping CPNS dengan buku TWK, TIU, TKP, dan CAT CPNS" width="960" height="720">
        </div>

        <div class="card card--tight card--pop" style="position:absolute;left:-18px;bottom:24px;max-width:210px;z-index:3">
          <span class="badge badge--mint badge--flat"><svg data-icon="target"></svg> Belajar Terarah</span>
          <p class="text-sm fw-700" style="margin-top:.5rem">Materi, latihan, dan hasil dalam satu akun.</p>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- ===================== MARQUEE ===================== --}}
<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TWK</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TIU</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TKP</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Timer CAT</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Autosave Jawaban</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Pembahasan Lengkap</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Grafik Perkembangan</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Leaderboard</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TWK</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TIU</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> TKP</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Timer CAT</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Autosave Jawaban</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Pembahasan Lengkap</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Grafik Perkembangan</span>
    <span class="marquee__item"><svg data-icon="check-circle"></svg> Leaderboard</span>
  </div>
</div>

{{-- ===================== FITUR ===================== --}}
<section class="section" id="fitur">
  <div class="container container--wide">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow" style="justify-content:center">Semua Yang Kamu Butuh</span>
      <h2 style="margin:.75rem 0 0">Satu Platform, <span class="underline-squiggle">Alur Belajar Lengkap</span></h2>
      <p>Dari baca materi sampai tahu posisimu di leaderboard — semuanya tercatat dalam satu akun.</p>
    </div>

    <div class="grid grid-3" style="--gap:1.75rem">
      <article class="card card--icon card--pop reveal" data-delay="0">
        <span class="card__icon ico--primary"><svg data-icon="book"></svg></span>
        <h3>Materi Ringkas</h3>
        <p class="text-sm text-soft">Materi PDF, video pembelajaran, dan artikel ringkas per kategori. Progress belajar langsung tercatat di dashboard.</p>
        <div class="tile__foot">
          <span class="badge badge--primary badge--flat">Gratis + Premium</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('materials.index') }}">Lihat<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>

      <article class="card card--icon card--pop reveal" data-delay="90">
        <span class="card__icon ico--pink"><svg data-icon="timer"></svg></span>
        <h3>Simulasi Try Out CAT</h3>
        <p class="text-sm text-soft">Timer, navigasi nomor soal, randomisasi soal, dan autosave jawaban, sehingga latihan terasa seperti ujian CAT sungguhan.</p>
        <div class="tile__foot">
          <span class="badge badge--pink badge--flat">Fitur Utama</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('tryouts.index') }}">Coba<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>

      <article class="card card--icon card--pop reveal" data-delay="180">
        <span class="card__icon ico--amber"><svg data-icon="layers"></svg></span>
        <h3>Bank Soal & Pembahasan</h3>
        <p class="text-sm text-soft">Soal TWK, TIU, dan TKP dengan pembahasan serta latihan per kategori.</p>
        <div class="tile__foot">
          <span class="badge badge--amber badge--flat">{{ number_format($questionCount, 0, ",", ".") }} soal terbit</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('question-bank.index') }}">Buka<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>

      <article class="card card--icon card--pop reveal" data-delay="0">
        <span class="card__icon ico--mint"><svg data-icon="chart"></svg></span>
        <h3>Statistik & Grafik</h3>
        <p class="text-sm text-soft">Nilai tertinggi, nilai terbaru, dan grafik perkembangan nilai dari tryout pertama sampai sekarang.</p>
        <div class="tile__foot">
          <span class="badge badge--mint badge--flat">Real-time</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('statistics.index') }}">Analisis<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>

      <article class="card card--icon card--pop reveal" data-delay="90">
        <span class="card__icon ico--violet"><svg data-icon="trophy"></svg></span>
        <h3>Leaderboard</h3>
        <p class="text-sm text-soft">Bandingkan posisimu dengan peserta lain. Papan peringkat diperbarui setelah setiap tryout.</p>
        <div class="tile__foot">
          <span class="badge badge--violet badge--flat">Peringkat</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('leaderboard.index') }}">Lihat<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>

      <article class="card card--icon card--pop reveal" data-delay="180">
        <span class="card__icon ico--coral"><svg data-icon="award"></svg></span>
        <h3>Sertifikat Digital</h3>
        <p class="text-sm text-soft">Selesaikan program belajar dan dapatkan sertifikat yang bisa diunduh kapan saja.</p>
        <div class="tile__foot">
          <span class="badge badge--pink badge--flat">Reward</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('certificates.index') }}">Detail<svg data-icon="arrow-right"></svg></a>
        </div>
      </article>
    </div>
  </div>
</section>

{{-- ===================== ALUR BELAJAR ===================== --}}
<section class="section section--alt">
  <div class="container container--wide">
    <div class="split">
      <div class="reveal">
        <span class="eyebrow">Alur Belajar</span>
        <h2 style="margin:.75rem 0 1rem">Empat Langkah, <span class="underline-squiggle">Selesai</span></h2>
        <p class="lead" style="margin-bottom:2rem">Alur belajar yang sama dari hari pertama sampai kamu merasa siap.</p>

        <div class="acc" data-accordion="single">
          <div class="acc-item">
            <button class="acc-trigger" aria-expanded="true" aria-controls="s1">
              <span>1 · Pilih materi yang dibutuhkan</span><svg data-icon="chevron-down" class="acc-icon"></svg>
            </button>
            <div class="acc-panel is-open" id="s1">
              Browse kategori TWK, TIU, atau TKP. Setiap materi punya indikator progress supaya kamu tahu apa yang belum selesai.
            </div>
          </div>
          <div class="acc-item">
            <button class="acc-trigger" aria-expanded="false" aria-controls="s2">
              <span>2 · Latihan dengan bank soal</span><svg data-icon="chevron-down" class="acc-icon"></svg>
            </button>
            <div class="acc-panel" id="s2">
              Kerjakan soal latihan per kategori. Jawaban langsung dinilai dan kunci muncul setelah submit. Pembahasan tersedia dengan paket berbayar aktif.
            </div>
          </div>
          <div class="acc-item">
            <button class="acc-trigger" aria-expanded="false" aria-controls="s3">
              <span>3 · Simulasi Try Out CAT</span><svg data-icon="chevron-down" class="acc-icon"></svg>
            </button>
            <div class="acc-panel" id="s3">
              Ikuti simulasi dengan timer, navigasi nomor soal, dan autosave. Otomatis submit saat waktu habis.
            </div>
          </div>
          <div class="acc-item">
            <button class="acc-trigger" aria-expanded="false" aria-controls="s4">
              <span>4 · Pantau perkembangan</span><svg data-icon="chevron-down" class="acc-icon"></svg>
            </button>
            <div class="acc-panel" id="s4">
              Lihat grafik nilai, riwayat pengerjaan, dan posisimu di leaderboard untuk tahu area yang perlu diperkuat.
            </div>
          </div>
        </div>
      </div>

      <div class="reveal" data-delay="120">
        <div class="card card--pop">
          <h3 class="card__title">Ruang Belajar Kamu</h3>
          <p class="text-soft" style="margin:.75rem 0 1.25rem">Dashboard mencatat aktivitas belajar dan hasil pengerjaan dari akunmu.</p>
          <div class="kv">
            <div class="kv__row"><span class="kv__key">Materi terbit</span><span class="kv__val">{{ $materialCount }}</span></div>
            <div class="kv__row"><span class="kv__key">Soal tersedia</span><span class="kv__val">{{ $questionCount }}</span></div>
            <div class="kv__row"><span class="kv__key">Tryout aktif</span><span class="kv__val">{{ $tryoutCount }}</span></div>
          </div>
          <a class="btn btn--primary btn--block" href="{{ route('dashboard') }}" style="margin-top:1.25rem">Buka Dashboard<svg data-icon="arrow-right"></svg></a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ===================== MATERI ===================== --}}
<section class="section" id="materi">
  <div class="container container--wide">
    <div class="section-head section-head--split reveal">
      <div><span class="eyebrow">Konten Belajar</span><h2 style="margin:.75rem 0 0">Materi <span class="underline-squiggle">dan Video</span></h2><p>Pelajari konten yang tersedia sesuai bidang dan paket belajarmu.</p></div>
      <a class="btn btn--ghost" href="{{ route('materials.index') }}">Semua Materi<svg data-icon="arrow-right"></svg></a>
    </div>
    <div data-tabs>
      <div class="tabs" role="tablist" aria-label="Format materi">
        <button class="tab is-active" data-tab="t-pdf" role="tab" aria-selected="true">Materi PDF</button>
        <button class="tab" data-tab="t-video" role="tab" aria-selected="false">Video Pembelajaran</button>
      </div>
      @foreach (['pdf' => $featuredPdfs, 'video' => $featuredVideos] as $format => $materials)
      <div class="tab-panel{{ $loop->first ? ' is-active' : '' }}" id="t-{{ $format }}" data-tab-panel>
        <div class="grid-auto grid-auto--lg">
          @forelse ($materials as $material)
          <article class="card card--pad-0 tile reveal">
            <div class="tile__media"><span class="tile__glyph"><svg data-icon="{{ $format === 'pdf' ? 'file' : 'play-circle' }}"></svg></span><span class="badge badge--primary tile__badge">{{ $material->access_level->value === 'premium' ? 'Premium' : 'Gratis' }}</span></div>
            <div class="tile__body">
              <span class="tile__cat">{{ $material->section->code }} · {{ $material->topic->name }}</span>
              <h3 class="tile__title">{{ $material->title }}</h3>
              <p class="tile__desc">{{ $material->summary }}</p>
              <div class="tile__foot"><span class="text-xs text-soft">{{ $format === 'pdf' ? ($material->page_count ? $material->page_count.' halaman' : 'Materi PDF') : ($material->humanDuration() ?? 'Video Pembelajaran') }}</span><a class="btn btn--sm btn--primary" href="{{ route('materials.show', $material) }}">Buka<svg data-icon="arrow-right"></svg></a></div>
            </div>
          </article>
          @empty
          <div class="card"><h3 class="card__title">{{ $format === 'pdf' ? 'Materi PDF' : 'Video Pembelajaran' }}</h3><p class="text-soft" style="margin-top:.75rem">Lihat katalog materi untuk konten yang tersedia, atau hubungi admin untuk bantuan memilih bahan belajar.</p><a class="btn btn--ghost btn--sm" style="margin-top:1rem" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a></div>
          @endforelse
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== ARTIKEL ===================== --}}
<section class="section" id="artikel">
  <div class="container container--wide">
    <div class="section-head section-head--split reveal">
      <div>
        <span class="eyebrow">Bacaan pilihan</span>
        <h2 style="margin:.75rem 0 0">Artikel <span class="underline-squiggle">Terbaru</span></h2>
        <p style="max-width:52ch">Panduan dan tips yang bisa kamu baca kapan saja selama persiapan CPNS.</p>
      </div>
      <a class="btn btn--ghost" href="{{ route('articles.index') }}">Semua Artikel<svg data-icon="arrow-right"></svg></a>
    </div>

    @if ($latestArticles->isNotEmpty())
      <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($latestArticles as $article)
          <article class="card article-card reveal">
            @if ($article->coverUrl())
              <x-article-cover :article="$article" image-class="article-card-cover" :lazy="true" />
            @else
              <div class="article-card-cover article-card-cover--empty"><svg data-icon="quote"></svg></div>
            @endif
            <span class="badge {{ $article->section->resolvedTone()->badgeClass() }}">{{ $article->section->name }}</span>
            <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
            <p class="text-soft">{{ $article->summary() }}</p>
            <div class="article-card-meta"><span>{{ $article->published_at->format('d M Y') }}</span><span>{{ $article->readingMinutes() }} menit baca</span></div>
            <a class="btn btn--outline" href="{{ route('articles.show', $article) }}">Baca artikel <svg data-icon="arrow-right"></svg></a>
          </article>
        @endforeach
      </div>
    @else
      <div class="card article-empty"><svg data-icon="quote"></svg><h3>Artikel segera hadir</h3><p class="text-soft">Bacaan terbaru akan muncul setelah diterbitkan.</p></div>
    @endif
  </div>
</section>

{{-- ===================== TRYOUT SHOWCASE ===================== --}}
<section class="section section--alt" id="tryout">
  <div class="container container--wide">
    <div class="split split--reverse">
      <div class="reveal">
        <div class="card card--pop">
          <span class="badge badge--ink"><svg data-icon="timer"></svg> Tryout CAT</span>
          <h3 class="card__title" style="margin-top:1.25rem">{{ $referenceTryout?->title ?? 'Persiapkan Diri untuk Seleksi CPNS' }}</h3>
          @if ($referenceTryout)
          <div class="kv" style="margin-top:1rem">
            <div class="kv__row"><span class="kv__key">Jumlah soal</span><span class="kv__val">{{ $referenceTryout->question_count }}</span></div>
            <div class="kv__row"><span class="kv__key">Durasi</span><span class="kv__val">{{ $referenceTryout->duration_minutes }} menit</span></div>
            <div class="kv__row"><span class="kv__key">Poin maksimal</span><span class="kv__val">{{ $referenceTryout->maximumScore() }}</span></div>
          </div>
          @else
          <p class="text-soft" style="margin-top:1rem">Periksa katalog tryout untuk memilih sesi belajar sesuai targetmu.</p>
          @endif
          <a class="btn btn--primary btn--block" style="margin-top:1.25rem" href="{{ route('tryouts.index') }}">Lihat Tryout<svg data-icon="arrow-right"></svg></a>
        </div>
      </div>

      <div class="reveal" data-delay="100">
        <span class="eyebrow">Fitur Utama</span>
        <h2 style="margin:.75rem 0 1rem">Simulasi <span class="underline-squiggle">Try Out CAT</span> yang Serius</h2>
        <p class="lead" style="margin-bottom:1.75rem">
          Didesain untuk membiasakan kamu dengan tekanan waktu dan format CAT sebelum hari-H.
        </p>
        <x-passing-grades style="margin-bottom:1.5rem" />

        <div class="grid" style="--gap:.85rem">
          <div class="card card--tight flex items-start gap-3">
            <span class="stat__icon ico--pink" style="width:40px;height:40px;flex-basis:40px;border-radius:12px"><svg data-icon="timer"></svg></span>
            <div><strong style="font-family:var(--font-display);color:var(--ink)">Timer & auto-submit</strong><p class="text-sm text-soft">Saat waktu habis, sistem otomatis mengumpulkan jawaban dan menghitung nilai.</p></div>
          </div>
          <div class="card card--tight flex items-start gap-3">
            <span class="stat__icon ico--mint" style="width:40px;height:40px;flex-basis:40px;border-radius:12px"><svg data-icon="check-circle"></svg></span>
            <div><strong style="font-family:var(--font-display);color:var(--ink)">Autosave jawaban</strong><p class="text-sm text-soft">Jawaban tersimpan otomatis. Refresh atau pindah halaman tidak menghapus progress.</p></div>
          </div>
          <div class="card card--tight flex items-start gap-3">
            <span class="stat__icon ico--amber" style="width:40px;height:40px;flex-basis:40px;border-radius:12px"><svg data-icon="refresh"></svg></span>
            <div><strong style="font-family:var(--font-display);color:var(--ink)">Randomisasi soal</strong><p class="text-sm text-soft">Urutan soal berbeda untuk setiap peserta agar latihan lebih jujur.</p></div>
          </div>
          <div class="card card--tight flex items-start gap-3">
            <span class="stat__icon ico--violet" style="width:40px;height:40px;flex-basis:40px;border-radius:12px"><svg data-icon="grid"></svg></span>
            <div><strong style="font-family:var(--font-display);color:var(--ink)">Navigasi nomor soal</strong><p class="text-sm text-soft">Pindah antar soal dengan bebas, tandai soal yang masih ragu, lalu kembali lagi.</p></div>
          </div>
        </div>

        <a class="btn btn--dark btn--lg" href="{{ route('tryouts.index') }}" style="margin-top:1.75rem">
          Coba Tryout Sekarang<span class="btn__icon"><svg data-icon="zap"></svg></span>
        </a>
      </div>
    </div>
  </div>
</section>

{{-- ===================== PAKET / PRICING ===================== --}}
<section class="section" id="paket">
  <div class="container container--wide">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow" style="justify-content:center">Paket Belajar</span>
      <h2 style="margin:.75rem 0 0">Pilih Paket <span class="underline-squiggle">Sesuai Targetmu</span></h2>
      <p>Pilih jumlah percobaan tryout, akses leaderboard, materi, dan latihan sesuai kebutuhanmu.</p>
    </div>

    <div class="grid grid-3" style="--gap:1.75rem;align-items:start">

      @forelse ($packages as $package)
      <article class="card card--pop price {{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::Local ? 'price--featured' : '' }} reveal" data-delay="{{ $loop->index * 90 }}">
        <div>
          <span class="badge {{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'badge--flat' : 'badge--premium' }}"><svg data-icon="{{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'check' : 'crown' }}"></svg> {{ $package->leaderboard_access->label() }}</span>
          <h3 class="card__title" style="margin-top:.6rem;font-size:var(--fs-lg)">{{ $package->name }}</h3>
          <p class="text-sm text-soft" style="margin-top:.6rem">{{ $package->description }}</p>
        </div>
        <div>
          <div class="price__amount">{{ $package->formattedPrice() }}</div>
          <div class="price__period">{{ $package->duration_days === null ? 'Tanpa batas waktu' : 'Akses '.$package->durationLabel() }}</div>
        </div>
        <ul class="price__list">
          <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'tryout')->count() }} paket soal tryout</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_quota === null ? 'Percobaan tryout tanpa batas' : $package->tryout_quota.' percobaan tryout' }}</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Kunci jawaban dan pembahasan</li>
          @if ($package->materials->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->materials->count() }} materi &amp; video premium</li>@endif
          @if ($package->exams->where('kind', 'quiz')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'quiz')->count() }} latihan soal</li>@endif
          @if ($package->leaderboard_access === \App\Enums\LeaderboardAccess::National)<li><span class="price__check"><svg data-icon="check"></svg></span> Progres belajar &amp; statistik nilai</li>@endif
        </ul>
        <div class="price__foot">
          @if ($package->hasAvailableContent())
          <a class="btn btn--primary btn--block" href="{{ route('checkout', ['package' => $package->id]) }}">Pilih {{ $package->name }}<span class="btn__icon"><svg data-icon="arrow-right"></svg></span></a>
          @else
          <p class="text-sm text-soft" style="margin-bottom:.75rem">Hubungi admin untuk ketersediaan konten paket ini.</p>
          <a class="btn btn--ghost btn--block" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a>
          @endif
        </div>
      </article>
      @empty
      <div class="card price"><h3 class="card__title">Konsultasi Paket Belajar</h3><p class="text-sm text-soft" style="margin-top:.6rem">Hubungi admin untuk menyesuaikan paket dengan kebutuhan belajarmu.</p><a class="btn btn--primary" style="margin-top:1rem" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a></div>
      @endforelse
    </div>

    <p class="text-center text-xs text-soft" style="margin-top:1.75rem">Harga dan isi paket mengikuti katalog aktif yang dikelola admin.</p>
  </div>
</section>

{{-- ===================== LEADERBOARD ===================== --}}
<section class="section section--alt">
  <div class="container container--wide">
    <div class="split">
      <div class="reveal"><span class="eyebrow">Leaderboard</span><h2 style="margin:.75rem 0 1rem">Pantau Peringkat <span class="underline-squiggle">dan Perkembanganmu</span></h2><p class="lead">Bandingkan poin tryout sesuai acuan tryout aktif. Akses lokal mengikuti provinsimu; paket nasional juga membuka peringkat seluruh Indonesia.</p><a class="btn btn--primary" style="margin-top:1.5rem" href="{{ route('leaderboard.index') }}">Lihat Leaderboard<svg data-icon="arrow-right"></svg></a></div>
      <div class="card reveal"><h3 class="card__title">Hasil belajar yang bisa ditinjau kembali</h3><p class="text-soft" style="margin-top:.75rem">Buka riwayat untuk meninjau poin per bidang, jawaban, dan hasil sesi. Statistik membantu menentukan materi yang perlu dipelajari berikutnya.</p><a class="btn btn--ghost" style="margin-top:1.25rem" href="{{ route('statistics.index') }}">Buka Statistik<svg data-icon="chart"></svg></a></div>
    </div>
  </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section class="section section--alt" id="faq">
  <div class="container" style="max-width:860px">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow" style="justify-content:center">Pertanyaan Umum</span>
      <h2 style="margin:.75rem 0 0">Yang <span class="underline-squiggle">Paling Sering Ditanya</span></h2>
    </div>

    <div class="acc" data-accordion="single">
      <div class="acc-item reveal">
        <button class="acc-trigger" aria-expanded="true" aria-controls="f1">Apakah materi dan soal ini gratis?<svg data-icon="chevron-down" class="acc-icon"></svg></button>
        <div class="acc-panel is-open" id="f1">Ada materi dasar dan soal latihan gratis. Materi premium (video, modul lengkap, tryout tambahan) terbuka setelah kamu punya paket yang aktif.</div>
      </div>
      <div class="acc-item reveal" data-delay="60">
        <button class="acc-trigger" aria-expanded="false" aria-controls="f2">Bagaimana cara mengikuti Try Out CAT?<svg data-icon="chevron-down" class="acc-icon"></svg></button>
        <div class="acc-panel" id="f2">Masuk ke menu Tryout CAT, pilih tryout yang tersedia, lalu klik Mulai. Timer langsung berjalan dan jawaban tersimpan otomatis.</div>
      </div>
      <div class="acc-item reveal" data-delay="120">
        <button class="acc-trigger" aria-expanded="false" aria-controls="f3">Kalau waktu habis saat mengerjakan?<svg data-icon="chevron-down" class="acc-icon"></svg></button>
        <div class="acc-panel" id="f3">Sistem melakukan submit otomatis. Nilai dan kunci jawaban tetap bisa kamu lihat; pembahasan tersedia dengan paket berbayar aktif.</div>
      </div>
      <div class="acc-item reveal" data-delay="180">
        <button class="acc-trigger" aria-expanded="false" aria-controls="f4">Apakah saya bisa melakukan refund?<svg data-icon="chevron-down" class="acc-icon"></svg></button>
        <div class="acc-panel" id="f4">Untuk kendala transaksi atau permintaan pengembalian dana, <a class="text-primary fw-700" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a> dengan nomor pesanan dan rincian kendala agar transaksi dapat diperiksa.</div>
      </div>
      <div class="acc-item reveal" data-delay="240">
        <button class="acc-trigger" aria-expanded="false" aria-controls="f5">Apakah bisa belajar lewat HP?<svg data-icon="chevron-down" class="acc-icon"></svg></button>
        <div class="acc-panel" id="f5">Bisa. Seluruh halaman dibuat responsive untuk desktop, tablet, dan smartphone.</div>
      </div>
    </div>
  </div>
</section>

{{-- ===================== CTA ===================== --}}
<section class="section section--tight">
  <div class="container container--wide">
    <div class="card card--pop pattern-dots-primary" style="text-align:center;padding:clamp(2rem,6vw,3.5rem);border-radius:var(--r-lg)">
      <span class="deco deco--circle" style="width:120px;height:120px;background:var(--pop-amber);top:-40px;left:-30px"></span>
      <span class="deco deco--leaf" style="width:90px;height:90px;background:var(--pop-pink);bottom:-30px;right:-20px"></span>
      <h2 style="position:relative">Siap Mulai <span class="underline-squiggle">Belajar?</span></h2>
      <p class="lead" style="margin:.85rem auto 1.75rem;position:relative">Daftar gratis, coba tryout pertama, dan lihat sendiri perkembangan nilaimu.</p>
      <div class="btn-row justify-center" style="position:relative">
        <a class="btn btn--primary btn--lg" href="{{ route('register') }}">Daftar Gratis<span class="btn__icon"><svg data-icon="arrow-right"></svg></span></a>
        <a class="btn btn--ghost btn--lg" href="{{ route('login') }}">Saya Sudah Punya Akun</a>
      </div>
    </div>
  </div>
</section>


@endsection
