@extends('layouts.user.main')
@section('title', 'Tryout CAT — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Tryout CAT</span></nav>
<x-exam-catalog :banks="$banks" :kind="$kind" :completed-count="$completedCount" :token-count="$tokenCount" />
@endsection
