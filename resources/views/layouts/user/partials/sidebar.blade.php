{{--
    Sidebar area peserta.

    Menu ditulis langsung (bukan dari config / array) supaya mudah dibaca dan
    diubah. Status aktif dideteksi dari route lewat `request()->routeIs()`, jadi
    tidak perlu variabel $active yang harus dicocokkan manual di controller.

    Pola `tryouts.*` dipakai (bukan `tryouts.index`) supaya halaman
    mengerjakan soal dan hasil tetap menyorot induk menunya.
--}}
<div class="side__backdrop"></div>
<aside class="side" aria-label="Menu utama">
  <div class="flex items-center justify-between" style="margin-bottom:1.25rem">
    <span class="badge badge--ink badge--flat">{{ auth()->user()?->isAdmin() ? 'Admin · Peserta' : 'Peserta' }}</span>
    <button class="drawer-close appbar__icon" data-drawer-close aria-label="Tutup menu" style="width:38px;height:38px"><svg data-icon="close"></svg></button>
  </div>

  {{-- ===================== BERANDA ===================== --}}
  <div class="side__group">
    <div class="side__title">Beranda</div>
    <a class="side__link{{ request()->routeIs('dashboard') ? ' is-active' : '' }}"
       href="{{ route('dashboard') }}"
       @if (request()->routeIs('dashboard')) aria-current="page" @endif>
      <svg data-icon="dashboard"></svg><span>Dashboard</span>
    </a>
  </div>

  {{-- ===================== BELAJAR ===================== --}}
  <div class="side__group">
    <div class="side__title">Belajar</div>
    <a class="side__link{{ request()->routeIs('materials.*') ? ' is-active' : '' }}"
       href="{{ route('materials.index') }}"
       @if (request()->routeIs('materials.*')) aria-current="page" @endif>
      <svg data-icon="book"></svg><span>Materi</span>
    </a>
    <a class="side__link{{ request()->routeIs('quizzes.*', 'practices.*') ? ' is-active' : '' }}"
       href="{{ route('quizzes.index') }}"
       @if (request()->routeIs('quizzes.*', 'practices.*')) aria-current="page" @endif>
      <svg data-icon="pen-line"></svg><span>Latihan Soal</span>
    </a>
    <a class="side__link{{ request()->routeIs('articles.*') ? ' is-active' : '' }}"
       href="{{ route('articles.index') }}"
       @if (request()->routeIs('articles.*')) aria-current="page" @endif>
      <svg data-icon="quote"></svg><span>Artikel</span>
    </a>
  </div>

  {{-- ===================== SIMULASI CAT ===================== --}}
  <div class="side__group">
    <div class="side__title">Simulasi CAT</div>
    <a class="side__link{{ request()->routeIs('tryouts.*') ? ' is-active' : '' }}"
       href="{{ route('tryouts.index') }}"
       @if (request()->routeIs('tryouts.*')) aria-current="page" @endif>
      <svg data-icon="timer"></svg><span>Tryout CAT</span>
      <span class="side__pill">Baru</span>
    </a>
    <a class="side__link{{ request()->routeIs('history.*') ? ' is-active' : '' }}"
       href="{{ route('history.index') }}"
       @if (request()->routeIs('history.*')) aria-current="page" @endif>
      <svg data-icon="history"></svg><span>Riwayat</span>
    </a>
    <a class="side__link{{ request()->routeIs('statistics.*') ? ' is-active' : '' }}"
       href="{{ route('statistics.index') }}"
       @if (request()->routeIs('statistics.*')) aria-current="page" @endif>
      <svg data-icon="chart"></svg><span>Statistik</span>
    </a>
    <a class="side__link{{ request()->routeIs('leaderboard.*') ? ' is-active' : '' }}"
       href="{{ route('leaderboard.index') }}"
       @if (request()->routeIs('leaderboard.*')) aria-current="page" @endif>
      <svg data-icon="trophy"></svg><span>Leaderboard</span>
    </a>
  </div>

  {{-- ===================== PREMIUM ===================== --}}
  <div class="side__group">
    <div class="side__title">Premium</div>
    <a class="side__link{{ request()->routeIs('packages.*') ? ' is-active' : '' }}"
       href="{{ route('packages.index') }}"
       @if (request()->routeIs('packages.*')) aria-current="page" @endif>
      <svg data-icon="package"></svg><span>Paket Belajar</span>
    </a>
    <a class="side__link{{ request()->routeIs('transactions.*') ? ' is-active' : '' }}"
       href="{{ route('transactions.index') }}"
       @if (request()->routeIs('transactions.*')) aria-current="page" @endif>
      <svg data-icon="credit-card"></svg><span>Transaksi Saya</span>
    </a>
    <a class="side__link{{ request()->routeIs('certificates.*') ? ' is-active' : '' }}"
       href="{{ route('certificates.index') }}"
       @if (request()->routeIs('certificates.*')) aria-current="page" @endif>
      <svg data-icon="award"></svg><span>Sertifikat</span>
    </a>
  </div>

  {{-- ===================== AKUN ===================== --}}
  <div class="side__group">
    <div class="side__title">Akun</div>
    <a class="side__link{{ request()->routeIs('profile.*') ? ' is-active' : '' }}"
       href="{{ route('profile.edit') }}"
       @if (request()->routeIs('profile.*')) aria-current="page" @endif>
      <svg data-icon="user"></svg><span>Profil Saya</span>
    </a>
  </div>

  {{-- Admin yang juga peserta butuh jalan pintas ke Admin Panel. --}}
  @if (auth()->user()?->isAdmin())
    <div class="side__group">
      <div class="side__title">Admin</div>
      <a class="side__link{{ request()->routeIs('admin.*') ? ' is-active' : '' }}"
         href="{{ route('admin.dashboard') }}"
         @if (request()->routeIs('admin.*')) aria-current="page" @endif>
        <svg data-icon="shield"></svg><span>Admin Panel</span>
      </a>
    </div>
  @endif

</aside>
