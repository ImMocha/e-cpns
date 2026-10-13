@extends('layouts.admin.main')

@section('title', 'Tambah Materi — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ $returnUrl }}">Materi</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Tambah Materi</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Konten</span>
        <h1 style="margin-top:.5rem">Buat Materi Baru</h1>
        <p>Materi bisa disimpan sebagai Draft dulu dan diterbitkan nanti saat berkasnya siap.</p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ $returnUrl }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    @include('Dashboard.Admin.materials._form', ['mode' => 'create'])
@endsection
