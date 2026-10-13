@extends('layouts.user.main')

@section('title', 'Leaderboard — LONTAR CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Leaderboard</span>
    </nav>

    <header class="page-head page-head__row">
      <div>
        <span class="eyebrow">Papan Peringkat</span>
        <h1 style="margin-top:.5rem">Leaderboard</h1>
        <p>Papan peringkat diperbarui setelah setiap tryout. Mekanisme ranking final menunggu konfirmasi client.</p>
      </div>
      <div class="card card--tight" style="min-width:230px;box-shadow:var(--sh-soft-blue)">
        <div class="stat__label">Peringkatmu</div>
        <div class="flex items-center gap-3" style="margin-top:.25rem">
          <div class="fw-800" style="font-family:var(--font-display);font-size:2rem;color:var(--primary-700);line-height:1">#18</div>
          <span class="badge badge--mint badge--flat"><svg data-icon="trending-up"></svg> Naik 6</span>
        </div>
        <p class="text-xs text-soft" style="margin-top:.35rem">Dari 1.284 peserta aktif minggu ini</p>
      </div>
    </header>

    {{-- Cakupan leaderboard masih TBD (PRD §28), jadi ini difilterkan
         sebagai pill filter, bukan tab: hanya "Semua Tryout" yang terisi data. --}}
    <div class="flex items-center justify-between flex-wrap" style="gap:1rem;margin-bottom:1.5rem">
      <div class="pills">
        <button class="pill is-active" type="button" onclick="L.toast('Menampilkan gabungan semua tryout.','info')">Semua Tryout</button>
        <button class="pill" type="button" onclick="L.toast('Leaderboard per kategori masih menunggu keputusan client.','info')">TWK</button>
        <button class="pill" type="button" onclick="L.toast('Leaderboard per kategori masih menunggu keputusan client.','info')">TIU</button>
        <button class="pill" type="button" onclick="L.toast('Leaderboard per kategori masih menunggu keputusan client.','info')">TKP</button>
        <button class="pill" type="button" onclick="L.toast('Leaderboard mingguan belum diaktifkan pada prototipe.','info')">Mingguan</button>
      </div>
      <p class="text-xs text-soft"><svg data-icon="info" style="width:14px;height:14px;display:inline;vertical-align:-2px"></svg> Cakupan leaderboard masih TBD.</p>
    </div>

    {{-- ============ PODIUM ============ --}}
    <section class="grid grid-3" style="--gap:1.5rem;align-items:end;margin-bottom:1.75rem">

      <article class="card card--pop">
        <div class="flex items-center gap-3">
          <span class="avatar avatar--lg avatar--mint">A</span>
          <div style="flex:1;min-width:0">
            <span class="lb-rank lb-rank--2" style="width:32px;height:32px;font-size:var(--fs-sm)">2</span>
            <h2 class="lb-name" style="margin-top:.35rem">Ahmad Fauzi</h2>
            <p class="lb-meta">9 tryout - rata-rata 455</p>
          </div>
        </div>
        <div class="flex items-center justify-between" style="margin-top:1rem;padding-top:.85rem;border-top:2px dashed var(--border)">
          <span class="text-xs text-soft fw-700">Skor tryout terakhir</span>
          <span class="lb-score">472</span>
        </div>
      </article>

      <article class="card card--pop card--amber">
        <span class="star-badge"><svg data-icon="crown"></svg> Juara</span>
        <div class="flex items-center gap-3">
          <span class="avatar avatar--lg avatar--pink">D</span>
          <div style="flex:1;min-width:0">
            <span class="lb-rank lb-rank--1" style="width:32px;height:32px;font-size:var(--fs-sm)">1</span>
            <h2 class="lb-name" style="margin-top:.35rem">Dewi Anggraini</h2>
            <p class="lb-meta">12 tryout - rata-rata 468</p>
          </div>
        </div>
        <div class="flex items-center justify-between" style="margin-top:1rem;padding-top:.85rem;border-top:2px dashed var(--border)">
          <span class="text-xs text-soft fw-700">Skor tryout terakhir</span>
          <span class="lb-score" style="font-size:var(--fs-xl)">481</span>
        </div>
      </article>

      <article class="card card--pop">
        <div class="flex items-center gap-3">
          <span class="avatar avatar--lg avatar--coral">N</span>
          <div style="flex:1;min-width:0">
            <span class="lb-rank lb-rank--3" style="width:32px;height:32px;font-size:var(--fs-sm)">3</span>
            <h2 class="lb-name" style="margin-top:.35rem">Nadia Putri</h2>
            <p class="lb-meta">7 tryout - rata-rata 441</p>
          </div>
        </div>
        <div class="flex items-center justify-between" style="margin-top:1rem;padding-top:.85rem;border-top:2px dashed var(--border)">
          <span class="text-xs text-soft fw-700">Skor tryout terakhir</span>
          <span class="lb-score">466</span>
        </div>
      </article>
    </section>

    <div class="panes" style="--gap:1.5rem;">

      {{-- ============ DAFTAR ============ --}}
      <section class="card">
        <div class="card__head">
          <div>
            <div class="card__title">Peringkat 1 sampai 25</div>
            <div class="card__sub">Diperbarui 3 Oktober 2026, 19:30</div>
          </div>
          <div class="input-group" style="min-width:200px">
            <span class="input-group__icon"><svg data-icon="search"></svg></span>
            <input class="input" type="search" placeholder="Cari nama..." aria-label="Cari nama peserta" data-table-filter="tabelPeringkat">
          </div>
        </div>

        <div class="table-wrap" style="border:none;box-shadow:none">
          <table class="table" id="tabelPeringkat" style="min-width:520px">
            <thead>
              <tr><th class="num" style="width:64px">Peringkat</th><th>Peserta</th><th class="num">Tryout</th><th class="num">Rata-rata</th><th class="num">Terakhir</th></tr>
            </thead>
            <tbody>
              <tr><td class="num"><span class="lb-rank lb-rank--1" style="width:34px;height:34px;font-size:var(--fs-sm)">1</span></td><td><div class="userchip"><span class="avatar avatar--sm avatar--pink">D</span><div><strong>Dewi Anggraini</strong><span>Jakarta</span></div></div></td><td class="num">12</td><td class="num">468</td><td class="num">481</td></tr>
              <tr><td class="num"><span class="lb-rank lb-rank--2" style="width:34px;height:34px;font-size:var(--fs-sm)">2</span></td><td><div class="userchip"><span class="avatar avatar--sm avatar--mint">A</span><div><strong>Ahmad Fauzi</strong><span>Bandung</span></div></div></td><td class="num">9</td><td class="num">455</td><td class="num">472</td></tr>
              <tr><td class="num"><span class="lb-rank lb-rank--3" style="width:34px;height:34px;font-size:var(--fs-sm)">3</span></td><td><div class="userchip"><span class="avatar avatar--sm avatar--coral">N</span><div><strong>Nadia Putri</strong><span>Surabaya</span></div></div></td><td class="num">7</td><td class="num">441</td><td class="num">466</td></tr>
              <tr><td class="num">4</td><td><div class="userchip"><span class="avatar avatar--sm">B</span><div><strong>Bima Saputra</strong><span>Yogyakarta</span></div></div></td><td class="num">11</td><td class="num">438</td><td class="num">461</td></tr>
              <tr><td class="num">5</td><td><div class="userchip"><span class="avatar avatar--sm avatar--violet">S</span><div><strong>Sarah Nur</strong><span>Medan</span></div></div></td><td class="num">6</td><td class="num">432</td><td class="num">458</td></tr>
              <tr><td class="num">6</td><td><div class="userchip"><span class="avatar avatar--sm avatar--amber">F</span><div><strong>Fajar Hidayat</strong><span>Makassar</span></div></div></td><td class="num">14</td><td class="num">427</td><td class="num">455</td></tr>
              <tr><td class="num">7</td><td><div class="userchip"><span class="avatar avatar--sm">L</span><div><strong>Lina Marlina</strong><span>Semarang</span></div></div></td><td class="num">8</td><td class="num">421</td><td class="num">449</td></tr>
              <tr><td class="num">8</td><td><div class="userchip"><span class="avatar avatar--sm avatar--mint">H</span><div><strong>Hendra Gunawan</strong><span>Palembang</span></div></div></td><td class="num">5</td><td class="num">415</td><td class="num">443</td></tr>
              <tr><td class="num">9</td><td><div class="userchip"><span class="avatar avatar--sm avatar--pink">M</span><div><strong>Maya Puspita</strong><span>Denpasar</span></div></div></td><td class="num">10</td><td class="num">409</td><td class="num">440</td></tr>
              <tr><td class="num">10</td><td><div class="userchip"><span class="avatar avatar--sm">T</span><div><strong>Taufik Rahman</strong><span>Banda Aceh</span></div></div></td><td class="num">4</td><td class="num">404</td><td class="num">436</td></tr>
            </tbody>
          </table>
        </div>

        <div class="empty" data-table-empty hidden style="margin-top:1rem">
          <span class="empty__icon"><svg data-icon="search"></svg></span>
          <strong style="color:var(--ink);font-family:var(--font-display)">Peserta tidak ditemukan</strong>
        </div>

        <div class="pager">
          <button class="pager__btn" type="button" disabled aria-label="Sebelumnya"><svg data-icon="chevron-left"></svg></button>
          <button class="pager__btn is-active" type="button">1</button>
          <button class="pager__btn" type="button">2</button>
          <button class="pager__btn" type="button">3</button>
          <button class="pager__btn" type="button" aria-label="Berikutnya"><svg data-icon="chevron-right"></svg></button>
        </div>
      </section>

      {{-- ============ SISI KANAN ============ --}}
      <aside class="stack" style="--stack:1.5rem">

        <section class="card card--pop card--amber">
          <span class="card__icon ico--coral" style="position:static"><svg data-icon="trophy"></svg></span>
          <h2 class="card__title" style="margin-top:.75rem">Posisi Kamu</h2>
          <div class="flex items-center gap-4" style="margin-top:1rem">
            <div class="ring ring--sm" data-pct="72">
              <div class="ring__label">
                <div class="ring__value">#18</div>
                <div class="ring__unit">dari 1.284</div>
              </div>
            </div>
            <div class="kv" style="flex:1">
              <div class="kv__row"><span class="kv__key">Skor terakhir</span><span class="kv__val">452</span></div>
              <div class="kv__row"><span class="kv__key">Rata-rata</span><span class="kv__val">428</span></div>
              <div class="kv__row"><span class="kv__key">Tryout</span><span class="kv__val">4</span></div>
            </div>
          </div>
          <div class="alert alert--info" style="margin-top:1.25rem">
            <span class="alert__icon"><svg data-icon="info"></svg></span>
            <div>Perlu 9 poin lagi untuk masuk 10 besar.</div>
          </div>
          <a class="btn btn--primary btn--block" href="{{ route('tryouts.index') }}" style="margin-top:1rem">Coba Kejar Peringkat<svg data-icon="arrow-right"></svg></a>
        </section>

        <section class="card">
          <div class="card__head">
            <div><div class="card__title">Aturan Peringkat</div><div class="card__sub">Dipakai sementara pada prototipe</div></div>
          </div>
          <div class="legend">
            <div class="legend__item"><span class="legend__swatch legend__swatch--answered"></span> Skor tryout terakhir</div>
            <div class="legend__item"><span class="legend__swatch legend__swatch--flagged"></span> Rata-rata seluruh tryout</div>
            <div class="legend__item"><span class="legend__swatch legend__swatch--current"></span> Jumlah tryout yang diselesaikan</div>
          </div>
          <div class="alert alert--warn" style="margin-top:1rem">
            <span class="alert__icon"><svg data-icon="alert"></svg></span>
            <div>Ranking per tryout, per kategori, atau global masih <strong>TBD</strong>.</div>
          </div>
        </section>
      </aside>
    </div>
@endsection