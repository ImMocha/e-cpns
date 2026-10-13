{{--
    Sidebar Admin Panel.

    Menu ditulis langsung (bukan dari config / array) supaya mudah dibaca dan
    diubah. Status aktif dideteksi dari route lewat `request()->routeIs()`, jadi
    tidak perlu variabel $active yang harus dicocokkan manual di controller.

    Pola `admin.users.*` dipakai (bukan `admin.users.index`) supaya halaman
    edit/create tetap menyorot induk menunya.
--}}
<div class="side__backdrop"></div>
<aside class="side" aria-label="Menu utama">
  <div class="flex items-center justify-between" style="margin-bottom:1.25rem">
    <span class="badge badge--ink badge--flat">Administrator</span>
    <button class="drawer-close appbar__icon" data-drawer-close aria-label="Tutup menu" style="width:38px;height:38px"><svg data-icon="close"></svg></button>
  </div>

  {{-- ===================== RINGKASAN ===================== --}}
  <div class="side__group">
    <div class="side__title">Ringkasan</div>
    <a class="side__link{{ request()->routeIs('admin.dashboard') ? ' is-active' : '' }}"
       href="{{ route('admin.dashboard') }}"
       @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
      <svg data-icon="dashboard"></svg><span>Admin Dashboard</span>
    </a>
  </div>

  {{-- ===================== KONTEN ===================== --}}
  <div class="side__group">
    <div class="side__title">Konten</div>
    <a class="side__link{{ request()->routeIs('admin.materials.*', 'admin.topics.*') ? ' is-active' : '' }}"
       href="{{ route('admin.materials.index') }}"
       @if (request()->routeIs('admin.materials.*', 'admin.topics.*')) aria-current="page" @endif>
      <svg data-icon="folder"></svg><span>Materi &amp; Topik</span>
    </a>
    <a class="side__link{{ request()->routeIs('admin.sections.*') ? ' is-active' : '' }}"
       href="{{ route('admin.sections.index') }}"
       @if (request()->routeIs('admin.sections.*')) aria-current="page" @endif>
      <svg data-icon="clipboard"></svg><span>Bidang</span>
    </a>
    <a class="side__link{{ request()->routeIs('admin.questions.*') ? ' is-active' : '' }}"
       href="{{ route('admin.questions.index') }}"
       @if (request()->routeIs('admin.questions.*')) aria-current="page" @endif>
      <svg data-icon="layers"></svg><span>Soal &amp; Pembahasan</span>
    </a>
    <a class="side__link{{ request()->routeIs('admin.exams.*') ? ' is-active' : '' }}" href="{{ route('admin.exams.index') }}" @if (request()->routeIs('admin.exams.*')) aria-current="page" @endif><svg data-icon="timer"></svg><span>Quiz &amp; Tryout</span></a>
    <a class="side__link{{ request()->routeIs('admin.articles.*') ? ' is-active' : '' }}"
       href="{{ route('admin.articles.index') }}"
       @if (request()->routeIs('admin.articles.*')) aria-current="page" @endif>
      <svg data-icon="quote"></svg><span>Artikel</span>
    </a>
  </div>

  {{-- ===================== PENGGUNA ===================== --}}
  <div class="side__group">
    <div class="side__title">Pengguna</div>
    <a class="side__link{{ request()->routeIs('admin.users.*') ? ' is-active' : '' }}"
       href="{{ route('admin.users.index') }}"
       @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>
      <svg data-icon="users"></svg><span>Peserta</span>
    </a>
  </div>

  {{-- ===================== BISNIS ===================== --}}
  <div class="side__group">
    <div class="side__title">Bisnis</div>
    <a class="side__link{{ request()->routeIs('admin.packages.*') ? ' is-active' : '' }}"
       href="{{ route('admin.packages.index') }}"
       @if (request()->routeIs('admin.packages.*')) aria-current="page" @endif>
      <svg data-icon="package"></svg><span>Paket Belajar</span>
    </a>
    <a class="side__link{{ request()->routeIs('admin.transactions.*') ? ' is-active' : '' }}"
       href="{{ route('admin.transactions.index') }}"
       @if (request()->routeIs('admin.transactions.*')) aria-current="page" @endif>
      <svg data-icon="credit-card"></svg><span>Transaksi</span>
    </a>
    <a class="side__link{{ request()->routeIs('admin.vouchers.*') ? ' is-active' : '' }}"
       href="{{ route('admin.vouchers.index') }}"
       @if (request()->routeIs('admin.vouchers.*')) aria-current="page" @endif>
      <svg data-icon="tag"></svg><span>Voucher</span>
    </a>
  </div>

  {{-- ===================== KELUAR DARI ADMIN PANEL ===================== --}}
  <div class="side__group">
    <div class="side__title">Lainnya</div>
    <a class="side__link" href="{{ route('dashboard') }}">
      <svg data-icon="book"></svg><span>Mode Peserta</span>
    </a>
  </div>

  <div class="side__group">
    <div class="card card--dashed" style="padding:1rem">
      <div class="flex items-center gap-2" style="margin-bottom:.4rem">
        <span class="tile__glyph" style="width:34px;height:34px;border-radius:10px"><svg data-icon="megaphone" width="17" height="17"></svg></span>
        <strong style="font-family:var(--font-display);font-size:var(--fs-sm);color:var(--ink)">Butuh bantuan?</strong>
      </div>
      <p class="text-xs text-soft">Tim kami siap bantu soal materi atau paket.</p>
    </div>
  </div>
</aside>
