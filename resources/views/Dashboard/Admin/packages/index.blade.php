@extends('layouts.admin.main')

@section('title', 'Kelola Paket Belajar — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Paket Belajar</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Bisnis</span>
        <h1 style="margin-top:.5rem">Kelola Paket Belajar</h1>
        <p>Tentukan harga, masa berlaku, dan konten yang tersedia pada setiap paket.</p>
      </div>
      <button class="btn btn--primary" type="button" data-modal-open="paketModal"><svg data-icon="plus"></svg> Buat Paket</button>
    </header>

    <div class="alert alert--warn" style="margin-bottom:1.5rem">
      <span class="alert__icon"><svg data-icon="alert"></svg></span>
      <div><strong>Harga, masa berlaku, jumlah tryout, dan sistem subscription masih TBD.</strong> Nilai di bawah hanya placeholder untuk demonstrasi tampilan.</div>
    </div>

    <section class="grid grid-3" style="--gap:1.5rem;align-items:start">

      <article class="card card--pop price">
        <div class="flex items-center justify-between">
          <div><span class="badge badge--flat">Basic</span><h2 class="card__title" style="margin-top:.6rem;font-size:var(--fs-lg)">Paket Basic</h2></div>
          <div style="text-align:right"><div class="text-xs text-soft fw-700">Terjual</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink)">2.104</div></div>
        </div>
        <div>
          <div class="price__amount"><small>Rp</small>49.000</div>
          <div class="price__period">Masa berlaku 30 hari</div>
        </div>
        <ul class="price__list">
          <li><span class="price__check"><svg data-icon="check"></svg></span> Materi PDF gratis dan ringkasan</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> 20 soal latihan per kategori</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> 2x Try Out CAT</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Statistik nilai dasar</li>
        </ul>
        <div class="price__foot">
          <div class="btn-row">
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="paketModal"><svg data-icon="pencil"></svg> Ubah</button>
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="isiModal"><svg data-icon="layers"></svg> Isi Paket</button>
          </div>
        </div>
      </article>

      <article class="card card--pop price" style="box-shadow:var(--sh-soft-pink)">
        <div class="flex items-center justify-between">
          <div><span class="badge badge--premium"><svg data-icon="crown"></svg> Terlaris</span><h2 class="card__title" style="margin-top:.6rem;font-size:var(--fs-lg)">Paket Premium</h2></div>
          <div style="text-align:right"><div class="text-xs text-soft fw-700">Terjual</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink)">1.842</div></div>
        </div>
        <div>
          <div class="price__amount"><small>Rp</small>99.000</div>
          <div class="price__period">Masa berlaku 90 hari</div>
        </div>
        <ul class="price__list">
          <li><span class="price__check"><svg data-icon="check"></svg></span> Semua materi PDF lengkap</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Seluruh video pembelajaran</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Bank soal tanpa batas</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> 12x Try Out CAT beserta pembahasan</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Leaderboard dan grafik lengkap</li>
        </ul>
        <div class="price__foot">
          <div class="btn-row">
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="paketModal"><svg data-icon="pencil"></svg> Ubah</button>
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="isiModal"><svg data-icon="layers"></svg> Isi Paket</button>
          </div>
        </div>
      </article>

      <article class="card card--pop price">
        <div class="flex items-center justify-between">
          <div><span class="badge badge--flat">Ultimate</span><h2 class="card__title" style="margin-top:.6rem;font-size:var(--fs-lg)">Paket Ultimate</h2></div>
          <div style="text-align:right"><div class="text-xs text-soft fw-700">Terjual</div><div class="fw-800" style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink)">386</div></div>
        </div>
        <div>
          <div class="price__amount"><small>Rp</small>249.000</div>
          <div class="price__period">Masa berlaku 12 bulan</div>
        </div>
        <ul class="price__list">
          <li><span class="price__check"><svg data-icon="check"></svg></span> Semua konten Premium</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Try Out CAT tanpa batas</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Sertifikat digital kelulusan</li>
          <li><span class="price__check"><svg data-icon="check"></svg></span> Review jawaban bersama admin</li>
        </ul>
        <div class="price__foot">
          <div class="btn-row">
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="paketModal"><svg data-icon="pencil"></svg> Ubah</button>
            <button class="btn btn--ghost btn--sm" type="button" data-modal-open="isiModal"><svg data-icon="layers"></svg> Isi Paket</button>
          </div>
        </div>
      </article>
    </section>

    <section class="card" style="margin-top:1.5rem">
      <div class="card__head">
        <div><div class="card__title">Penjualan per Paket</div><div class="card__sub">Transaksi berhasil dalam 6 bulan terakhir</div></div>
      </div>
      <div style="height:230px" data-chart='{"type":"bar","labels":["Mei","Jun","Jul","Agu","Sep","Okt"],"values":[412,538,601,648,702,761],"max":900,"height":230,"colors":["#0191ED","#0191ED","#4FB6F5","#4FB6F5","#0191ED","#2DD4A7"],"aria":"Grafik penjualan paket"}'></div>
    </section>
@endsection

@section('modals')
{{-- ============ MODAL PAKET ============ --}}
<div class="modal" id="paketModal" role="dialog" aria-modal="true" aria-labelledby="pakTitle" aria-hidden="true">
  <div class="modal__backdrop"></div>
  <div class="modal__panel">
    <button class="modal__close" type="button" data-modal-close aria-label="Tutup"><svg data-icon="close"></svg></button>
    <h2 class="modal__title" id="pakTitle">Buat Paket Belajar</h2>

    <form class="stack" style="--stack:1.15rem;margin-top:1.25rem" data-demo-submit="Paket disimpan.">
      <div class="field">
        <label class="label" for="namaPak">Nama Paket</label>
        <input class="input" type="text" id="namaPak" placeholder="Contoh: Paket Premium" required>
      </div>

      <div class="form-grid">
        <div class="field">
          <label class="label" for="hargaPak">Harga</label>
          <input class="input" type="number" id="hargaPak" placeholder="99000" min="0">
        </div>
        <div class="field">
          <label class="label" for="masaPak">Masa Berlaku (hari)</label>
          <input class="input" type="number" id="masaPak" placeholder="90" min="1">
        </div>
        <div class="field">
          <label class="label" for="limitTry">Jumlah Tryout</label>
          <input class="input" type="number" id="limitTry" placeholder="12" min="0">
          <span class="field__hint">Isi 0 untuk tanpa batas.</span>
        </div>
        <div class="field">
          <label class="label" for="limitMateri">Jumlah Materi Premium</label>
          <input class="input" type="number" id="limitMateri" placeholder="24" min="0">
        </div>
      </div>

      <div class="field">
        <label class="label" for="tipePak">Tipe Paket</label>
        <select class="select" id="tipePak">
          <option>Sekali bayar (masa berlaku)</option>
          <option>Langganan berkala</option>
          <option>Permanen</option>
        </select>
        <span class="field__hint">Sistem langganan masih <span class="badge badge--amber badge--flat">TBD</span></span>
      </div>

      <label class="switch"><input type="checkbox" checked><span>Tampilkan paket di halaman publik</span></label>

      <div class="modal__foot" style="margin-top:0">
        <button class="btn btn--ghost" type="button" data-modal-close>Batal</button>
        <button class="btn btn--primary" type="submit">Simpan Paket</button>
      </div>
    </form>
  </div>
</div>

{{-- ============ MODAL ISI PAKET ============ --}}
<div class="modal" id="isiModal" role="dialog" aria-modal="true" aria-labelledby="isiTitle" aria-hidden="true">
  <div class="modal__backdrop"></div>
  <div class="modal__panel">
    <button class="modal__close" type="button" data-modal-close aria-label="Tutup"><svg data-icon="close"></svg></button>
    <h2 class="modal__title" id="isiTitle">Konten dalam Paket</h2>
    <p class="text-sm text-soft" style="margin-top:.4rem">Pilih materi, video, dan tryout yang tersedia pada paket ini.</p>

    <div data-tabs>
      <div class="tabs" style="margin-top:1.25rem">
        <button class="tab is-active" data-tab="pkMateri" role="tab" aria-selected="true">Materi dan Video</button>
        <button class="tab" data-tab="pkTryout" role="tab" aria-selected="false">Tryout</button>
        <button class="tab" data-tab="pkSisanya" role="tab" aria-selected="false">Lainnya</button>
      </div>

      <div class="tab-panel is-active" id="pkMateri" data-tab-panel>
        <div class="stack" style="--stack:.55rem">
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Rangkuman Pengetahuan Umum Indonesia</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Modul Matematika Dasar untuk ASN</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Teknik Membaca Tabel dan Grafik</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Penalaran Silogisme: 12 Pola</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox"><span>Logika Silogisme Bagian 2</span></label>
        </div>
      </div>
      <div class="tab-panel" id="pkTryout" data-tab-panel>
        <div class="stack" style="--stack:.55rem">
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Tryout SKD Penuh</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Tryout TWK Mingguan</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox" checked><span>Tryout TKP Kontekstual</span></label>
          <label class="check card card--tight card--dashed"><input type="checkbox"><span>Tryout TIU Intensif</span></label>
        </div>
      </div>
      <div class="tab-panel" id="pkSisanya" data-tab-panel>
        <div class="stack" style="--stack:1rem">
          <label class="switch"><input type="checkbox" checked><span>Akses leaderboard</span></label>
          <label class="switch"><input type="checkbox" checked><span>Akses grafik perkembangan nilai</span></label>
          <label class="switch"><input type="checkbox"><span>Terbitkan sertifikat digital</span></label>
          <label class="switch"><input type="checkbox"><span>Bonus review jawaban bersama admin</span></label>
        </div>
      </div>
    </div>

    <div class="modal__foot">
      <button class="btn btn--ghost" type="button" data-modal-close>Batal</button>
      <button class="btn btn--primary" type="button" onclick="L.toast('Isi paket disimpan.','success')">Simpan Isi Paket</button>
    </div>
  </div>
</div>
@endsection