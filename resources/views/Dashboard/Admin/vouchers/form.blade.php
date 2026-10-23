@extends('layouts.admin.main')
@section('title', ($voucher->exists ? 'Ubah Voucher' : 'Buat Voucher') . ' — Ganesha Nusa')
@section('content')
    <nav class="crumbs"><a href="{{ route('admin.vouchers.index') }}">Voucher</a><span class="crumbs__sep">›</span><span>{{ $voucher->exists ? 'Ubah' : 'Buat' }}</span></nav>
    <header class="page-head"><h1>{{ $voucher->exists ? 'Ubah Voucher' : 'Buat Voucher' }}</h1><p>Atur diskon dan syarat penggunaan voucher untuk pembelian paket.</p></header>
    @if ($errors->any())
        <div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <form class="card stack max-w-3xl" style="--stack:1.25rem" method="POST" action="{{ $voucher->exists ? route('admin.vouchers.update', $voucher) : route('admin.vouchers.store') }}">
        @csrf
        @if ($voucher->exists) @method('PUT') @endif
        <div class="field">
            <label class="label" for="code">Kode Voucher</label>
            <input class="input uppercase" id="code" name="code" value="{{ old('code', $voucher->code) }}" maxlength="40" pattern="[A-Za-z0-9_-]+" required>
            <span class="field__hint">Huruf, angka, tanda hubung, atau garis bawah. Kode disimpan dalam huruf kapital.</span>
        </div>
        <div class="form-grid">
            <div class="field"><label class="label" for="type">Tipe Diskon</label><select class="select" id="type" name="type" required><option value="fixed" @selected(old('type', $voucher->type) === 'fixed')>Nominal (rupiah)</option><option value="percentage" @selected(old('type', $voucher->type) === 'percentage')>Persentase (1–100%)</option></select></div>
            <div class="field"><label class="label" for="value">Nilai Diskon</label><input class="input" type="number" id="value" name="value" min="1" max="4294967295" step="1" value="{{ old('value', $voucher->value) }}" required><span class="field__hint">Potongan maksimal sebesar harga paket. Persentase dibulatkan ke bawah ke rupiah penuh.</span></div>
            <div class="field"><label class="label" for="starts_on">Tanggal Mulai (opsional)</label><input class="input" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $voucher->starts_on?->format('Y-m-d')) }}"></div>
            <div class="field"><label class="label" for="ends_on">Tanggal Berakhir (opsional)</label><input class="input" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $voucher->ends_on?->format('Y-m-d')) }}"><span class="field__hint">Berlaku hingga akhir tanggal ini.</span></div>
            <div class="field"><label class="label" for="usage_limit">Kuota Total (opsional)</label><input class="input" type="number" id="usage_limit" name="usage_limit" min="1" max="4294967295" step="1" value="{{ old('usage_limit', $voucher->usage_limit) }}"><span class="field__hint">Kosong berarti tanpa batas. Pesanan pending mencadangkan kuota.</span></div>
            <div class="field"><label class="label" for="minimum_amount">Minimum Transaksi (rupiah)</label><input class="input" type="number" id="minimum_amount" name="minimum_amount" min="0" max="4294967295" step="1" value="{{ old('minimum_amount', $voucher->minimum_amount) }}" required></div>
        </div>
        <div class="field"><label class="label" for="package_id">Berlaku untuk Paket</label><select class="select" id="package_id" name="package_id"><option value="">Semua paket</option>@foreach ($packages as $package)<option value="{{ $package->id }}" @selected((string) old('package_id', $voucher->package_id) === (string) $package->id)>{{ $package->name }}{{ $package->is_active ? '' : ' — nonaktif' }}</option>@endforeach</select></div>
        <input type="hidden" name="is_active" value="0">
        <label class="switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $voucher->is_active))><span>Voucher aktif</span></label>
        <p class="text-sm text-soft">Perubahan hanya berlaku untuk pesanan baru. Diskon pesanan yang sudah dibuat tetap mengikuti nilai saat pemesanan.</p>
        <div class="btn-row"><button class="btn btn--primary" type="submit">Simpan Voucher</button><a class="btn btn--ghost" href="{{ route('admin.vouchers.index') }}">Batal</a></div>
    </form>
@endsection
