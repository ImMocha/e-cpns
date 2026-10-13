@extends('layouts.admin.main')

@section('title', 'Tambah Pengguna — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('admin.users.index') }}">Peserta</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Tambah Pengguna</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Pengelolaan Pengguna</span>
        <h1 style="margin-top:.5rem">Buat Akun Baru</h1>
        <p>Akun dibuat langsung aktif supaya peserta bisa langsung masuk setelah password dibagikan.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ route('admin.users.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    @include('Dashboard.Admin.users._form', ['mode' => 'create'])
@endsection
