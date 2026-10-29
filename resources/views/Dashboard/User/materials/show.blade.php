@extends('layouts.user.main')

@section('title', $material->title . ' — Ganesha Nusa CPNS Online')

@section('content')
    @include('components.flash')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('materials.index') }}">Materi</a>
      @if ($material->section)
        <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
        <a href="{{ route('materials.index', ['section_id' => $material->section->id]) }}">{{ $material->section->name }}</a>
      @endif
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $material->topic?->name ?? $material->title }}</span>
    </nav>

    <div class="topic-study">
      @include('Dashboard.User.materials._sidebar')
      <div class="topic-study__content">
        <header class="page-head">
          <span class="eyebrow">{{ $material->section?->name ?? 'Materi' }}</span>
          @if ($material->topic)
            <p class="text-sm text-soft" style="margin-top:.5rem">{{ $material->topic->name }}</p>
          @endif
          <h1 style="margin-top:.5rem">{{ $material->title }}</h1>
          <div class="flex items-center gap-2 flex-wrap" style="margin-top:.85rem">
            @if ($material->page_count)
              <span class="badge badge--ink badge--flat">{{ number_format($material->page_count, 0, ',', '.') }} halaman</span>
            @endif
            @if ($material->humanDuration())
              <span class="badge badge--ink badge--flat"><svg data-icon="clock"></svg> {{ $material->humanDuration() }}</span>
            @endif
            @if ($material->published_at)
              <span class="badge badge--mint badge--flat"><svg data-icon="check"></svg> Terbit <x-local-time :value="$material->published_at" format="d M Y" /></span>
            @endif
          </div>
        </header>

        @if ($contentAccessible)
          @include('Dashboard.User.materials._reader')
        @else
          <section class="card topic-study__locked">
            <span class="empty__icon"><svg data-icon="lock"></svg></span>
            <h2>Materi Premium</h2>
            <p class="text-soft">Aktifkan paket belajar yang mencakup materi ini untuk mulai belajar.</p>
            <a class="btn btn--primary" href="{{ route('packages.index') }}">Lihat paket belajar <svg data-icon="arrow-right"></svg></a>
          </section>
        @endif

        <div class="btn-row" style="margin-top:1.5rem">
          <a class="btn btn--ghost" href="{{ route('materials.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar materi</a>
        </div>

      </div>
    </div>
@endsection
