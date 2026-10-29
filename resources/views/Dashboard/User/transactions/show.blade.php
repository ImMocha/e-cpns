@extends('layouts.user.main')
@section('title', 'Detail Transaksi — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('transactions.index') }}">Transaksi Saya</a><span class="crumbs__sep">›</span><span class="crumbs__current">Detail Transaksi</span></nav>
<header class="page-head"><span class="eyebrow">Pembelian Paket</span><h1 style="margin-top:.5rem">{{ $transaction->package_snapshot['name'] }}</h1><p class="break-all">{{ $transaction->order_id }}</p></header>
@if ($errors->any() || $paymentError)<div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach @if ($paymentError)<li>{{ $paymentError }}</li>@endif</ul></div>@endif
<div class="grid items-start gap-6 lg:grid-cols-2">
<section class="card min-w-0 overflow-hidden p-0!" aria-labelledby="payment-status-title">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] bg-[var(--primary-050)] p-5 sm:p-6">
        <div class="flex items-center gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-2xl border-2 border-[var(--ink)] bg-[var(--pop-mint)] text-xl text-[var(--pop-ink)] shadow-[3px_3px_0_var(--ink)]" aria-hidden="true"><svg data-icon="credit-card"></svg></span>
            <div>
                <h2 id="payment-status-title" class="font-[var(--font-display)] text-xl! font-extrabold text-[var(--ink)]">Status pembayaran</h2>
                <p class="mt-1! text-sm text-[var(--text-soft)]">Ringkasan transaksi paketmu</p>
            </div>
        </div>
        <span class="badge badge--{{ $transaction->status === \App\Enums\TransactionStatus::Paid ? 'mint' : 'amber' }} badge--flat">{{ $transaction->status->label() }}</span>
    </div>
    <div class="flex flex-col gap-6 p-5 sm:p-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[var(--text-soft)]">Total pembayaran</p>
            <div class="mt-2! font-[var(--font-display)] text-4xl font-extrabold tracking-tight text-[var(--ink)] sm:text-5xl">{{ $transaction->formattedAmount() }}</div>
            <p class="mt-2! flex items-center gap-2 text-sm text-[var(--text-soft)]"><svg data-icon="trophy" aria-hidden="true"></svg>{{ (\App\Enums\LeaderboardAccess::tryFrom($transaction->package_snapshot['leaderboard_access'] ?? 'none') ?? \App\Enums\LeaderboardAccess::None)->label() }}</p>
        </div>
        @if ($transaction->voucher_code)
            <dl class="flex flex-col gap-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4 text-sm">
                <div class="flex flex-wrap justify-between gap-2"><dt>Harga paket</dt><dd class="font-bold">Rp{{ number_format($transaction->amount + $transaction->discount_amount, 0, ',', '.') }}</dd></div>
                <div class="flex flex-wrap justify-between gap-2"><dt class="break-all">Voucher {{ $transaction->voucher_code }}</dt><dd class="font-bold text-[var(--success)]">−Rp{{ number_format($transaction->discount_amount, 0, ',', '.') }}</dd></div>
            </dl>
        @endif
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                <dt class="flex items-center gap-2 text-xs text-[var(--text-soft)]"><svg data-icon="calendar" aria-hidden="true"></svg>Dibuat</dt>
                <dd class="mt-2! text-sm font-bold text-[var(--ink)]"><x-local-time :value="$transaction->created_at" format="d/m/Y H:i" /></dd>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                <dt class="flex items-center gap-2 text-xs text-[var(--text-soft)]"><svg data-icon="credit-card" aria-hidden="true"></svg>Metode pembayaran</dt>
                <dd class="mt-2! break-words text-sm font-bold text-[var(--ink)]">{{ $transaction->payment_type ? ($transaction->payment_type === 'qris' ? 'QRIS' : ucwords(str_replace('_', ' ', $transaction->payment_type))) : ($transaction->provider === 'free' ? 'Paket gratis' : 'Belum dipilih') }}</dd>
            </div>
            @if ($transaction->status === \App\Enums\TransactionStatus::Pending)
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--warning-050)] p-4 sm:col-span-2">
                    <dt class="flex items-center gap-2 text-xs text-[var(--text-soft)]"><svg data-icon="clock" aria-hidden="true"></svg>Batas pembayaran</dt>
                    <dd class="mt-2! text-sm font-bold text-[var(--ink)]"><x-local-time :value="$transaction->expires_at" format="d/m/Y H:i" /></dd>
                </div>
            @endif
            @if ($transaction->paid_at)
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--success-050)] p-4 sm:col-span-2">
                    <dt class="flex items-center gap-2 text-xs text-[var(--text-soft)]"><svg data-icon="check-circle" aria-hidden="true"></svg>Terverifikasi</dt>
                    <dd class="mt-2! text-sm font-bold text-[var(--ink)]"><x-local-time :value="$transaction->paid_at" format="d/m/Y H:i" /></dd>
                </div>
            @endif
        </dl>
        <div class="flex flex-col gap-4 border-t border-dashed border-[var(--border-strong)] pt-5">
    @if ($transaction->status === \App\Enums\TransactionStatus::Pending)
        <p class="text-sm text-soft">Sudah membayar? Periksa status jika aksesmu belum aktif. Kamu bisa melanjutkan pembayaran dari pesanan yang sama.</p>
        @if ($transaction->expires_at->isFuture())
        <form method="POST" action="{{ route('transactions.pay', $transaction) }}" style="margin-top:1rem">@csrf<button class="btn btn--primary btn--block">{{ $transaction->payment_url ? 'Bayar di Midtrans' : 'Siapkan pembayaran' }} <svg data-icon="arrow-right"></svg></button></form>
        @endif
    @endif
    @if ($transaction->provider === 'midtrans')
        <form method="POST" action="{{ route('transactions.refresh', $transaction) }}">@csrf<button class="btn btn--outline btn--block"><svg data-icon="refresh" aria-hidden="true"></svg>Periksa status pembayaran</button></form>
    @endif
    @if ($transaction->checked_at)<p class="flex items-center justify-center gap-2 text-center text-xs text-[var(--text-soft)]"><svg data-icon="clock" aria-hidden="true"></svg>Terakhir diperiksa: <x-local-time :value="$transaction->checked_at" format="d/m/Y H:i" /></p>@endif
    @if (in_array($transaction->status, [\App\Enums\TransactionStatus::Failed, \App\Enums\TransactionStatus::Expired, \App\Enums\TransactionStatus::Refunded], true))
        <a class="btn btn--primary btn--block" style="margin-top:1rem" href="{{ route('packages.index') }}">Pilih paket lagi</a>
    @endif
        </div>
    </div>
</section>
<section class="card card--blue card--pad-0 min-w-0 p-0!" aria-labelledby="package-access-title">
    <div class="flex items-center gap-4 border-b border-[var(--border)] bg-[var(--primary-050)] p-5 sm:p-6">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl border-2 border-[var(--ink)] bg-[var(--pop-amber)] text-xl text-[var(--ink)] shadow-[3px_3px_0_var(--ink)]" aria-hidden="true"><svg data-icon="package"></svg></span>
        <div class="min-w-0">
            <h2 id="package-access-title" class="font-[var(--font-display)] text-xl! font-extrabold text-[var(--ink)]">Akses paket</h2>
            <p class="mt-1! text-sm text-[var(--text-soft)]">Manfaat dan konten belajarmu</p>
        </div>
    </div>

    <div class="flex flex-col gap-6 p-5 sm:p-6">
        @if ($transaction->userPackage)
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <strong class="text-sm text-[var(--ink)]">Status akses</strong>
                    <span class="badge badge--{{ $transaction->userPackage->isActive() ? 'mint' : 'amber' }} badge--flat">{{ $transaction->userPackage->statusLabel() }}</span>
                </div>
                <div class="mt-3! flex flex-col gap-1 text-sm text-[var(--text)]">
                    <p>@if ($transaction->userPackage->expires_at)Akses sampai <x-local-time :value="$transaction->userPackage->expires_at" format="d/m/Y H:i" />@else Tanpa batas waktu @endif</p>
                    <p>Sisa kuota tryout: <strong>{{ $transaction->userPackage->remainingTryouts() ?? 'Tanpa batas' }}</strong></p>
                </div>
                <a class="btn btn--primary btn--sm mt-4!" href="{{ route('packages.index') }}">Buka paket saya <svg data-icon="arrow-right"></svg></a>
            </div>
        @else
            <div class="flex items-start gap-3 rounded-2xl border border-[var(--pop-amber)] bg-[var(--pop-amber-050)] p-4">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-[var(--surface)] text-lg text-[var(--ink)]" aria-hidden="true"><svg data-icon="lock"></svg></span>
                <div class="min-w-0">
                    <strong class="block text-sm text-[var(--ink)]">Akses belum diaktifkan</strong>
                    <p class="mt-1! text-sm leading-relaxed text-[var(--text)]">Masa aktif baru dimulai setelah pembayaran terverifikasi.</p>
                </div>
            </div>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2" style="--gap:.75rem">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                <dt class="flex items-center gap-2 text-sm text-[var(--text-soft)]"><svg data-icon="clock" class="size-4" aria-hidden="true"></svg> Masa akses</dt>
                <dd class="mt-2! font-[var(--font-display)] text-lg font-extrabold text-[var(--ink)]">{{ $transaction->package_snapshot['duration_days'] === null ? 'Tanpa batas waktu' : ($transaction->package_snapshot['duration_days'] === 365 ? '1 tahun akses' : $transaction->package_snapshot['duration_days'].' hari akses') }}</dd>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                <dt class="flex items-center gap-2 text-sm text-[var(--text-soft)]"><svg data-icon="timer" class="size-4" aria-hidden="true"></svg> Kuota tryout</dt>
                <dd class="mt-2! font-[var(--font-display)] text-lg font-extrabold text-[var(--ink)]">{{ $transaction->package_snapshot['tryout_quota'] === null ? 'Tanpa batas' : $transaction->package_snapshot['tryout_quota'].' sesi tryout' }}</dd>
            </div>
        </dl>

        <div class="border-t border-[var(--border)] pt-5">
            <h3 class="font-[var(--font-display)] text-lg! font-extrabold text-[var(--ink)]">Isi paket</h3>
            <p class="mt-1! text-sm text-[var(--text-soft)]">Buka kategori untuk melihat semua konten yang termasuk.</p>
            <div class="mt-4! flex flex-col gap-3">
                @if ($transaction->package_snapshot['materials'])
                    <details class="transaction-content-group">
                        <summary class="transaction-content-group__header">
                            <span class="transaction-content-group__icon" aria-hidden="true"><svg data-icon="book"></svg></span>
                            <span class="transaction-content-group__copy"><strong>Materi</strong><span>Modul, pembahasan, dan panduan belajar</span></span>
                            <span class="transaction-content-group__count">{{ count($transaction->package_snapshot['materials']) }} materi</span>
                            <svg data-icon="chevron-down" class="transaction-content-group__chevron" aria-hidden="true"></svg>
                        </summary>
                        <ul class="transaction-content-group__list">
                            @foreach ($transaction->package_snapshot['materials'] as $material)
                                <li class="transaction-content-group__item">
                                    <span class="transaction-content-group__item-icon" aria-hidden="true"><svg data-icon="file"></svg></span>
                                    <span class="transaction-content-group__item-title">{{ $material['title'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($transaction->package_snapshot['exams'])
                    <details class="transaction-content-group transaction-content-group--exams">
                        <summary class="transaction-content-group__header">
                            <span class="transaction-content-group__icon" aria-hidden="true"><svg data-icon="list-checks"></svg></span>
                            <span class="transaction-content-group__copy"><strong>Latihan Soal &amp; Tryout</strong><span>Latihan soal dan simulasi ujian</span></span>
                            <span class="transaction-content-group__count">{{ count($transaction->package_snapshot['exams']) }} sesi</span>
                            <svg data-icon="chevron-down" class="transaction-content-group__chevron" aria-hidden="true"></svg>
                        </summary>
                        <ul class="transaction-content-group__list">
                            @foreach ($transaction->package_snapshot['exams'] as $exam)
                                <li class="transaction-content-group__item">
                                    <span class="transaction-content-group__item-icon" aria-hidden="true"><svg data-icon="list-checks"></svg></span>
                                    <span class="transaction-content-group__item-title">{{ $exam['title'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if (($transaction->package_snapshot['tryout_tokens'] ?? 0) > 0)
                    <div class="card card--tight flex flex-col items-start gap-4 p-5! sm:p-6!">
                        <div class="flex flex-col gap-2">
                            <strong class="text-sm text-[var(--ink)]">{{ $transaction->package_snapshot['tryout_tokens'] }} token pilihan tryout</strong>
                            <p class="text-sm leading-relaxed text-soft">Setelah pembayaran terverifikasi, token tersedia di akunmu untuk membuka tryout premium pilihanmu.</p>
                        </div>
                        <a class="btn btn--ghost btn--sm" href="{{ route('tryouts.index') }}">Pilih tryout</a>
                    </div>
                @endif
                @if (! $transaction->package_snapshot['materials'] && ! $transaction->package_snapshot['exams'] && ($transaction->package_snapshot['tryout_tokens'] ?? 0) === 0)
                    <p class="rounded-xl bg-[var(--surface-2)] p-4 text-sm text-[var(--text-soft)]">Daftar konten belum tersedia.</p>
                @endif
            </div>
        </div>
    </div>
</section>
</div>
@endsection
