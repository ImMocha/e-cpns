@extends('layouts.exam')
@section('title', $attempt->title.' — LONTAR CPNS Online')
@section('exam-bar')
<header class="tryout-bar">
  <div class="container container--wide tryout-bar__inner">
    <a class="logo" href="{{ route($attempt->kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}" aria-label="Daftar sesi"><span class="logo__mark"><svg data-icon="book"></svg></span></a>
    <div class="tryout-bar__meta"><strong>{{ $attempt->title }}</strong><span class="exam-note">{{ count($clientConfig['questions']) }} soal · {{ $clientConfig['showTimer'] ? 'Waktu tetap berjalan saat keluar' : 'Tanpa batas waktu' }}</span></div>
    <div class="tryout-bar__right">
      @if ($clientConfig['showTimer'])<div class="timer" id="timerWrap"><svg data-icon="timer"></svg><span id="timer">--:--:--</span></div>@endif
      <button class="btn btn--ghost btn--sm" type="button" id="exitBtn">Keluar</button>
      <button class="btn btn--primary btn--sm" type="button" id="submitBtn">Kumpulkan</button>
    </div>
  </div>
</header>
@endsection
@section('content')
<main class="container container--wide tryout-layout" id="main">
  <div id="questionStage"><noscript><div class="alert alert--danger">Aktifkan JavaScript untuk mengerjakan soal dan menyimpan jawaban.</div></noscript></div>
  <aside class="qnav" aria-label="Navigasi nomor soal">
    <div class="card card--tight">
      <div class="card__head"><div><div class="card__title">Nomor Soal</div><div class="card__sub">Pilih nomor untuk berpindah</div></div></div>
      <button class="btn btn--ghost btn--sm" type="button" id="filterUnanswered" aria-pressed="false" style="margin-bottom:1rem">Hanya belum dijawab</button>
      <div class="palette" id="palette"></div>
      <div class="legend" style="margin-top:1rem;padding-top:1rem;border-top:2px dashed var(--border)">
        <div class="legend__item"><span class="legend__swatch legend__swatch--answered"></span> Dijawab</div>
        <div class="legend__item"><span class="legend__swatch"></span> Kosong</div>
        <div class="legend__item"><span class="legend__swatch legend__swatch--flagged"></span> Ditandai</div>
        <div class="legend__item"><span class="legend__swatch legend__swatch--current"></span> Aktif</div>
      </div>
      <div class="kv" style="margin-top:1rem">
        <div class="kv__row"><span class="kv__key">Dijawab</span><span class="kv__val" id="statAnswered">0/{{ count($clientConfig['questions']) }}</span></div>
        <div class="kv__row"><span class="kv__key">Kosong</span><span class="kv__val" id="statLeft">{{ count($clientConfig['questions']) }}</span></div>
        <div class="kv__row"><span class="kv__key">Ditandai</span><span class="kv__val" id="statFlagged">0</span></div>
      </div>
    </div>
    <div class="card card--tight" style="margin-top:1.25rem">
      <p class="text-sm" id="connectionStatus" role="status" aria-live="polite">Memuat status penyimpanan…</p>
      <button class="btn btn--ghost btn--sm" type="button" id="saveNow" style="margin-top:.65rem">Simpan ulang</button>
      <p class="text-xs text-soft" style="margin-top:.75rem">Jawaban disimpan otomatis. Saat offline, perubahan disimpan sementara di perangkat ini dan dikirim saat koneksi pulih.</p>
    </div>
    <div class="card card--tight card--dashed" style="margin-top:1.25rem">
      <strong>Pintasan keyboard</strong>
      <p class="text-xs text-soft" style="margin-top:.6rem">A–J: pilihan jawaban · ← →: pindah soal · Shift+F: tandai. Pembahasan tersedia setelah dikumpulkan.</p>
    </div>
  </aside>
</main>
<script id="quiz-data" type="application/json">@json($clientConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection
