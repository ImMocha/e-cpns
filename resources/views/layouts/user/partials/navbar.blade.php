{{--
    Top bar area peserta.

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
    <a class="logo" href="{{ route('dashboard') }}">
      <x-logo sub="CPNS Online" />
    </a>

    <div class="appbar__search">
      <svg data-icon="search"></svg>
      <input class="input" type="search" placeholder="Cari materi, soal, tryout…" aria-label="Pencarian">
    </div>

    <div class="appbar__right">
      <a class="appbar__icon" href="{{ route('dashboard') }}" aria-label="Notifikasi"><svg data-icon="bell"></svg><span class="appbar__dot">3</span></a>

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
          <a class="side__link" href="{{ route('profile.edit') }}#pengaturan"><svg data-icon="settings"></svg>Pengaturan</a>
          {{-- Admin yang juga peserta perlu jalan pintas ke Admin Panel. --}}
          @if (auth()->user()?->isAdmin())
            <a class="side__link" href="{{ route('admin.dashboard') }}"><svg data-icon="shield"></svg>Admin Panel</a>
          @endif
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="side__link" type="submit" style="width:100%;text-align:left"><svg data-icon="logout"></svg>Keluar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>
