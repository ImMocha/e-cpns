@extends('layouts.guest')

@section('title', 'Daftar — Ganesha Nusa')

@section('body-attributes')
    style="min-height:100dvh;display:flex;flex-direction:column"
@endsection

@push('head')
<style>
  .meter { display: grid; gap: .35rem; }
  .meter__bars { display: grid; grid-template-columns: repeat(4,1fr); gap: .3rem; }
  .meter__bars span { height: 7px; border-radius: 99px; background: var(--border); border: 2px solid var(--border-strong); transition: background-color var(--t); }
  .meter[data-level="1"] .meter__bars span:nth-child(-n+1) { background: var(--danger); border-color: var(--ink); }
  .meter[data-level="2"] .meter__bars span:nth-child(-n+2) { background: var(--warning); border-color: var(--ink); }
  .meter[data-level="3"] .meter__bars span:nth-child(-n+3) { background: var(--pop-amber); border-color: var(--ink); }
  .meter[data-level="4"] .meter__bars span { background: var(--pop-mint); border-color: var(--ink); }
</style>
@endpush

@section('content')
<header class="nav auth-nav">
  <div class="container container--wide nav__inner">
    <a class="logo" href="{{ route('home') }}">
      <x-logo />
    </a>
    <a class="btn btn--ghost btn--sm auth-back" href="{{ route('home') }}" aria-label="Kembali ke beranda">
      <svg data-icon="arrow-left"></svg><span class="auth-back__full">Kembali ke beranda</span><span class="auth-back__short" aria-hidden="true">Beranda</span>
    </a>
  </div>
</header>

<main id="main" class="container container--wide panes panes--center auth-layout">

  <!-- ===================== FORM ===================== -->
  <div class="auth-form-column">
    <span class="eyebrow">Gratis Selamanya</span>
    <h1 style="font-size:var(--fs-2xl);margin:.75rem 0 .5rem">Buat Akun Peserta</h1>
    <p class="text-soft" style="margin-bottom:1.75rem">Cukup satu email untuk mulai belajar dan mengikuti tryout.</p>

    <form class="card card--pop" method="POST" action="{{ route('register.store') }}">
      @csrf
      <div class="stack" style="--stack:1.15rem">

        <div class="field{{ $errors->has('name') ? ' is-invalid' : '' }}">
          <label class="label" for="nama">Nama Lengkap</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="user"></svg></span>
            <input class="input" type="text" id="nama" name="name" value="{{ old('name') }}" placeholder="Contoh: Rani Kusuma" required maxlength="160" autocomplete="name" @error('name') aria-invalid="true" @enderror>
          </div>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('name') ?? 'Wajib diisi.' }}</span></span>
        </div>

        <x-province-select />

        <div class="field{{ $errors->has('email') ? ' is-invalid' : '' }}">
          <label class="label" for="email">Email</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="mail"></svg></span>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email" @error('email') aria-invalid="true" @enderror>
          </div>
          <span class="field__hint">Dipakai untuk masuk dan menerima info tryout baru.</span>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('email') ?? 'Wajib diisi.' }}</span></span>
        </div>

        <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
          <label class="label" for="password">Password</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="lock"></svg></span>
            <input class="input" type="password" id="password" name="password" placeholder="Minimal 8 karakter" required minlength="8" autocomplete="new-password" style="padding-right:3.25rem" @error('password') aria-invalid="true" @enderror>
            <button class="input-group__action" type="button" data-toggle-password="#password" aria-label="Tampilkan sandi">
              <svg data-icon="eye"></svg>
            </button>
          </div>
          <div class="meter" id="pwMeter" data-level="0" aria-live="polite">
            <div class="meter__bars"><span></span><span></span><span></span><span></span></div>
            <span class="text-xs text-soft" id="pwLabel">Gunakan minimal 8 karakter, kombinasi huruf dan angka.</span>
          </div>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('password') ?? 'Wajib diisi.' }}</span></span>
        </div>

        <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
          <label class="label" for="password2">Konfirmasi Password</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="key"></svg></span>
            <input class="input" type="password" id="password2" name="password_confirmation" placeholder="Ulangi password" required minlength="8" autocomplete="new-password" style="padding-right:3.25rem">
            <button class="input-group__action" type="button" data-toggle-password="#password2" aria-label="Tampilkan sandi">
              <svg data-icon="eye"></svg>
            </button>
          </div>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('password') ?? 'Konfirmasi password tidak sama.' }}</span></span>
        </div>

        <label class="check{{ $errors->has('terms') ? ' is-invalid' : '' }}">
          <input type="checkbox" id="terms" name="terms" value="1" required @checked(old('terms'))>
          <span>Saya setuju dengan <a class="fw-700 text-primary" href="{{ route('terms') }}" data-legal-open="terms" aria-haspopup="dialog" aria-controls="legal-terms">Syarat &amp; Ketentuan</a> serta <a class="fw-700 text-primary" href="{{ route('privacy') }}" data-legal-open="privacy" aria-haspopup="dialog" aria-controls="legal-privacy">Kebijakan Privasi</a> Ganesha Nusa.</span>
        </label>
        @error('terms')<span class="field__error" style="display:flex"><svg data-icon="alert"></svg><span>{{ $message }}</span></span>@enderror

        <button class="btn btn--primary btn--lg btn--block" type="submit">
          Buat Akun Gratis
          <span class="btn__icon"><svg data-icon="arrow-right"></svg></span>
        </button>

        <p class="text-center text-sm text-soft">
          Sudah punya akun? <a class="fw-700 text-primary" href="{{ route('login') }}">Masuk di sini</a>
        </p>
      </div>
    </form>

    <div class="alert alert--info" style="margin-top:1.25rem">
      <span class="alert__icon"><svg data-icon="info"></svg></span>
      <div>
        <strong>Verifikasi email</strong>
        Setelah akun dibuat, kami mengirim tautan verifikasi ke email kamu sebelum dashboard dapat digunakan.
      </div>
    </div>
  </div>

  <!-- ===================== SIDE PANEL ===================== -->
  <aside class="reveal auth-side" id="sidePanel">
    <div class="card card--pop" style="position:relative">
      <span class="deco deco--circle" style="width:110px;height:110px;background:var(--pop-amber);top:-40px;right:-30px"></span>
      <span class="eyebrow">Setelah Daftar</span>
      <h2 style="margin:.75rem 0 1.25rem;font-size:var(--fs-xl)">Setelah Daftar, Kamu Bisa:</h2>

      <div class="stack" style="--stack:.85rem">
        <div class="flex items-start gap-3">
          <span class="price__check"><svg data-icon="check"></svg></span>
          <div><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Materi dasar gratis</strong><span class="text-sm text-soft">PDF ringkasan TWK, TIU, dan TKP.</span></div>
        </div>
        <div class="flex items-start gap-3">
          <span class="price__check"><svg data-icon="check"></svg></span>
          <div><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Latihan soal terbatas</strong><span class="text-sm text-soft">Kerjakan sesi latihan per topik beserta kunci jawaban. Pembahasan tersedia dengan paket berbayar aktif.</span></div>
        </div>
        <div class="flex items-start gap-3">
          <span class="price__check"><svg data-icon="check"></svg></span>
          <div><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Dashboard progres</strong><span class="text-sm text-soft">Pantau materi selesai, nilai, dan riwayat tryout.</span></div>
        </div>
        <div class="flex items-start gap-3">
          <span class="price__check"><svg data-icon="check"></svg></span>
          <div><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Naik kelas kapan saja</strong><span class="text-sm text-soft">Beli paket belajar kapan kamu siap.</span></div>
        </div>
      </div>

      <p class="text-sm text-soft" style="margin-top:1.5rem">Butuh bantuan pendaftaran? <a class="text-primary fw-700" href="{{ config('app.contact_whatsapp') }}" target="_blank" rel="noopener noreferrer">Hubungi Kami</a>.</p>
    </div>
  </aside>
</main>

<footer class="footer auth-footer" style="padding-block:1.5rem">
  <div class="container container--wide flex items-center justify-between flex-wrap" style="gap:1rem">
    <span class="text-sm">© <span data-year>2026</span> Ganesha Nusa. Hak cipta dilindungi.</span>
    <div class="footer__links">
      <a href="{{ route('terms') }}" data-legal-open="terms" aria-haspopup="dialog" aria-controls="legal-terms">Syarat &amp; Ketentuan</a>
      <a href="{{ route('privacy') }}" data-legal-open="privacy" aria-haspopup="dialog" aria-controls="legal-privacy">Kebijakan Privasi</a>
      <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
    </div>
  </div>
</footer>

<script>
  /* Indikator kekuatan password */
  (function () {
    var pw = document.getElementById('password');
    var meter = document.getElementById('pwMeter');
    var label = document.getElementById('pwLabel');
    if (!pw || !meter) return;
    var COPY = [
      'Gunakan minimal 8 karakter, kombinasi huruf dan angka.',
      'Masih lemah. Tambahkan angka atau huruf besar.',
      'Cukup. Tambahkan simbol untuk lebih aman.',
      'Bagus. Sedikit lagi jadi sangat kuat.',
      'Sangat kuat. Password ini aman dipakai.'
    ];
    pw.addEventListener('input', function () {
      var v = pw.value;
      var score = 0;
      if (v.length >= 8) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^\w\s]/.test(v) || v.length >= 14) score++;
      meter.dataset.level = v ? String(score) : '0';
      label.textContent = COPY[v ? score : 0];
    });
  })();
</script>
@endsection
