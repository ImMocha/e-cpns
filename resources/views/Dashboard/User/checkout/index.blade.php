@extends('layouts.user.main')

@section('title', 'Checkout — LONTAR CPNS Online')

@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <a href="{{ route('packages.index') }}">Paket Belajar</a>
      <span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span>
      <span class="crumbs__current">Checkout</span>
    </nav>

    <header class="page-head">
      <h1>Checkout</h1>
      <p>Pilih metode pembayaran dan selesaikan transaksi untuk mengaktifkan akses paket.</p>
    </header>

    <div class="steps" style="margin-bottom:1.75rem" aria-label="Tahapan checkout">
      <span class="step is-done"><span class="step__num"><svg data-icon="check"></svg></span><span class="step__label">Pilih Paket</span></span>
      <span class="step__line"></span>
      <span class="step is-current"><span class="step__num">2</span><span class="step__label">Pembayaran</span></span>
      <span class="step__line"></span>
      <span class="step"><span class="step__num">3</span><span class="step__label">Akses Aktif</span></span>
    </div>

    <div class="panes" style="--gap:1.5rem;">

      {{-- ============ FORM ============ --}}
      <form class="card card--pop" id="formCheckout" data-demo-submit="Pembayaran berhasil. Paket Premium sudah aktif." data-demo-redirect="{{ route('dashboard') }}">
        <div class="stack" style="--stack:1.5rem">

          <section>
            <div class="card__title" style="margin-bottom:1rem">1. Detail Paket</div>
            <div class="card card--tight card--dashed" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
              <span class="card__icon ico--primary" style="position:static;width:44px;height:44px;flex:0 0 44px"><svg data-icon="package"></svg></span>
              <div style="flex:1;min-width:160px">
                <strong style="display:block;font-family:var(--font-display);color:var(--ink)">Paket Premium</strong>
                <span class="text-xs text-soft">Akses 90 hari</span>
              </div>
              <span class="badge badge--premium">Rp99.000</span>
            </div>
          </section>

          <section>
            <div class="card__title" style="margin-bottom:1rem">2. Metode Pembayaran</div>
            <div class="grid" style="--gap:.6rem">

              <label class="card card--tight flex items-center gap-3" style="cursor:pointer;border-color:var(--primary);box-shadow:var(--sh-primary)">
                <input type="radio" name="metode" value="transfer" checked style="width:20px;height:20px;accent-color:var(--primary)">
                <span class="tile__glyph" style="width:42px;height:42px;border-radius:12px;flex:0 0 42px"><svg data-icon="credit-card"></svg></span>
                <div style="flex:1"><strong style="display:block;color:var(--ink);font-family:var(--font-display)">Transfer Bank</strong><span class="text-xs text-soft">BCA, BNI, Mandiri, BRI</span></div>
                <span class="badge badge--mint badge--flat">Tanpa biaya</span>
              </label>

              <label class="card card--tight flex items-center gap-3" style="cursor:pointer">
                <input type="radio" name="metode" value="ewallet" style="width:20px;height:20px;accent-color:var(--primary)">
                <span class="tile__glyph" style="width:42px;height:42px;border-radius:12px;flex:0 0 42px;background:var(--pop-mint-050);color:var(--success)"><svg data-icon="smartphone"></svg></span>
                <div style="flex:1"><strong style="display:block;color:var(--ink);font-family:var(--font-display)">E-Wallet</strong><span class="text-xs text-soft">GoPay, OVO, DANA, ShopeePay</span></div>
              </label>

              <label class="card card--tight flex items-center gap-3" style="cursor:pointer">
                <input type="radio" name="metode" value="qris" style="width:20px;height:20px;accent-color:var(--primary)">
                <span class="tile__glyph" style="width:42px;height:42px;border-radius:12px;flex:0 0 42px;background:var(--pop-violet-050);color:var(--pop-violet)"><svg data-icon="grid"></svg></span>
                <div style="flex:1"><strong style="display:block;color:var(--ink);font-family:var(--font-display)">QRIS</strong><span class="text-xs text-soft">Scan dari aplikasi apa pun</span></div>
              </label>

              <div class="alert alert--warn" style="margin-top:.5rem">
                <span class="alert__icon"><svg data-icon="alert"></svg></span>
                <div>Payment gateway yang dipakai belum ditentukan. Pada prototipe, pilihan metode hanya simulasi tampilan.</div>
              </div>
            </div>
          </section>

          <section>
            <div class="card__title" style="margin-bottom:1rem">3. Kode Voucher</div>
            <div class="flex gap-2" style="--gap:.6rem;flex-wrap:wrap">
              <input class="input" type="text" id="voucher" placeholder="Contoh: GAIN20" aria-label="Kode voucher" style="flex:1;min-width:160px;text-transform:uppercase">
              <button class="btn btn--ghost" type="button" id="btnVoucher">Gunakan</button>
            </div>
            <p class="field__hint" id="voucherHint">Voucher akan mengurangi total pembayaran di bawah.</p>
          </section>

          <section>
            <div class="card__title" style="margin-bottom:1rem">4. Detail Pembeli</div>
            <div class="form-grid">
              <div class="field">
                <label class="label" for="nama">Nama Lengkap</label>
                <input class="input" type="text" id="nama" value="Rani Kusuma" required>
              </div>
              <div class="field">
                <label class="label" for="email">Email</label>
                <input class="input" type="email" id="email" value="rani.kusuma@mail.com" required>
              </div>
              <div class="field span-2">
                <label class="label" for="telp">Nomor Telepon</label>
                <input class="input" type="tel" id="telp" placeholder="08xxxxxxxxxx" required>
                <span class="field__hint">Dipakai untuk menerima notifikasi pembayaran.</span>
              </div>
            </div>
          </section>

          <label class="check">
            <input type="checkbox" checked>
            <span>Saya setuju dengan ketentuan pembelian paket belajar LONTAR.</span>
          </label>

          <button class="btn btn--primary btn--lg btn--block" type="submit">
            Bayar Sekarang Rp<span id="btnTotal">99.000</span>
            <span class="btn__icon"><svg data-icon="arrow-right"></svg></span>
          </button>
        </div>
      </form>

      {{-- ============ RINGKASAN ============ --}}
      <aside class="stack" style="--stack:1.5rem;position:sticky;top:86px">

        <section class="card card--pop">
          <div class="card__head">
            <div><div class="card__title">Ringkasan Pesanan</div><div class="card__sub">INV-2026-10-00841</div></div>
            <span class="badge badge--amber badge--flat">Pending</span>
          </div>

          <div class="kv" style="padding-bottom:1.25rem;border-bottom:2px dashed var(--border)">
            <div class="kv__row"><span class="kv__key">Paket Premium</span><span class="kv__val">Rp99.000</span></div>
            <div class="kv__row"><span class="kv__key">Diskon voucher</span><span class="kv__val" id="sumVoucher">-Rp0</span></div>
            <div class="kv__row"><span class="kv__key">Biaya layanan</span><span class="kv__val">Rp0</span></div>
          </div>

          <div class="flex items-center justify-between" style="padding-top:1.25rem">
            <div>
              <div class="stat__label">Total Pembayaran</div>
              <div class="fw-800" id="sumTotal" style="font-family:var(--font-display);font-size:2rem;color:var(--ink);line-height:1.1;letter-spacing:-.03em">Rp99.000</div>
            </div>
            <span class="tile__glyph" style="background:var(--pop-amber)"><svg data-icon="credit-card"></svg></span>
          </div>

          <div class="alert alert--success" style="margin-top:1.25rem">
            <span class="alert__icon"><svg data-icon="shield"></svg></span>
            <div>Akses paket diberikan otomatis setelah status pembayaran berhasil.</div>
          </div>
        </section>

        <section class="card card--tight card--dashed">
          <div class="card__title" style="margin-bottom:.75rem">Status Transaksi</div>
          <div class="grid" style="--gap:.5rem">
            <div class="flex items-center gap-2 text-sm"><span class="badge badge--mint badge--flat">Pending</span><span class="text-soft">Pesanan dibuat</span></div>
            <div class="flex items-center gap-2 text-sm"><span class="badge badge--flat">Paid</span><span class="text-soft">Menunggu pembayaran</span></div>
            <div class="flex items-center gap-2 text-sm"><span class="badge badge--flat">Failed</span><span class="text-soft">Pembayaran gagal</span></div>
            <div class="flex items-center gap-2 text-sm"><span class="badge badge--flat">Expired</span><span class="text-soft">Kedaluwarsa</span></div>
          </div>
        </section>
      </aside>
    </div>

<script>
  /* Perhitungan total dengan voucher */
  (function () {
    var BASE = 99000;
    var voucherInput = document.getElementById('voucher');
    var btn = document.getElementById('btnVoucher');
    var hint = document.getElementById('voucherHint');
    var sumVoucher = document.getElementById('sumVoucher');
    var sumTotal = document.getElementById('sumTotal');
    var btnTotal = document.getElementById('btnTotal');

    var CODES = { GAIN20: 20000, CERDAS15: 15000, NAVIGASI10: 10000 };

    function rupiah(n) {
      return 'Rp' + new Intl.NumberFormat('id-ID').format(n);
    }

    function apply() {
      var code = (voucherInput.value || '').trim().toUpperCase();
      var disc = CODES[code] || 0;
      var total = Math.max(0, BASE - disc);

      sumVoucher.textContent = '-'.concat(rupiah(disc));
      sumTotal.textContent = rupiah(total);
      btnTotal.textContent = new Intl.NumberFormat('id-ID').format(total);

      if (!code) {
        hint.textContent = 'Voucher akan mengurangi total pembayaran di bawah.';
        hint.style.color = '';
      } else if (disc) {
        hint.textContent = 'Voucher ' + code + ' aktif. Potongan ' + rupiah(disc) + '.';
        hint.style.color = 'var(--success)';
      } else {
        hint.textContent = 'Kode ' + code + ' tidak dikenali.';
        hint.style.color = 'var(--danger)';
      }
    }

    btn.addEventListener('click', function () {
      apply();
      var code = (voucherInput.value || '').trim().toUpperCase();
      L.toast(CODES[code] ? 'Voucher berhasil dipakai.' : 'Kode voucher tidak valid.', CODES[code] ? 'success' : 'error');
    });
    voucherInput.addEventListener('input', apply);
  })();
</script>
@endsection