{{--
    Top bar Admin Panel.

    Identitas diambil dari user yang benar-benar login. Kalau halaman ini
    dirender tanpa sesi (mis. preview blade), tampil placeholder.
--}}
@php
    $user = auth()->user();
    $name = $user?->name ?? 'Tamu';
    $email = $user?->email ?? 'belum masuk';
    $initials = $user?->initials() ?? '?';
    $tone = $user?->avatarTone() ?? 'ink';
@endphp

<header class="appbar">
  <div class="container container--wide appbar__inner">
    <button class="appbar__icon drawer-toggle" data-drawer-toggle aria-label="Buka menu"><svg data-icon="menu"></svg></button>
    <a class="logo" href="{{ route('home') }}">
      <x-logo />
    </a>

    <div class="appbar__right">
      <x-navbar-search placeholder="Cari soal, materi, peserta…" />
      <a class="btn btn--ghost btn--sm appbar-site-link" href="{{ route('home') }}" aria-label="Situs publik"><svg data-icon="external"></svg><span class="text-sm">Situs</span></a>
      <button class="appbar__icon theme-toggle" type="button" data-theme-toggle
              data-label-dark="Aktifkan mode gelap" data-label-light="Aktifkan mode terang"
              aria-pressed="false" aria-label="Aktifkan mode gelap">
        <svg class="theme-toggle__to-dark" data-icon="moon"></svg>
        <svg class="theme-toggle__to-light" data-icon="sun"></svg>
      </button>

      <div class="dropdown" data-dropdown>
        <button class="appbar__icon" data-dropdown-toggle aria-expanded="false" aria-label="Menu akun" style="box-shadow:none;border-color:transparent;background:transparent">
          <span class="avatar avatar--sm avatar--{{ $tone }}" style="box-shadow:2px 2px 0 var(--ink)">{{ $initials }}</span>
        </button>
        <div class="dropdown__menu">
          <div class="dropdown__head">
            <strong>{{ $name }}</strong>
            <span>{{ $email }}</span>
          </div>
          <a class="side__link" href="{{ route('profile.edit') }}"><svg data-icon="user"></svg>Profil Saya</a>
          <a class="side__link" href="{{ route('dashboard') }}"><svg data-icon="book"></svg>Mode Peserta</a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="side__link" type="submit" style="width:100%;text-align:left"><svg data-icon="logout"></svg>Keluar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>
