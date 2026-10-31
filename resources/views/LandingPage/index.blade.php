@extends('layouts.public')

@section('title', 'Ganesha Nusa — Belajar & Tryout Online')

@section('content')
{{-- ===================== HERO ===================== --}}
<section class="hero">
  <div class="hero__bg deco"></div>
  <div class="hero__bg-2 deco"></div>
  <div class="deco deco--circle pattern-dots-primary" style="width:180px;height:180px;top:14%;right:6%;opacity:.5" aria-hidden="true"></div>

  <div class="container container--wide">
    <div class="split">
      <div>
        <span class="eyebrow">Belajar untuk Berbagai Topik</span>
        <h1 style="margin:1rem 0 1.25rem">
          Belajar Lebih <span class="underline-squiggle">Cerdas</span>,<br>
          Hadapi Tryout <span style="color:var(--primary-700)">Tanpa Panik</span>.
        </h1>
        <p class="lead" style="margin-bottom:1.75rem">
          Materi PDF, video pembelajaran, latihan soal, dan simulasi Try Out CAT
          dengan timer, autosave, pembahasan, serta grafik perkembangan nilai.
          Semua dalam satu platform.
        </p>

        <div class="btn-row" style="margin-bottom:2rem">
          <a class="btn btn--primary btn--lg" href="{{ route('register') }}">
            Daftar Gratis Sekarang
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
            <div class="hero-stat__label">Soal Latihan</div>
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
          <img src="{{ asset('images/hero-general-learning.webp') }}" alt="Ilustrasi peserta belajar bersama dengan buku dan laptop" width="960" height="720">
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
<section class="section landing-scene landing-scene--features" id="fitur">
  <x-landing-accents />
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
          <span class="badge badge--primary badge--flat">Konten Premium</span>
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
        <h3>Latihan Soal & Pembahasan</h3>
        <p class="text-sm text-soft">Soal TWK, TIU, dan TKP dengan pembahasan serta latihan per kategori.</p>
        <div class="tile__foot">
          <span class="badge badge--amber badge--flat">{{ number_format($questionCount, 0, ",", ".") }} soal terbit</span>
          <a class="btn btn--ghost btn--sm" href="{{ route('quizzes.index') }}">Buka<svg data-icon="arrow-right"></svg></a>
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
    <div class="split split--equal">
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
              <span>2 · Uji pemahaman melalui Latihan Soal</span><svg data-icon="chevron-down" class="acc-icon"></svg>
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
        <div class="card card--pop learning-card">
          <div class="learning-card__heading">
            <div><span class="preview-eyebrow">Satu tempat, semua persiapan</span><h3 class="card__title">Ruang Belajar Kamu</h3></div>
            <span class="preview-emblem" aria-hidden="true"><svg data-icon="book"></svg></span>
          </div>
          <p class="text-soft learning-card__description">Dashboard mencatat aktivitas belajar dan hasil pengerjaan dari akunmu.</p>
          <div class="learning-metrics">
            <div><svg data-icon="book"></svg><strong>{{ $materialCount }}</strong><span>Materi terbit</span></div>
            <div><svg data-icon="layers"></svg><strong>{{ $questionCount }}</strong><span>Soal tersedia</span></div>
            <div><svg data-icon="timer"></svg><strong>{{ $tryoutCount }}</strong><span>Tryout aktif</span></div>
          </div>
          <div class="learning-preview" aria-label="Alur di ruang belajar">
            <div class="learning-preview__intro">
              <div class="learning-preview__copy">
                <div class="learning-preview__heading"><span>LANGKAH KECIL, TARGET BESAR</span></div>
                <p>Bangun ritme belajarmu.</p>
              </div>
            </div>
            <div class="learning-preview__steps">
              <div><span class="learning-preview__icon"><svg data-icon="book"></svg><small>01</small></span><strong>Pelajari</strong><span>Materi pilihan</span></div>
              <div><span class="learning-preview__icon"><svg data-icon="pencil"></svg><small>02</small></span><strong>Latih</strong><span>Uji pemahaman</span></div>
              <div><span class="learning-preview__icon"><svg data-icon="trending-up"></svg><small>03</small></span><strong>Evaluasi</strong><span>Tinjau hasilmu</span></div>
            </div>
          </div>
          <a class="btn btn--primary btn--block" href="{{ route('dashboard') }}" style="margin-top:1.25rem">Buka Dashboard<svg data-icon="arrow-right"></svg></a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ===================== TRYOUT DAN LATIHAN SOAL ===================== --}}
<section class="section" id="materi">
  <div class="container container--wide">
    <div class="section-head section-head--split reveal">
      <div><span class="eyebrow">Konten Belajar</span><h2 style="margin:.75rem 0 0">Tryout <span class="underline-squiggle">dan Latihan Soal</span></h2><p>Pilih sesi sesuai kebutuhanmu. Akses soal mengikuti paket aktif yang kamu miliki.</p></div>
      <a class="btn btn--ghost" href="{{ route('tryouts.index') }}">Semua Tryout<svg data-icon="arrow-right"></svg></a>
    </div>
    <div data-tabs>
      <div class="tabs" role="tablist" aria-label="Jenis sesi">
        <button class="tab is-active" data-tab="t-tryout" role="tab" aria-selected="true">Tryout</button>
        <button class="tab" data-tab="t-practice" role="tab" aria-selected="false">Latihan Soal</button>
      </div>
      @foreach ($featuredExams as $kind => $items)
      <div class="tab-panel{{ $loop->first ? ' is-active' : '' }}" id="t-{{ $kind }}" data-tab-panel>
        <div class="grid-auto grid-auto--lg">
          @forelse ($items as $item)
          @php($exam = $item['exam'])
          <article class="card card--pad-0 tile reveal">
            <div class="tile__media">
              <img class="h-full w-full object-cover" src="{{ $exam->coverUrl() }}" alt="" width="960" height="540" loading="lazy" decoding="async">
              <span class="badge badge--{{ $item['hasAccess'] ? 'mint' : 'primary' }} tile__badge">@unless ($item['hasAccess'])<svg data-icon="lock"></svg> Terkunci · @endunless{{ $exam->accessLabel() }}</span>
            </div>
            <div class="tile__body">
              <span class="tile__cat">{{ $kind === 'tryout' ? 'Tryout CAT' : 'Latihan Soal' }}@if ($exam->topic) · {{ $exam->topic->section->code }} · {{ $exam->topic->name }}@endif</span>
              <h3 class="tile__title">{{ $exam->title }}</h3>
              <p class="tile__desc">{{ $exam->description }}</p>
              <p class="text-xs text-soft">{{ $exam->question_count }} soal · {{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</p>
              <div class="tile__foot">
                <span class="text-xs text-soft">{{ $item['hasAccess'] ? 'Akses tersedia' : ($exam->free_claim_enabled ? 'Penuhi syarat untuk membuka' : 'Aktifkan paket yang sesuai') }}</span>
                <a class="btn btn--sm btn--primary" href="{{ route($kind === 'tryout' ? 'tryouts.index' : 'quizzes.index') }}">Lihat Sesi<svg data-icon="{{ $item['hasAccess'] ? 'arrow-right' : 'lock' }}"></svg></a>
              </div>
            </div>
          </article>
          @empty
          <div class="card"><h3 class="card__title">{{ $kind === 'tryout' ? 'Tryout' : 'Latihan Soal' }} segera hadir</h3><p class="text-soft" style="margin-top:.75rem">Sesi akan tampil setelah diterbitkan. Hubungi kami untuk informasi paket belajar.</p><a class="btn btn--ghost btn--sm" style="margin-top:1rem" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a></div>
          @endforelse
        </div>
        <a class="btn btn--ghost" style="margin-top:1.5rem" href="{{ route($kind === 'tryout' ? 'tryouts.index' : 'quizzes.index') }}">{{ $kind === 'tryout' ? 'Semua Tryout' : 'Semua Latihan Soal' }}<svg data-icon="arrow-right"></svg></a>
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
        <p style="max-width:52ch">Panduan dan tips yang bisa kamu baca kapan saja selama perjalanan belajar.</p>
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
            <div class="article-card-meta"><span><x-local-time :value="$article->published_at" format="d M Y" /></span><span>{{ $article->readingMinutes() }} menit baca</span></div>
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
    <div class="split split--equal tryout-showcase">
      <div class="reveal tryout-showcase__demo">
        @include('LandingPage._tryout-demo')
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
<section class="section landing-scene landing-scene--packages" id="paket">
  <x-landing-accents />
  <div class="container container--wide">
    <div class="section-head section-head--center reveal">
      <span class="eyebrow" style="justify-content:center">Paket Belajar</span>
      <h2 style="margin:.75rem 0 0">Pilih Paket <span class="underline-squiggle">Sesuai Targetmu</span></h2>
      <p>Pilih jumlah percobaan tryout, akses leaderboard, materi, dan latihan sesuai kebutuhanmu.</p>
    </div>

    <div class="grid grid-3 price-grid" style="--gap:1.75rem">

      @forelse ($packages as $package)
      <article class="card card--pop price {{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::Local ? 'price--featured' : '' }} reveal" data-delay="{{ $loop->index * 90 }}">
        <div>
          <span class="badge {{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'badge--flat' : 'badge--premium' }}"><svg data-icon="{{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'check' : 'crown' }}"></svg> {{ $package->leaderboard_access->label() }}</span>
          <h3 class="card__title" style="margin-top:.6rem;font-size:var(--fs-lg)">{{ $package->name }}</h3>
          <p class="text-sm text-soft" style="margin-top:.6rem">{{ $package->description }}</p>
        </div>
        <div>
          <x-package-original-price :package="$package" />
          <div class="price__amount">{{ $package->formattedPrice() }}</div>

          <div class="price__period">{{ $package->duration_days === null ? 'Tanpa batas waktu' : 'Akses '.$package->durationLabel() }}</div>
        </div>
        @if ($package->tier)
        <x-package-benefits :package="$package" />
        @else
        <ul class="price__list">
          @if ($package->tryout_tokens === 0 || $package->exams->where('kind', 'tryout')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'tryout')->count() }} paket soal tryout</li>@endif
          @if ($package->tryout_tokens > 0)<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_tokens }} token untuk {{ $package->tryout_tokens }} tryout pilihanmu</li>@endif
          <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_quota === null ? 'Percobaan tryout tanpa batas' : $package->tryout_quota.' percobaan tryout' }}</li>
          @if ($package->price > 0)<li><span class="price__check"><svg data-icon="check"></svg></span> Kunci jawaban dan pembahasan</li>@endif
          @if ($package->materials->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->materials->count() }} materi &amp; video premium</li>@endif
          @if ($package->exams->where('kind', 'practice')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'practice')->count() }} latihan soal</li>@endif
          @if ($package->exams->where('kind', 'quiz')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'quiz')->count() }} latihan topik</li>@endif
          @if ($package->leaderboard_access === \App\Enums\LeaderboardAccess::National)<li><span class="price__check"><svg data-icon="check"></svg></span> Progres belajar &amp; statistik nilai</li>@endif
        </ul>
        @endif
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
    <div class="split split--equal">
      <div class="reveal"><span class="eyebrow">Leaderboard</span><h2 style="margin:.75rem 0 1rem">Pantau Peringkat <span class="underline-squiggle">dan Perkembanganmu</span></h2><p class="lead">Bandingkan poin tryout sesuai acuan tryout aktif. Akses lokal mengikuti provinsimu; paket nasional juga membuka peringkat seluruh Indonesia.</p><a class="btn btn--primary" style="margin-top:1.5rem" href="{{ route('leaderboard.index') }}">Lihat Leaderboard<svg data-icon="arrow-right"></svg></a></div>
      <div class="card reveal results-preview">
        <h3 class="card__title"><span class="results-preview__icon" aria-hidden="true"><svg data-icon="chart"></svg></span>Hasil belajar yang bisa ditinjau kembali</h3>
        <p class="text-soft" style="margin-top:.75rem">Buka riwayat untuk meninjau poin per bidang, jawaban, dan hasil sesi. Statistik membantu menentukan materi yang perlu dipelajari berikutnya.</p>
        <div class="results-preview__visual">
          <div class="results-preview__caption"><span><svg data-icon="trending-up"></svg> Setiap sesi, selangkah lebih siap.</span><span>Ilustrasi statistik</span></div>
          <svg class="results-preview__chart" viewBox="0 0 480 72" fill="none" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 18H480M0 42H480M0 70H480" stroke="var(--border-strong)" stroke-dasharray="3 7" />
            <path d="M8 59C48 59 54 33 88 39S142 61 174 36S224 42 263 25S309 36 347 19S405 26 468 7V72H8Z" fill="var(--primary-100)" />
            <path d="M8 65C54 65 59 53 96 58S151 45 188 51S245 38 282 42S347 35 379 39S426 24 468 27" stroke="var(--pop-mint)" stroke-width="2" stroke-linecap="round" stroke-dasharray="5 5" vector-effect="non-scaling-stroke" />
            <path d="M8 59C48 59 54 33 88 39S142 61 174 36S224 42 263 25S309 36 347 19S405 26 468 7" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            <circle cx="468" cy="7" r="6" fill="var(--surface)" stroke="var(--primary)" stroke-width="3" vector-effect="non-scaling-stroke" />
          </svg>
        </div>
        <a class="btn btn--ghost" style="margin-top:1.25rem" href="{{ route('statistics.index') }}">Buka Statistik<svg data-icon="chart"></svg></a>
      </div>
    </div>
  </div>
</section>

@include('LandingPage._testimonials')

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
        <div class="acc-panel is-open" id="f1">Seluruh materi, latihan soal, dan tryout menggunakan akses Premium. Katalog tetap dapat dilihat sebelum membeli paket. Paket Reguler dan Bundling khusus akses tryout, tanpa materi dan latihan soal. Pilih Premium untuk materi lengkap SKD, latihan soal, progress belajar, statistik nilai, dan sertifikat.</div>
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
    <div class="card card--pop landing-cta">
      <div class="landing-cta__copy">
        <h2>Siap Mulai <span class="underline-squiggle">Belajar?</span></h2>
        <p class="lead">Daftar gratis, pilih paket belajar, lalu mulai tryout dan pantau perkembangan nilaimu.</p>
        <div class="btn-row">
          <a class="btn btn--primary btn--lg" href="{{ route('register') }}">Daftar Gratis<span class="btn__icon"><svg data-icon="arrow-right"></svg></span></a>
          <a class="btn btn--ghost btn--lg" href="{{ route('login') }}">Saya Sudah Punya Akun</a>
        </div>
      </div>
      <div class="landing-cta__art" aria-hidden="true">
        <img src="{{ asset('images/landing-welcome-companion.webp') }}" alt="" width="320" height="480" loading="lazy" decoding="async">
      </div>
    </div>
  </div>
</section>


@endsection
