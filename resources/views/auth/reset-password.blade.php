@extends('layouts.guest')

@section('title', 'Reset Password — Ganesha Nusa')

@section('content')
<header class="nav auth-nav">
  <div class="container container--wide nav__inner">
    <a class="logo" href="{{ route('home') }}"><x-logo /></a>
    <a class="btn btn--ghost btn--sm" href="{{ route('login') }}">Kembali ke login</a>
  </div>
</header>

<main id="main" class="container" style="min-height:70vh;display:grid;place-items:center;padding-block:3rem">
  <section class="card card--pop" style="width:min(100%,520px)">
    <span class="eyebrow">Pulihkan akunmu</span>
    <h1 style="font-size:var(--fs-2xl);margin:.75rem 0">Buat password baru</h1>
    <p class="text-soft" style="margin-bottom:1.5rem">Gunakan minimal 8 karakter untuk password barumu.</p>

    @error('token')
      <div class="alert alert--danger" role="alert" style="margin-bottom:1.25rem">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('password.update') }}" novalidate>
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <div class="stack" style="--stack:1.15rem">
        <div class="field{{ $errors->has('email') ? ' is-invalid' : '' }}">
          <label class="label" for="email">Email</label>
          <input class="input" type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" aria-describedby="email-error" @error('email') aria-invalid="true" @enderror>
          <span class="field__error" id="email-error">{{ $errors->first('email') }}</span>
        </div>
        <div class="field{{ $errors->has('password') ? ' is-invalid' : '' }}">
          <label class="label" for="password">Password baru</label>
          <input class="input" type="password" id="password" name="password" required minlength="8" autofocus autocomplete="new-password" aria-describedby="password-error" @error('password') aria-invalid="true" @enderror>
          <span class="field__error" id="password-error">{{ $errors->first('password') }}</span>
        </div>
        <div class="field">
          <label class="label" for="password_confirmation">Konfirmasi password baru</label>
          <input class="input" type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Simpan password baru</button>
        <p class="text-sm text-center"><a class="fw-700 text-primary" href="{{ route('password.request') }}">Minta tautan reset baru</a></p>
      </div>
    </form>
  </section>
</main>
@endsection
