@extends('layouts.user.main')
@section('title', 'Latihan Soal — LONTAR')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Latihan</span></nav>
<x-exam-catalog :banks="$banks" :kind="$kind" :completed-count="$completedCount" />
@endsection
