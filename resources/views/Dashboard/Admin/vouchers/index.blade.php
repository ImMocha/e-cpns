@extends('layouts.admin.main')
@section('title', 'Kelola Voucher — Ganesha Nusa')
@section('content')
    @include('components.flash')
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep">›</span><span class="crumbs__current">Voucher</span></nav>
    <header class="page-head page-head__row">
        <div><span class="eyebrow">Bisnis</span><h1>Voucher &amp; Diskon</h1><p>Buat kode voucher, atur diskon, dan pantau pemakaiannya.</p></div>
        <a class="btn btn--primary" href="{{ route('admin.vouchers.create') }}"><svg data-icon="plus"></svg> Buat Voucher</a>
    </header>
    <section class="grid grid-4 mb-7!" style="--gap:1.15rem">
        <article class="stat"><div><div class="stat__label">Total Voucher</div><div class="stat__value">{{ number_format($stats['total'], 0, ',', '.') }}</div></div></article>
        <article class="stat"><div><div class="stat__label">Transaksi Lunas</div><div class="stat__value">{{ number_format($stats['paid'], 0, ',', '.') }}</div></div></article>
        <article class="stat"><div><div class="stat__label">Diskon Transaksi Lunas</div><div class="stat__value">Rp{{ number_format($stats['discount'], 0, ',', '.') }}</div></div></article>
        <article class="stat"><div><div class="stat__label">Menunggu Pembayaran</div><div class="stat__value">{{ number_format($stats['pending'], 0, ',', '.') }}</div></div></article>
    </section>
    <form method="GET" action="{{ route('admin.vouchers.index') }}" class="flex flex-wrap items-center gap-3 mb-5!">
        <input class="input max-w-sm" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="40" placeholder="Cari kode voucher..." aria-label="Cari voucher">
        <select class="select max-w-xs" name="state" aria-label="Filter status">
            <option value="">Semua status</option>
            @foreach (['active' => 'Dalam masa aktif', 'inactive' => 'Nonaktif', 'expired' => 'Kedaluwarsa', 'scheduled' => 'Terjadwal'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['state'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn--ghost" type="submit">Cari</button>
        <a class="btn btn--ghost" href="{{ route('admin.vouchers.index') }}">Reset</a>
    </form>
    <section class="card card--pad-0">
        <div class="table-wrap">
            <table class="table" id="tabelVoucher">
                <thead><tr><th>Kode / Paket</th><th>Diskon</th><th>Masa Berlaku</th><th>Minimum Transaksi</th><th>Kuota Terpakai</th><th>Lunas</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                        <tr>
                            <td><strong>{{ $voucher->code }}</strong><div class="text-sm text-soft">{{ $voucher->package?->name ?? 'Semua paket' }}</div></td>
                            <td>{{ $voucher->formattedValue() }}<div class="text-sm text-soft">{{ $voucher->type === 'fixed' ? 'Nominal' : 'Persentase' }}</div></td>
                            <td>{{ $voucher->starts_on?->format('d/m/Y') ?? 'Tanpa batas awal' }}<br>{{ $voucher->ends_on?->format('d/m/Y') ?? 'Tanpa batas akhir' }}</td>
                            <td>Rp{{ number_format($voucher->minimum_amount, 0, ',', '.') }}</td>
                            <td>{{ $voucher->reserved_count }} / {{ $voucher->usage_limit ?? 'Tanpa batas' }}</td>
                            <td>{{ $voucher->paid_count }}</td>
                            <td><span class="badge badge--flat">{{ $voucher->statusLabel() }}</span></td>
                            <td><div class="flex flex-wrap items-center gap-2">
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.vouchers.edit', $voucher) }}">Ubah</a>
                                <form method="POST" action="{{ route('admin.vouchers.status', $voucher) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $voucher->is_active ? '0' : '1' }}">
                                    <button class="btn btn--ghost btn--sm" type="submit">{{ $voucher->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty"><strong>Voucher tidak ditemukan</strong><p>Buat voucher baru atau ubah filter pencarian.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5">
            <p class="text-sm text-soft">Kuota terpakai mencakup pesanan menunggu pembayaran dan pemakaian selesai, termasuk pengembalian dana. Kuota pesanan gagal atau kedaluwarsa dilepas setelah status pembayaran diperiksa. Batas tanggal berlaku sampai akhir hari dalam zona waktu aplikasi.</p>
            <x-pager :paginator="$vouchers" />
        </div>
    </section>
@endsection
