@extends('layouts.user.main')

@section('title', 'Sertifikat — Ganesha Nusa CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Sertifikat</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Penghargaan</span>
        <h1 style="margin-top:.5rem">Sertifikat Digital</h1>
        <p>Sertifikat tersedia setelah tryout selesai dan semua bidang dinyatakan PASS. Buka sertifikat untuk mencetak atau menyimpannya sebagai PDF.</p>
      </div>
      <span class="badge badge--mint badge--flat"><svg data-icon="award"></svg> {{ $attempts->total() }} sertifikat tersedia</span>
    </header>

    <x-passing-grades style="margin-bottom:1.5rem" />

    <div class="alert alert--info" style="margin-bottom:1.5rem">
      <span class="alert__icon"><svg data-icon="info"></svg></span>
      <div>Nama pada sertifikat mengikuti profil kamu. <a href="{{ route('profile.edit') }}">Periksa nama lengkap</a> sebelum mencetak.</div>
    </div>

    <section class="grid-auto" aria-label="Daftar sertifikat">
      @forelse ($attempts as $attempt)
        <article class="card flex flex-col gap-4">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <span class="badge badge--mint badge--flat"><svg data-icon="award"></svg> Lulus Tryout</span>
            <span class="text-xs text-soft">{{ $attempt->certificateNumber() }}</span>
          </div>
          <div>
            <h2 class="card__title">{{ $attempt->title }}</h2>
            <p class="text-sm text-soft" style="margin-top:.5rem">Selesai pada {{ $attempt->submitted_at->locale('id')->translatedFormat('d F Y') }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            @foreach ($attempt->sectionGrades() as $grade)
              <span class="badge badge--mint badge--flat">{{ $grade['section'] }} {{ $grade['score'] }} · PASS</span>
            @endforeach
          </div>
          <div class="flex items-center justify-between gap-4 flex-wrap mt-auto">
            <span class="text-sm">Nilai <strong>{{ $attempt->score }} / {{ $attempt->max_score }}</strong></span>
            <a class="btn btn--primary btn--sm" href="{{ route('certificates.show', $attempt) }}"><svg data-icon="printer"></svg> Lihat &amp; Cetak</a>
          </div>
        </article>
      @empty
        <div class="empty">
          <span class="empty__icon"><svg data-icon="award"></svg></span>
          <h2 class="card__title">Belum ada sertifikat</h2>
          <p class="text-sm text-soft">Selesaikan tryout dan penuhi passing grade TWK, TIU, serta TKP. Ketiganya wajib PASS sebelum sertifikat tersedia.</p>
          <a class="btn btn--primary btn--sm" href="{{ route('tryouts.index') }}">Mulai Tryout<svg data-icon="arrow-right"></svg></a>
        </div>
      @endforelse
    </section>

    @if ($attempts->hasPages())
      <div style="margin-top:1.5rem"><x-pager :paginator="$attempts" /></div>
    @endif
@endsection
