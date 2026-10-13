@extends('layouts.admin.main')

@section('title', 'Kelola Voucher — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Voucher</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Bisnis</span>
        <h1 style="margin-top:.5rem">Voucher &amp; Diskon</h1>
        <p>Buat kode voucher, atur nilai diskon, dan pantau pemakaiannya.</p>
      </div>
      <button class="btn btn--primary" type="button" data-modal-open="voucherModal"><svg data-icon="plus"></svg> Buat Voucher</button>
    </header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat">
        <span class="stat__icon ico--amber"><svg data-icon="tag"></svg></span>
        <div><div class="stat__label">Voucher Aktif</div><div class="stat__value" data-count-to="6">0</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon"><svg data-icon="percent"></svg></span>
        <div><div class="stat__label">Total Dipakai</div><div class="stat__value" data-count-to="1840">0</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--mint"><svg data-icon="credit-card"></svg></span>
        <div><div class="stat__label">Total Diskon Diberikan</div><div class="stat__value" data-count-to="24850000" data-count-prefix="Rp">0</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--pink"><svg data-icon="alert"></svg></span>
        <div><div class="stat__label">Kedaluwarsa</div><div class="stat__value" data-count-to="2">0</div><div class="stat__delta text-soft">Bulan ini</div></div>
      </article>
    </section>

    <div class="flex items-center justify-between flex-wrap" style="gap:1rem;margin-bottom:1.25rem">
      <div class="pills">
        <button class="pill is-active" type="button">Semua</button>
        <button class="pill" type="button">Aktif</button>
        <button class="pill" type="button">Nonaktif</button>
        <button class="pill" type="button">Kedaluwarsa</button>
      </div>
      <div class="input-group" style="min-width:230px">
        <span class="input-group__icon"><svg data-icon="search"></svg></span>
        <input class="input" type="search" placeholder="Cari kode voucher..." aria-label="Cari voucher" data-table-filter="tabelVoucher">
      </div>
    </div>

    <section class="card card--pad-0">
      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table" id="tabelVoucher">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Tipe Diskon</th>
              <th class="num">Nilai</th>
              <th>Masa Berlaku</th>
              <th>Kuota</th>
              <th class="is-sortable num" aria-sort="descending">Dipakai<span class="sort-ind">↕</span></th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="badge badge--ink">GAIN20</span></td>
              <td>Nominal</td>
              <td class="num">Rp20.000</td>
              <td>1 Sep - 31 Okt 2026</td>
              <td>500</td>
              <td class="num" data-sort="312">312</td>
              <td><span class="badge badge--mint badge--flat">Aktif</span></td>
              <td>
                <div class="flex" style="--gap:.35rem">
                  <button class="btn btn--ghost btn--square btn--sm" type="button" data-modal-open="voucherModal" aria-label="Ubah"><svg data-icon="pencil"></svg></button>
                  <button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Voucher dinonaktifkan.','warn')" aria-label="Nonaktifkan"><svg data-icon="x-circle"></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td><span class="badge badge--ink">CERDAS15</span></td>
              <td>Nominal</td>
              <td class="num">Rp15.000</td>
              <td>15 Sep - 15 Nov 2026</td>
              <td>300</td>
              <td class="num" data-sort="188">188</td>
              <td><span class="badge badge--mint badge--flat">Aktif</span></td>
              <td>
                <div class="flex" style="--gap:.35rem">
                  <button class="btn btn--ghost btn--square btn--sm" type="button" data-modal-open="voucherModal" aria-label="Ubah"><svg data-icon="pencil"></svg></button>
                  <button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Voucher dinonaktifkan.','warn')" aria-label="Nonaktifkan"><svg data-icon="x-circle"></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td><span class="badge badge--ink">NAVIGASI10</span></td>
              <td>Persentase</td>
              <td class="num">10%</td>
              <td>1 Sep - 30 Sep 2026</td>
              <td>200</td>
              <td class="num" data-sort="200">200</td>
              <td><span class="badge badge--flat">Kedaluwarsa</span></td>
              <td>
                <div class="flex" style="--gap:.35rem">
                  <button class="btn btn--ghost btn--square btn--sm" type="button" data-modal-open="voucherModal" aria-label="Ubah"><svg data-icon="pencil"></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td><span class="badge badge--ink">EARLYBIRD25</span></td>
              <td>Persentase</td>
              <td class="num">25%</td>
              <td>1 Sep - 31 Des 2026</td>
              <td>150</td>
              <td class="num" data-sort="96">96</td>
              <td><span class="badge badge--mint badge--flat">Aktif</span></td>
              <td>
                <div class="flex" style="--gap:.35rem">
                  <button class="btn btn--ghost btn--square btn--sm" type="button" data-modal-open="voucherModal" aria-label="Ubah"><svg data-icon="pencil"></svg></button>
                  <button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Voucher dinonaktifkan.','warn')" aria-label="Nonaktifkan"><svg data-icon="x-circle"></svg></button>
                </div>
              </td>
            </tr>
            <tr>
              <td><span class="badge badge--ink">HEMAT30K</span></td>
              <td>Nominal</td>
              <td class="num">Rp30.000</td>
              <td>Tidak dibatasi</td>
              <td>50</td>
              <td class="num" data-sort="50">50</td>
              <td><span class="badge badge--amber badge--flat">Kuota habis</span></td>
              <td>
                <div class="flex" style="--gap:.35rem">
                  <button class="btn btn--ghost btn--square btn--sm" type="button" data-modal-open="voucherModal" aria-label="Ubah"><svg data-icon="pencil"></svg></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="empty" data-table-empty hidden style="margin:1.5rem">
        <span class="empty__icon"><svg data-icon="search"></svg></span>
        <strong style="color:var(--ink);font-family:var(--font-display)">Voucher tidak ditemukan</strong>
      </div>

      <div style="padding:1.25rem 1.5rem;border-top:2px solid var(--border)">
        <div class="alert alert--warn">
          <span class="alert__icon"><svg data-icon="alert"></svg></span>
          <div>Detail seperti tanggal mulai, tanggal berakhir, minimum transaksi, dan kuota voucher <strong>TBD</strong>. Invoice hanya menyebut kode, tipe, nilai, dan status.</div>
        </div>
      </div>
    </section>
@endsection

@section('modals')
{{-- ============ MODAL VOUCHER ============ --}}
<div class="modal" id="voucherModal" role="dialog" aria-modal="true" aria-labelledby="vouTitle" aria-hidden="true">
  <div class="modal__backdrop"></div>
  <div class="modal__panel">
    <button class="modal__close" type="button" data-modal-close aria-label="Tutup"><svg data-icon="close"></svg></button>
    <h2 class="modal__title" id="vouTitle">Buat Voucher</h2>

    <form class="stack" style="--stack:1.15rem;margin-top:1.25rem" data-demo-submit="Voucher disimpan.">
      <div class="field">
        <label class="label" for="kode">Kode Voucher</label>
        <input class="input" type="text" id="kode" placeholder="Contoh: GAIN20" required style="text-transform:uppercase">
        <span class="field__hint">Gunakan huruf kapital dan angka tanpa spasi.</span>
      </div>

      <div class="form-grid">
        <div class="field">
          <label class="label" for="tipe">Tipe Diskon</label>
          <select class="select" id="tipe">
            <option selected>Nominal</option>
            <option>Persentase</option>
          </select>
        </div>
        <div class="field">
          <label class="label" for="nilai">Nilai Diskon</label>
          <input class="input" type="number" id="nilai" placeholder="20000" min="0">
        </div>
        <div class="field">
          <label class="label" for="mulai">Tanggal Mulai</label>
          <input class="input" type="date" id="mulai" value="2026-10-03">
        </div>
        <div class="field">
          <label class="label" for="akhir">Tanggal Berakhir</label>
          <input class="input" type="date" id="akhir" value="2026-12-31">
        </div>
        <div class="field">
          <label class="label" for="kuota">Kuota</label>
          <input class="input" type="number" id="kuota" placeholder="500" min="1">
        </div>
        <div class="field">
          <label class="label" for="minTrans">Minimum Transaksi</label>
          <input class="input" type="number" id="minTrans" placeholder="99000" min="0">
        </div>
      </div>

      <div class="field">
        <label class="label" for="paketVou">Berlaku untuk Paket</label>
        <select class="select" id="paketVou">
          <option>Semua paket</option>
          <option>Basic</option>
          <option>Premium</option>
          <option>Ultimate</option>
        </select>
      </div>

      <label class="switch"><input type="checkbox" checked><span>Aktifkan voucher setelah disimpan</span></label>

      <div class="modal__foot" style="margin-top:0">
        <button class="btn btn--ghost" type="button" data-modal-close>Batal</button>
        <button class="btn btn--primary" type="submit">Simpan Voucher</button>
      </div>
    </form>
  </div>
</div>
@endsection