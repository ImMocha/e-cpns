@extends('layouts.admin.main')

@section('title', 'Ubah ' . $user->name . ' — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('admin.users.index') }}">Peserta</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $user->name }}</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Pengelolaan Pengguna</span>
        <h1 style="margin-top:.5rem">Ubah Akun {{ $user->name }}</h1>
        <p>Perbarui data, hak akses, atau status akun {{ $user->email }}.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ route('admin.users.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    <div class="alert {{ $user->isActive() ? 'alert--success' : 'alert--warn' }}" style="margin-bottom:1.5rem">
      <span class="alert__icon"><svg data-icon="{{ $user->isActive() ? 'check-circle' : 'alert' }}"></svg></span>
      <div>
        <strong>{{ $user->isActive() ? 'Akun sedang aktif' : 'Akun sedang nonaktif' }}</strong>
        {{ $user->isActive()
            ? 'Peserta ini bisa masuk dan mengakses halaman belajar sampai statusnya diubah.'
            : 'Peserta tidak bisa masuk sampai admin mengaktifkan kembali akunnya.' }}
      </div>
    </div>

    @include('Dashboard.Admin.users._form', ['mode' => 'edit'])
@endsection

@section('modals')
    {{-- ============ ZONA BERBAHAYA ============ --}}
    <div class="card card--tight card--dashed" style="margin-top:1.5rem;border-color:#F5C6C7">
      <div class="card__title" style="font-size:var(--fs-base);color:#B3282C">Tindakan Permanen</div>
      <p class="text-sm text-soft" style="margin:.4rem 0 1rem">
        Menghapus akun akan menyembunyikannya dari daftar. Riwayat tryout dan transaksi peserta tetap tersimpan di sistem.
      </p>
      <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
            onsubmit="event.preventDefault(); L.confirmAction({ title: 'Hapus akun ini?', body: 'Akun {{ $user->name }} akan dihapus dari daftar peserta. Tindakan ini tidak bisa dibatalkan.', ok: 'Ya, hapus', tone: 'danger', onOk: function () { this.closest('form').submit(); } }.bind(this));">
        @csrf
        @method('DELETE')
        <button class="btn btn--ghost btn--sm" type="submit" style="color:#B3282C">
          <svg data-icon="trash"></svg> Hapus Akun
        </button>
      </form>
    </div>
@endsection
