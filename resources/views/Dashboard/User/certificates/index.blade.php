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
        <p>Sertifikat diterbitkan otomatis setelah kamu memenuhi syarat kelulusan program.</p>
      </div>
      <div class="btn-row no-print">
        <button class="btn btn--ghost" type="button" onclick="L.toast('Sertifikat dibagikan lewat tautan publik.','success')"><svg data-icon="share"></svg> Bagikan</button>
        <button class="btn btn--ghost" type="button" data-print><svg data-icon="printer"></svg> Cetak</button>
        <button class="btn btn--primary" type="button" onclick="L.toast('Sertifikat diunduh dalam format PDF.','success')"><svg data-icon="download"></svg> Unduh PDF</button>
      </div>
    </header>

    {{-- ============ STATUS KELULUSAN ============ --}}
    <section class="card card--pop card--amber" style="margin-bottom:1.75rem">
      <div class="panes panes--lead" style="--gap:1.5rem;">
        <div class="ring" data-pct="78" style="--ring-color:var(--pop-amber)">
          <div class="ring__label">
            <div class="ring__value">78%</div>
            <div class="ring__unit">Kelayakan</div>
          </div>
        </div>
        <div>
          <span class="badge badge--amber badge--flat"><svg data-icon="clock"></svg> Belum terbit</span>
          <h2 class="card__title" style="font-size:var(--fs-lg);margin-top:.6rem">Sertifikat belum terbit</h2>
          <p class="text-sm text-soft" style="margin-top:.4rem;max-width:60ch">
            Kamu sudah menyelesaikan 78 persen dari syarat program. Selesaikan 2 materi lagi dan 1 tryout dengan nilai di atas 400 untuk menerbitkan sertifikat.
          </p>

          <div class="grid" style="--gap:.6rem;margin-top:1.25rem">
            <div class="flex items-center gap-2">
              <span class="price__check" style="background:var(--success)"><svg data-icon="check"></svg></span>
              <span class="text-sm">10 dari 10 materi PDF selesai</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="price__check" style="background:var(--success)"><svg data-icon="check"></svg></span>
              <span class="text-sm">9 dari 18 video ditonton</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="price__check" style="background:var(--warning)"><svg data-icon="close"></svg></span>
              <span class="text-sm">2 materi video belum selesai</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="price__check" style="background:var(--success)"><svg data-icon="check"></svg></span>
              <span class="text-sm">Tryout dengan nilai di atas 400: 2 dari 3</span>
            </div>
          </div>
        </div>
      </div>
      <div class="alert alert--warn no-print" style="margin-top:1.5rem">
        <span class="alert__icon"><svg data-icon="alert"></svg></span>
        <div>Trigger penerbitan sertifikat (misalnya menyelesaikan paket, program, atau mencapai nilai tertentu) masih <strong>TBD</strong> dan perlu keputusan client.</div>
      </div>
    </section>

    {{-- ============ PRATJAUANG SERTIFIKAT ============ --}}
    <section>
      <div class="section-head">
        <span class="eyebrow">Pratinjau</span>
        <h2 style="margin-top:.75rem">Tampilan Sertifikat</h2>
        <p>Contoh tampilan sertifikat yang akan terbit. Nama dan data sudah disesuaikan dengan profil kamu.</p>
      </div>

      <div class="cert">
        <div class="cert__frame" aria-hidden="true"></div>

        <div class="flex items-center justify-between" style="margin-bottom:1.5rem">
          <div class="logo">
            <span class="logo__mark"><svg data-icon="book"></svg></span>
            <span style="text-align:left">Ganesha Nusa<span class="logo__sub" style="text-align:left">Lembaga Belajar CPNS</span></span>
          </div>
          <span class="badge badge--flat">Nomor: CERT-2026-000184</span>
        </div>

        <div class="cert__seal"><svg data-icon="award"></svg></div>

        <p class="text-xs fw-700" style="letter-spacing:.2em;text-transform:uppercase;color:var(--text-soft)">Sertifikat Penyelesaian</p>
        <p class="cert__name">Rani Kusuma</p>
        <p style="max-width:56ch;margin-inline:auto;color:var(--text-soft)">
          Telah menyelesaikan program <strong style="color:var(--ink)">Persiapan Seleksi CPNS 2026</strong>
          yang terdiri dari materi pembelajaran, latihan soal, dan simulasi Try Out CAT.
        </p>

        <div class="grid grid-3" style="--gap:1rem;max-width:640px;margin:2rem auto 0">
          <div class="card card--tight card--dashed" style="box-shadow:none">
            <div class="text-xs text-soft fw-700">Nilai Tryout Terakhir</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--ink)">452</div>
          </div>
          <div class="card card--tight card--dashed" style="box-shadow:none">
            <div class="text-xs text-soft fw-700">Tryout Dikerjakan</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--ink)">4</div>
          </div>
          <div class="card card--tight card--dashed" style="box-shadow:none">
            <div class="text-xs text-soft fw-700">Peringkat Leaderboard</div>
            <div class="fw-800" style="font-family:var(--font-display);font-size:1.5rem;color:var(--ink)">18</div>
          </div>
        </div>

        <div class="cert__meta">
          <div><dt>Tanggal Terbit</dt><dd>28 November 2026</dd></div>
          <div><dt>Masa Berlaku</dt><dd>Sampai 28 November 2027</dd></div>
          <div><dt>ID Verifikasi</dt><dd>LNT-8F2A-91C4</dd></div>
        </div>

        <div class="flex items-center justify-between" style="margin-top:2rem;padding-top:1.5rem;border-top:2px solid var(--border);text-align:left;flex-wrap:wrap;gap:1rem">
          <div>
            <div class="text-xs text-soft fw-700">Mengetahui</div>
            <div style="font-family:var(--cursive,'Segoe Script','Brush Script MT',cursive);font-size:1.4rem;color:var(--ink);margin-top:.25rem">Bagas Pratama</div>
            <div style="width:170px;height:2px;background:var(--border-strong);margin-top:.25rem"></div>
            <div class="text-xs text-soft">Kepala Ganesha Nusa</div>
          </div>
          <div class="text-center">
            <div class="stat__icon ico--primary" style="margin-inline:auto;width:56px;height:56px;border-radius:50%"><svg data-icon="shield"></svg></div>
            <div class="text-xs text-soft" style="margin-top:.4rem">Verifikasi online</div>
          </div>
        </div>
      </div>

      <p class="text-center text-xs text-soft" style="margin-top:1.5rem">
        Pada prototipe, sertifikat langsung tercetak di halaman. Pada implementasi final, sertifikat digenerate sebagai PDF dengan kode verifikasi.
      </p>
    </section>

    {{-- ============ SERTIFIKAT LAMA ============ --}}
    <section class="section" style="padding-bottom:0">
      <div class="section-head">
        <h2>Sertifikat sebelumnya</h2>
      </div>
      <div class="empty">
        <span class="empty__icon"><svg data-icon="award"></svg></span>
        <strong style="color:var(--ink);font-family:var(--font-display)">Belum ada sertifikat terbit</strong>
        <p class="text-sm text-soft">Sertifikat yang sudah terbit akan muncul di sini beserta tombol unduh.</p>
        <a class="btn btn--ghost btn--sm" href="{{ route('materials.index') }}">Lanjut belajar<svg data-icon="arrow-right"></svg></a>
      </div>
    </section>
@endsection