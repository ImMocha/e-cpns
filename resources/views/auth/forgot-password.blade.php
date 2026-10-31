@extends('layouts.guest')

@section('title', 'Lupa Password — Ganesha Nusa')

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
    <h1 style="font-size:var(--fs-2xl);margin:.75rem 0">Lupa password?</h1>
    <p class="text-soft" style="margin-bottom:1.5rem">Masukkan email akunmu. Kami akan mengirim tautan untuk membuat password baru.</p>

    @if (session('status'))
      <div class="alert alert--success" role="status" style="margin-bottom:1.25rem">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
      @csrf
      <div class="stack" style="--stack:1.15rem">
        <div class="field{{ $errors->has('email') ? ' is-invalid' : '' }}">
          <label class="label" for="email">Email</label>
          <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="email" aria-describedby="email-error" @error('email') aria-invalid="true" @enderror>
          <span class="field__error" id="email-error">{{ $errors->first('email') }}</span>
        </div>
        <button class="btn btn--primary btn--lg btn--block" type="submit">Kirim tautan reset password</button>
      </div>
    </form>
  </section>
</main>
@endsection
