@extends('layouts.guest')

@section('title', 'Verifikasi Email — Ganesha Nusa CPNS Online')

@section('content')
<header class="nav">
  <div class="container container--wide nav__inner">
    <a class="logo" href="{{ route('home') }}">
      <x-logo />
    </a>
    <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
      @csrf
      <button class="btn btn--ghost btn--sm" type="submit">Keluar</button>
    </form>
  </div>
</header>

<main class="container" style="min-height:70vh;display:grid;place-items:center;padding-block:3rem">
  <section class="card card--pop" style="width:min(100%,600px)">
    <span class="eyebrow">Satu langkah lagi</span>
    <h1 style="font-size:var(--fs-2xl);margin:.75rem 0">Verifikasi email kamu</h1>
    <p class="text-soft" style="margin-bottom:1.5rem">
      Kami mengirim tautan verifikasi ke <strong>{{ auth()->user()->email }}</strong>.
      Buka tautan tersebut untuk mengaktifkan akses belajar dan tryout.
    </p>

    @if (session('status') === 'verification-link-sent')
      <div class="alert alert--success" role="status" style="margin-bottom:1.25rem">
        <span class="alert__icon"><svg data-icon="check-circle"></svg></span>
        <div>Tautan verifikasi baru sudah dikirim. Periksa juga folder spam kamu.</div>
      </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button class="btn btn--primary btn--lg btn--block" type="submit">
        Kirim ulang tautan verifikasi
        <span class="btn__icon"><svg data-icon="arrow-right"></svg></span>
      </button>
    </form>
  </section>
</main>
@endsection
