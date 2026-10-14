@extends('layouts.admin.main')

@section('title', 'Ubah ' . $material->title . ' — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ $returnUrl }}">Materi</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $material->title }}</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Konten</span>
        <h1 style="margin-top:.5rem">Ubah Materi {{ $material->title }}</h1>
        <p>
          {{ $material->type->label() }}
          @if ($material->section)
            &middot; bidang <span class="text-mono">{{ $material->section->code }}</span>
          @endif
          &middot; dibuat {{ $material->created_at->format('d M Y') }}
        </p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ $returnUrl }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    @php($missingSource = ! $material->hasContentSource())

    <div class="alert {{ $missingSource ? 'alert--warn' : 'alert--success' }}" style="margin-bottom:1.5rem">
      <span class="alert__icon"><svg data-icon="{{ $material->isPublished() ? 'check-circle' : 'alert' }}"></svg></span>
      <div>
        <strong>{{ $material->isPublished() ? 'Materi sudah terbit' : 'Materi berstatus Draft' }}</strong>
        @if ($missingSource)
          Belum ada sumber, jadi belum bisa diterbitkan. Unggah PDF atau isi URL video dulu.
        @elseif ($material->isPublished())
          Terbit {{ $material->published_at?->format('d M Y') }} &mdash; @if ($material->section?->is_active && $material->topic?->is_active) peserta dapat membukanya sesuai hak akses. @else bidang atau topiknya nonaktif, sehingga materi belum tampil untuk peserta. @endif
        @else
          Belum tampil untuk peserta. Beri status Terbit setelah sumbernya siap.
        @endif
      </div>
    </div>

    @include('Dashboard.Admin.materials._form', ['mode' => 'edit'])

    <div class="card card--tight card--dashed" style="margin-top:1.5rem;border-color:#F5C6C7">
      <div class="card__title" style="font-size:var(--fs-base);color:#B3282C">Arsip Materi</div>
      <p class="text-sm text-soft" style="margin:.4rem 0 1rem">
        Menghapus materi berarti materi ini hilang dari daftar peserta. Berkas PDF
        <strong>tidak</strong> ikut dihapus, jadi materinya masih bisa dipulihkan tanpa mengunggah ulang.
      </p>
      <form method="POST" action="{{ route('admin.materials.destroy', $material) }}"
            data-content-confirm data-confirm-title="Arsipkan materi?" data-confirm-body="Materi &quot;{{ $material->title }}&quot; akan disembunyikan. Berkas PDF tetap disimpan." data-confirm-ok="Ya, arsipkan">
        @csrf
        @method('DELETE')
        <input type="hidden" name="workspace" value="1">
        <button class="btn btn--ghost btn--sm" type="submit" style="color:#B3282C">
          <svg data-icon="trash"></svg> Arsipkan Materi
        </button>
      </form>
    </div>
@endsection
