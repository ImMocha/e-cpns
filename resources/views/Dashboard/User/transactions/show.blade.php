@extends('layouts.user.main')
@section('title', 'Detail Transaksi — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('transactions.index') }}">Transaksi Saya</a><span class="crumbs__sep">›</span><span class="crumbs__current">Detail Transaksi</span></nav>
<header class="page-head"><span class="eyebrow">Pembelian Paket</span><h1 style="margin-top:.5rem">{{ $transaction->package_snapshot['name'] }}</h1><p class="break-all">{{ $transaction->order_id }}</p></header>
@if ($errors->any() || $paymentError)<div class="article-errors question-section" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach @if ($paymentError)<li>{{ $paymentError }}</li>@endif</ul></div>@endif
<div class="grid items-start gap-6 lg:grid-cols-2">
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
                    <p>{{ $transaction->userPackage->expires_at ? 'Akses sampai '.$transaction->userPackage->expires_at->format('d/m/Y H:i') : 'Tanpa batas waktu' }}</p>
                    <p>Sisa kuota tryout: <strong>{{ $transaction->userPackage->remainingTryouts() ?? 'Tanpa batas' }}</strong></p>
                </div>
                <a class="btn btn--primary btn--sm mt-4!" href="{{ route('packages.index') }}">Buka paket saya <svg data-icon="arrow-right"></svg></a>
            </div>
        @else
            <div class="flex items-start gap-3 rounded-2xl border border-[var(--pop-amber)] bg-[var(--pop-amber-050)] p-4">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-lg text-[var(--ink)]" aria-hidden="true"><svg data-icon="lock"></svg></span>
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
                    <details class="group overflow-hidden rounded-2xl border border-[var(--border)]">
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 transition-colors hover:bg-[var(--primary-050)] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[var(--primary)] [&::-webkit-details-marker]:hidden">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[var(--primary-100)] text-lg text-[var(--primary-700)]" aria-hidden="true"><svg data-icon="book"></svg></span>
                            <span class="min-w-0 flex-1"><strong class="block text-sm text-[var(--ink)]">Materi</strong><span class="text-xs text-[var(--text-soft)]">Modul, pembahasan, dan panduan belajar</span></span>
                            <span class="badge badge--primary badge--flat shrink-0">{{ count($transaction->package_snapshot['materials']) }}</span>
                            <svg data-icon="chevron-down" class="size-4 shrink-0 text-[var(--text-soft)] group-open:rotate-180" aria-hidden="true"></svg>
                        </summary>
                        <ul class="border-t border-[var(--border)] px-4">
                            @foreach ($transaction->package_snapshot['materials'] as $material)
                                <li class="flex items-start gap-3 border-b border-[var(--border)] py-3 last:border-b-0">
                                    <svg data-icon="file" class="mt-0.5! size-4 shrink-0 text-[var(--primary-700)]" aria-hidden="true"></svg>
                                    <span class="min-w-0 text-sm leading-relaxed break-words text-[var(--text)]">{{ $material['title'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if ($transaction->package_snapshot['exams'])
                    <details class="group overflow-hidden rounded-2xl border border-[var(--border)]">
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 transition-colors hover:bg-[var(--primary-050)] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[var(--primary)] [&::-webkit-details-marker]:hidden">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[var(--pop-amber-050)] text-lg text-[var(--ink)]" aria-hidden="true"><svg data-icon="list-checks"></svg></span>
                            <span class="min-w-0 flex-1"><strong class="block text-sm text-[var(--ink)]">Latihan &amp; tryout</strong><span class="text-xs text-[var(--text-soft)]">Latihan soal dan simulasi ujian</span></span>
                            <span class="badge badge--amber badge--flat shrink-0">{{ count($transaction->package_snapshot['exams']) }}</span>
                            <svg data-icon="chevron-down" class="size-4 shrink-0 text-[var(--text-soft)] group-open:rotate-180" aria-hidden="true"></svg>
                        </summary>
                        <ul class="border-t border-[var(--border)] px-4">
                            @foreach ($transaction->package_snapshot['exams'] as $exam)
                                <li class="flex items-start gap-3 border-b border-[var(--border)] py-3 last:border-b-0">
                                    <svg data-icon="list-checks" class="mt-0.5! size-4 shrink-0 text-[var(--primary-700)]" aria-hidden="true"></svg>
                                    <span class="min-w-0 text-sm leading-relaxed break-words text-[var(--text)]">{{ $exam['title'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @if (! $transaction->package_snapshot['materials'] && ! $transaction->package_snapshot['exams'])
                    <p class="rounded-xl bg-[var(--surface-2)] p-4 text-sm text-[var(--text-soft)]">Daftar konten belum tersedia.</p>
                @endif
            </div>
        </div>
    </div>
</section>
</div>
@endsection
