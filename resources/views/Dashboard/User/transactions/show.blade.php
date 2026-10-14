@extends('layouts.user.main')
@section('title', 'Detail Transaksi — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('transactions.index') }}">Transaksi Saya</a><span class="crumbs__sep">›</span><span class="crumbs__current">Detail Transaksi</span></nav>
<header class="page-head"><span class="eyebrow">Pembelian Paket</span><h1 style="margin-top:.5rem">{{ $transaction->package_snapshot['name'] }}</h1><p class="break-all">{{ $transaction->order_id }}</p></header>
@if ($errors->any() || $paymentError)<div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach @if ($paymentError)<li>{{ $paymentError }}</li>@endif</ul></div>@endif
<div class="grid gap-6 lg:grid-cols-2">
<section class="card">
    <h2 class="card__title">Status pembayaran</h2>
    <p style="margin-top:1rem"><span class="badge badge--{{ $transaction->status === \App\Enums\TransactionStatus::Paid ? 'mint' : 'amber' }} badge--flat">{{ $transaction->status->label() }}</span></p>
    <div class="price__amount" style="margin-top:1.5rem">{{ $transaction->formattedAmount() }}</div>
    <ul class="price__list">
        <li>{{ (\App\Enums\LeaderboardAccess::tryFrom($transaction->package_snapshot['leaderboard_access'] ?? 'none') ?? \App\Enums\LeaderboardAccess::None)->label() }}</li>
        <li>Dibuat: {{ $transaction->created_at->format('d/m/Y H:i') }}</li>
        @if ($transaction->status === \App\Enums\TransactionStatus::Pending)<li>Batas pembayaran: {{ $transaction->expires_at->format('d/m/Y H:i') }}</li>@endif
        @if ($transaction->paid_at)<li>Terverifikasi: {{ $transaction->paid_at->format('d/m/Y H:i') }}</li>@endif
        <li>Metode: {{ $transaction->payment_type ? str_replace('_', ' ', $transaction->payment_type) : ($transaction->provider === 'free' ? 'Paket gratis' : 'Belum dipilih') }}</li>
        @if ($transaction->checked_at)<li>Terakhir diperiksa: {{ $transaction->checked_at->format('d/m/Y H:i') }}</li>@endif
    </ul>
    @if ($transaction->status === \App\Enums\TransactionStatus::Pending)
        <p class="text-sm text-soft">Sudah membayar? Periksa status jika aksesmu belum aktif. Kamu bisa melanjutkan pembayaran dari pesanan yang sama.</p>
        @if ($transaction->expires_at->isFuture())
        <form method="POST" action="{{ route('transactions.pay', $transaction) }}" style="margin-top:1rem">@csrf<button class="btn btn--primary btn--block">{{ $transaction->payment_url ? 'Bayar di Midtrans' : 'Siapkan pembayaran' }} <svg data-icon="arrow-right"></svg></button></form>
        @endif
    @endif
    @if ($transaction->provider === 'midtrans')
        <form method="POST" action="{{ route('transactions.refresh', $transaction) }}" style="margin-top:.75rem">@csrf<button class="btn btn--ghost btn--block">Periksa status pembayaran</button></form>
    @endif
    @if (in_array($transaction->status, [\App\Enums\TransactionStatus::Failed, \App\Enums\TransactionStatus::Expired, \App\Enums\TransactionStatus::Refunded], true))
        <a class="btn btn--primary btn--block" style="margin-top:1rem" href="{{ route('packages.index') }}">Pilih paket lagi</a>
    @endif
</section>
<section class="card">
    <h2 class="card__title">Akses paket</h2>
    @if ($transaction->userPackage)
        <p style="margin-top:1rem"><strong>{{ $transaction->userPackage->statusLabel() }}</strong></p>
        <p class="text-soft" style="margin-top:.75rem">{{ $transaction->userPackage->expires_at ? 'Akses sampai '.$transaction->userPackage->expires_at->format('d/m/Y H:i') : 'Tanpa batas waktu' }}</p>
        <p class="text-soft">Sisa kuota tryout: {{ $transaction->userPackage->remainingTryouts() ?? 'Tanpa batas' }}</p>
        <a class="btn btn--primary" style="margin-top:1rem" href="{{ route('packages.index') }}">Buka paket saya</a>
    @else
        <p class="text-soft" style="margin-top:1rem">Akses belum diaktifkan. Masa aktif baru dimulai setelah pembayaran terverifikasi.</p>
    @endif
    <ul class="price__list">
        <li>{{ $transaction->package_snapshot['duration_days'] === null ? 'Tanpa batas waktu' : ($transaction->package_snapshot['duration_days'] === 365 ? '1 tahun akses' : $transaction->package_snapshot['duration_days'].' hari akses') }}</li>
        <li>{{ $transaction->package_snapshot['tryout_quota'] === null ? 'Kuota tryout tanpa batas' : 'Total kuota '.$transaction->package_snapshot['tryout_quota'].' sesi tryout' }}</li>
        @foreach ($transaction->package_snapshot['materials'] as $material)<li>{{ $material['title'] }}</li>@endforeach
        @foreach ($transaction->package_snapshot['exams'] as $exam)<li>{{ $exam['title'] }}</li>@endforeach
    </ul>
</section>
</div>
@endsection
