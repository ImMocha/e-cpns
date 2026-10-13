@extends('layouts.admin.main')

@section('title', 'Tambah Bidang — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('admin.sections.index') }}">Bidang</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Tambah Bidang</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Taksonomi Konten</span>
        <h1 style="margin-top:.5rem">Buat Bidang Baru</h1>
        <p>Bidang bukan daftar tertutup. TWK, TIU, dan TKP bisa ditambah, diubah, atau dihapus seperti bidang lain.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ route('admin.sections.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    @include('Dashboard.Admin.sections._form', ['mode' => 'create'])
@endsection