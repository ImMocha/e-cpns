@extends('layouts.guest')

@section('title', 'Masuk — Ganesha Nusa CPNS Online')

@section('body-attributes')
    style="min-height:100dvh;display:flex;flex-direction:column"
@endsection

@section('content')
<!-- ===================== HEADER ===================== -->
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
    <span class="eyebrow">Selamat Datang Kembali</span>
    <h1 style="font-size:var(--fs-2xl);margin:.75rem 0 .5rem">Masuk ke Akunmu</h1>
    <p class="text-soft" style="margin-bottom:1.75rem">Lanjutkan belajar dan lanjutkan tryout-mu.</p>

    <form class="card card--pop" method="POST" action="{{ route('login.store') }}" novalidate>
      @csrf
      @if (session('status'))
        <div class="alert alert--success" role="status" style="margin-bottom:1.25rem">{{ session('status') }}</div>
      @endif
      <div class="stack" style="--stack:1.15rem">

        <div class="field{{ $errors->has('email') ? ' is-invalid' : '' }}">
          <label class="label" for="email">Email</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="mail"></svg></span>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@email.com" required autofocus autocomplete="email"
                   @error('email') aria-invalid="true" @enderror>
          </div>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('email') ?? 'Wajib diisi.' }}</span></span>
        </div>

        <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
          <label class="label" for="password">Password</label>
          <div class="input-group">
            <span class="input-group__icon"><svg data-icon="lock"></svg></span>
            <input class="input" type="password" id="password" name="password"
                   placeholder="Masukkan password" required autocomplete="current-password" style="padding-right:3.25rem"
                   @error('password') aria-invalid="true" @enderror>
            <button class="input-group__action" type="button" data-toggle-password="#password" aria-label="Tampilkan sandi">
              <svg data-icon="eye"></svg>
            </button>
          </div>
          <span class="field__error"><svg data-icon="alert"></svg><span>{{ $errors->first('password') ?? 'Wajib diisi.' }}</span></span>
        </div>

        <div class="flex items-center justify-between flex-wrap" style="gap:.75rem">
          <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Ingat saya</span>
          </label>
          <a class="text-sm fw-700 text-primary" href="{{ route('password.request') }}">Lupa password?</a>
        </div>

        <button class="btn btn--primary btn--lg btn--block" type="submit">
          Masuk
          <span class="btn__icon"><svg data-icon="arrow-right"></svg></span>
        </button>

        <p class="text-center text-sm text-soft">
          Belum punya akun? <a class="fw-700 text-primary" href="{{ route('register') }}">Daftar gratis</a>
        </p>
      </div>
    </form>

    <div class="alert alert--info" style="margin-top:1.25rem">
      <span class="alert__icon"><svg data-icon="info"></svg></span>
      <div>
        <strong>Akun demo</strong>
        Gunakan email akun admin atau peserta yang sudah tersedia &mdash; password <code class="text-mono text-xs">password</code>.
      </div>
    </div>
  </div>

  <!-- ===================== SIDE PANEL ===================== -->
  <aside class="split-media reveal auth-side">
    <div class="split-media__blob" style="background:var(--primary-100)" aria-hidden="true"></div>
    <div class="card card--pad-0" style="position:relative;z-index:1;border-radius:var(--r-lg);overflow:hidden">
      <div style="aspect-ratio:4/3;overflow:hidden;border-bottom:2px solid var(--ink)">
        <img src="{{ asset('images/hero-tryout.jpg') }}" alt="Ilustrasi peserta belajar prepping CPNS" style="width:100%;height:100%;object-fit:cover" width="960" height="720">
      </div>
      <div style="padding:1.75rem">
        <blockquote>
          <svg data-icon="quote" style="width:26px;height:26px;color:var(--primary-300)"></svg>
          <p style="margin:.75rem 0 1.25rem;font-size:var(--fs-lg);line-height:1.55;color:var(--ink);font-family:var(--font-display);font-weight:600">
            "Nilai tryout pertamaku 312, yang sekarang 452. Yang berubah cuma cara mengatur waktu pengerjaan."
          </p>
          <footer class="userchip">
            <span class="avatar avatar--amber">D</span>
            <div><strong>Dewi Anggraini</strong><span>Peserta Ultimate · 12 tryout</span></div>
          </footer>
        </blockquote>
      </div>
    </div>

    <div class="card card--tight card--pop" style="position:absolute;right:-20px;top:24px;z-index:3;max-width:200px">
      <span class="badge badge--mint badge--flat"><svg data-icon="check-circle"></svg> Autosave aktif</span>
      <p class="text-xs text-soft" style="margin-top:.5rem">Progress tryout tidak hilang meski tab tertutup.</p>
    </div>
  </aside>
</main>

<footer class="footer auth-footer" style="padding-block:1.5rem">
  <div class="container container--wide flex items-center justify-between flex-wrap" style="gap:1rem">
    <span class="text-sm">© <span data-year>2026</span> Ganesha Nusa · Prototipe demonstrasi.</span>
    <div class="footer__links">
      <a href="#">Syarat &amp; Ketentuan</a>
      <a href="#">Kebijakan Privasi</a>
      <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
    </div>
  </div>
</footer>

@endsection
