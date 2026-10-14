@extends('layouts.admin.main')

@section('title', 'Ubah ' . $section->name . ' — Ganesha Nusa')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('admin.sections.index') }}">Bidang</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">{{ $section->name }}</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Taksonomi Konten</span>
        <h1 style="margin-top:.5rem">Ubah Bidang {{ $section->name }}</h1>
        <p>
          Kode <span class="text-mono">{{ $section->code }}</span>
          &middot; urut tampil <span class="text-mono">{{ $section->position }}</span>
          &middot; dibuat {{ $section->created_at->format('d M Y') }}
        </p>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ route('admin.sections.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke daftar</a>
      </div>
    </header>

    <div class="alert {{ $section->is_active ? 'alert--success' : 'alert--warn' }}" style="margin-bottom:1.5rem">
      <span class="alert__icon"><svg data-icon="{{ $section->is_active ? 'check-circle' : 'alert' }}"></svg></span>
      <div>
        <strong>{{ $section->is_active ? 'Bidang aktif' : 'Bidang nonaktif' }}</strong>
        @if ($section->is_active)
          Bidang ini muncul di pilihan materi, soal, dan artikel untuk peserta.
        @else
          Bidang ini disembunyikan dari pilihan peserta. Isi yang sudah ada tidak ikut terhapus.
        @endif
      </div>
    </div>

    @include('Dashboard.Admin.sections._form', ['mode' => 'edit'])
@endsection

@section('modals')
    <div class="card card--tight card--dashed" style="margin-top:1.5rem;border-color:#F5C6C7">
      <div class="card__title" style="font-size:var(--fs-base);color:#B3282C">Tindakan Permanen</div>
      <p class="text-sm text-soft" style="margin:.4rem 0 1rem">
        Menghapus bidang berarti materi dan soal di dalamnya tidak punya kelompok lagi. Kalau hanya ingin
        menyembunyikannya dari peserta, nonaktifkan saja.
      </p>
      <form method="POST" action="{{ route('admin.sections.destroy', $section) }}"
            onsubmit="event.preventDefault(); L.confirmAction({ title: 'Hapus bidang ini?', body: 'Bidang '{{ $section->name }}' akan dihapus. Tindakan ini tidak bisa dibatalkan.', ok: 'Ya, hapus', tone: 'danger', onOk: function () { this.closest('form').submit(); } }.bind(this));">
        @csrf
        @method('DELETE')
        <button class="btn btn--ghost btn--sm" type="submit" style="color:#B3282C">
          <svg data-icon="trash"></svg> Hapus Bidang
        </button>
      </form>
    </div>
@endsection