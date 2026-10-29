@extends('layouts.user.main')
@section('title', \App\Enums\ExamKind::from($kind)->label().' — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">{{ \App\Enums\ExamKind::from($kind)->label() }}</span></nav>
<x-exam-catalog :banks="$banks" :kind="$kind" :completed-count="$completedCount" :learning-locked="$learningLocked" />
@endsection
