@extends('layouts.admin.main')

@section('title', 'Laporan Transaksi — LONTAR')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Transaksi</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Bisnis</span>
        <h1 style="margin-top:.5rem">Laporan Transaksi</h1>
        <p>Semua transaksi paket belajar beserta status pembayarannya.</p>
      </div>
      <div class="btn-row">
        <button class="btn btn--ghost" type="button" onclick="L.toast('Laporan diunduh sebagai CSV.','success')"><svg data-icon="download"></svg> Ekspor CSV</button>
        <button class="btn btn--ghost" type="button" data-print><svg data-icon="printer"></svg> Cetak</button>
      </div>
    </header>

    <section class="grid grid-4" style="--gap:1.15rem;margin-bottom:1.75rem">
      <article class="stat">
        <span class="stat__icon ico--mint"><svg data-icon="check-circle"></svg></span>
        <div><div class="stat__label">Transaksi Berhasil</div><div class="stat__value" data-count-to="418">0</div><div class="stat__delta text-soft">Bulan ini</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--amber"><svg data-icon="clock"></svg></span>
        <div><div class="stat__label">Menunggu Bayar</div><div class="stat__value" data-count-to="27">0</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon ico--pink"><svg data-icon="x-circle"></svg></span>
        <div><div class="stat__label">Gagal</div><div class="stat__value" data-count-to="14">0</div></div>
      </article>
      <article class="stat">
        <span class="stat__icon"><svg data-icon="credit-card"></svg></span>
        <div><div class="stat__label">Total Transaksi</div><div class="stat__value" data-count-to="470">0</div><div class="stat__delta is-up"><svg data-icon="trending-up"></svg> +11% dari September</div></div>
      </article>
    </section>

    <section class="card card--tight" style="margin-bottom:1.5rem">
      <div class="flex items-center gap-3 flex-wrap">
        <div class="field" style="flex:1;min-width:200px">
          <label class="label" for="dari">Tanggal dari</label>
          <input class="input" type="date" id="dari" value="2026-10-01">
        </div>
        <div class="field" style="flex:1;min-width:200px">
          <label class="label" for="sampai">Sampai</label>
          <input class="input" type="date" id="sampai" value="2026-10-03">
        </div>
        <div class="field" style="flex:1;min-width:180px">
          <label class="label" for="statusF">Status</label>
          <select class="select" id="statusF">
            <option>Semua status</option>
            <option>Paid</option>
            <option>Pending</option>
            <option>Failed</option>
            <option>Expired</option>
          </select>
        </div>
        <div class="field" style="flex:1;min-width:180px">
          <label class="label" for="paketF">Paket</label>
          <select class="select" id="paketF">
            <option>Semua paket</option>
            <option>Basic</option>
            <option>Premium</option>
            <option>Ultimate</option>
          </select>
        </div>
        <button class="btn btn--dark" type="button" style="align-self:flex-end" onclick="L.toast('Filter diterapkan pada tabel.','success')">
          <svg data-icon="filter"></svg> Terapkan
        </button>
      </div>
    </section>

    <section class="card card--pad-0">
      <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-bottom:2px solid var(--border)">
        <div><div class="card__title">Daftar Transaksi</div><div class="card__sub">470 transaksi pada rentang terpilih</div></div>
        <div class="input-group" style="min-width:230px">
          <span class="input-group__icon"><svg data-icon="search"></svg></span>
          <input class="input" type="search" placeholder="Cari ID atau nama..." aria-label="Cari transaksi" data-table-filter="tabelTrx">
        </div>
      </div>

      <div class="table-wrap" style="border:none;border-radius:0;box-shadow:none">
        <table class="table" id="tabelTrx">
          <thead>
            <tr>
              <th>ID Transaksi</th>
              <th>Peserta</th>
              <th>Paket</th>
              <th>Voucher</th>
              <th class="is-sortable num" aria-sort="descending">Total<span class="sort-ind">↕</span></th>
              <th>Metode</th>
              <th>Status</th>
              <th>Tanggal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code class="text-mono text-xs">TRX-90412</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm avatar--pink">S</span><div><strong>Sari Wulandari</strong><span>sari.w@mail.com</span></div></div></td>
              <td>Premium</td>
              <td><span class="badge badge--flat">GAIN20</span></td>
              <td class="num" data-sort="99000">Rp99.000</td>
              <td>Transfer Bank</td>
              <td><span class="badge badge--mint badge--flat">Paid</span></td>
              <td>03 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90411</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm">R</span><div><strong>Rizky Aditya</strong><span>rizky.a@mail.com</span></div></div></td>
              <td>Basic</td>
              <td><span class="badge badge--flat">-</span></td>
              <td class="num" data-sort="49000">Rp49.000</td>
              <td>E-Wallet</td>
              <td><span class="badge badge--mint badge--flat">Paid</span></td>
              <td>03 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90410</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm avatar--amber">D</span><div><strong>Dewi Lestari</strong><span>dewi.l@mail.com</span></div></div></td>
              <td>Ultimate</td>
              <td><span class="badge badge--flat">-</span></td>
              <td class="num" data-sort="249000">Rp249.000</td>
              <td>QRIS</td>
              <td><span class="badge badge--amber badge--flat">Pending</span></td>
              <td>03 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90409</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm avatar--mint">Y</span><div><strong>Yoga Pratama</strong><span>yoga.p@mail.com</span></div></div></td>
              <td>Premium</td>
              <td><span class="badge badge--flat">CERDAS15</span></td>
              <td class="num" data-sort="79000">Rp79.000</td>
              <td>Transfer Bank</td>
              <td><span class="badge badge--mint badge--flat">Paid</span></td>
              <td>02 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90408</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm avatar--violet">I</span><div><strong>Intan Permata</strong><span>intan.p@mail.com</span></div></div></td>
              <td>Basic</td>
              <td><span class="badge badge--flat">-</span></td>
              <td class="num" data-sort="49000">Rp49.000</td>
              <td>E-Wallet</td>
              <td><span class="badge badge--danger badge--flat">Failed</span></td>
              <td>02 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90407</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm">B</span><div><strong>Bayu Pamungkas</strong><span>bayu.p@mail.com</span></div></div></td>
              <td>Premium</td>
              <td><span class="badge badge--flat">-</span></td>
              <td class="num" data-sort="99000">Rp99.000</td>
              <td>Transfer Bank</td>
              <td><span class="badge badge--mint badge--flat">Paid</span></td>
              <td>01 Okt 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
            <tr>
              <td><code class="text-mono text-xs">TRX-90405</code></td>
              <td><div class="userchip"><span class="avatar avatar--sm avatar--coral">F</span><div><strong>Fajar Nugroho</strong><span>fajar.n@mail.com</span></div></div></td>
              <td>Basic</td>
              <td><span class="badge badge--flat">NAVIGASI10</span></td>
              <td class="num" data-sort="44000">Rp44.000</td>
              <td>QRIS</td>
              <td><span class="badge badge--flat">Expired</span></td>
              <td>30 Sep 2026</td>
              <td><button class="btn btn--ghost btn--square btn--sm" type="button" onclick="L.toast('Detail transaksi dibuka.','info')" aria-label="Detail"><svg data-icon="eye"></svg></button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="empty" data-table-empty hidden style="margin:1.5rem">
        <span class="empty__icon"><svg data-icon="search"></svg></span>
        <strong style="color:var(--ink);font-family:var(--font-display)">Transaksi tidak ditemukan</strong>
      </div>

      <div class="flex items-center justify-between flex-wrap" style="gap:1rem;padding:1.25rem 1.5rem;border-top:2px solid var(--border)">
        <span class="text-xs text-soft">Menampilkan 7 dari 470 transaksi</span>
        <div class="pager" style="margin-top:0">
          <button class="pager__btn" type="button" disabled aria-label="Sebelumnya"><svg data-icon="chevron-left"></svg></button>
          <button class="pager__btn is-active" type="button">1</button>
          <button class="pager__btn" type="button">2</button>
          <button class="pager__btn" type="button">3</button>
          <button class="pager__btn" type="button" aria-label="Berikutnya"><svg data-icon="chevron-right"></svg></button>
        </div>
      </div>
    </section>
@endsection