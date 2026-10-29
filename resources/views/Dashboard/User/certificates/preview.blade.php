@extends('layouts.user.main')
@section('title', 'Rincian Sertifikat — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('certificates.index') }}">Sertifikat</a><span class="crumbs__sep">›</span><span class="crumbs__current">Rincian sertifikat</span></nav>
<header class="page-head"><span class="eyebrow">Sertifikat Digital</span><h1 style="margin-top:.5rem">{{ $attempt->title }}</h1><p>Tryout selesai pada <x-local-time :value="$attempt->submitted_at" format="d F Y" />.</p></header>
<x-feature-lock title="Sertifikat terkunci" description="Sertifikat untuk tryout ini tersedia setelah kamu mengaktifkan paket Premium. Isi sertifikat serta fitur cetak dan simpan PDF akan terbuka selama akses Premium aktif." />
<div class="btn-row" style="margin-top:1.5rem"><a class="btn btn--ghost" href="{{ route('certificates.index') }}">Kembali ke daftar sertifikat</a></div>
@endsection
