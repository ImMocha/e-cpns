@extends('layouts.user.main')
@section('title', 'Checkout — Ganesha Nusa')
@section('content')
<div class="mx-auto! max-w-[1180px]">
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('packages.index') }}">Paket Belajar</a>
        <span class="crumbs__sep">›</span>
        <span class="crumbs__current">Checkout</span>
    </nav>

    <header class="page-head mb-7!">
        <span class="eyebrow">Selangkah lagi</span>
        <h1 class="mt-2!">Konfirmasi paketmu</h1>
        <p class="max-w-2xl">Cek manfaat paket dan totalnya sebelum melanjutkan pembayaran.</p>
    </header>

    @if ($errors->any())
        <div class="article-errors question-section" role="alert">
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(350px,420px)]">
        <section class="card card--blue card--pad-0" aria-labelledby="checkout-package-title">
            <div class="bg-[var(--primary-050)] p-6 sm:p-8">
                <div class="flex flex-col items-start gap-4 sm:flex-row">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl border-2 border-[var(--ink)] bg-[var(--pop-amber)] text-xl text-[var(--ink)] shadow-[3px_3px_0_var(--ink)]" aria-hidden="true">
                        <svg data-icon="package"></svg>
                    </span>
                    <div class="min-w-0">
                        <span class="text-xs font-extrabold uppercase tracking-[.12em] text-[var(--primary-700)]">Paket pilihanmu</span>
                        <h2 id="checkout-package-title" class="mt-1! font-[var(--font-display)] text-xl! leading-tight font-extrabold text-[var(--ink)] sm:text-[1.75rem]!">{{ $package->name }}</h2>
                        @if ($package->description)
                            <p class="mt-3! max-w-2xl leading-relaxed text-[var(--text)]">{{ $package->description }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <div class="grid sm:grid-cols-3" style="--gap:.75rem">
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <span class="mb-2! flex items-center gap-2 text-sm font-semibold text-[var(--text-soft)]"><svg data-icon="clock" class="size-4"></svg> Masa akses</span>
                        <strong class="block font-[var(--font-display)] text-lg leading-snug font-extrabold text-[var(--ink)]">{{ $package->durationLabel() }}</strong>
                        <span class="mt-1! block text-xs leading-snug text-[var(--text-soft)]">Sejak akses aktif</span>
                    </div>
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <span class="mb-2! flex items-center gap-2 text-sm font-semibold text-[var(--text-soft)]"><svg data-icon="timer" class="size-4"></svg> Jatah tryout</span>
                        <strong class="block font-[var(--font-display)] text-lg leading-snug font-extrabold text-[var(--ink)]">{{ $package->tryout_quota === null ? 'Tanpa batas' : $package->tryout_quota.' sesi' }}</strong>
                        <span class="mt-1! block text-xs leading-snug text-[var(--text-soft)]">Untuk sesi premium baru</span>
                    </div>
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <span class="mb-2! flex items-center gap-2 text-sm font-semibold text-[var(--text-soft)]"><svg data-icon="layers" class="size-4"></svg> Isi paket</span>
                        <strong class="block font-[var(--font-display)] text-lg leading-snug font-extrabold text-[var(--ink)]">{{ $package->materials->count() + $package->exams->count() }} konten</strong>
                        <span class="mt-1! block text-xs leading-snug text-[var(--text-soft)]">Materi &amp; sesi belajar</span>
                    </div>
                </div>

                <div class="mt-8! border-t border-[var(--border)] pt-7!">
                    <div class="mb-5! flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-[var(--font-display)] text-xl! font-extrabold text-[var(--ink)]">Yang kamu dapatkan</h3>
                        <span class="rounded-full bg-[var(--pop-amber-050)] px-3 py-1 text-xs font-bold text-[var(--ink)]">Sudah termasuk dalam paket</span>
                    </div>

                    <p class="text-sm text-soft mb-4!">{{ $package->leaderboard_access->label() }} · Berlaku selama paket aktif.</p>

                    @if ($package->materials->isNotEmpty())
                        <div>
                            <h4 class="mb-3! text-xs font-extrabold uppercase tracking-[.12em] text-[var(--text-soft)]">Materi &amp; video · {{ $package->materials->count() }}</h4>
                            <ul class="grid" style="--gap:.5rem">
                                @foreach ($package->materials as $material)
                                    <li class="flex items-center gap-3 rounded-xl border border-[var(--border)] bg-white px-4 py-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-[var(--primary-100)] text-lg text-[var(--primary-700)]" aria-hidden="true"><svg data-icon="{{ $material->type->icon() }}"></svg></span>
                                        <span class="min-w-0 flex-1 font-semibold leading-snug text-[var(--ink)]">{{ $material->title }}</span>
                                        <span class="hidden text-xs font-bold text-[var(--text-soft)] sm:block">{{ $material->type->shortLabel() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($package->exams->isNotEmpty())
                        <div class="{{ $package->materials->isNotEmpty() ? 'mt-6!' : '' }}">
                            <h4 class="mb-3! text-xs font-extrabold uppercase tracking-[.12em] text-[var(--text-soft)]">Latihan &amp; tryout · {{ $package->exams->count() }}</h4>
                            <ul class="grid" style="--gap:.5rem">
                                @foreach ($package->exams as $exam)
                                    <li class="flex items-center gap-3 rounded-xl border border-[var(--border)] bg-white px-4 py-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-[var(--pop-amber-050)] text-lg text-[var(--ink)]" aria-hidden="true"><svg data-icon="{{ $exam->kind === 'quiz' ? 'pencil' : 'timer' }}"></svg></span>
                                        <span class="min-w-0 flex-1 font-semibold leading-snug text-[var(--ink)]">{{ $exam->title }}</span>
                                        <span class="hidden text-xs font-bold text-[var(--text-soft)] sm:block">{{ $exam->kind === 'quiz' ? 'Latihan' : 'Tryout' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($package->materials->isEmpty() && $package->exams->isEmpty())
                        <p class="text-[var(--text-soft)]">Daftar konten belum tersedia.</p>
                    @endif
                </div>

                <p class="mt-7! flex gap-3 rounded-2xl bg-[var(--primary-050)] p-4 text-sm leading-relaxed text-[var(--text)]">
                    <svg data-icon="info" class="mt-0.5! size-5 shrink-0 text-[var(--primary-700)]"></svg>
                    <span>Jatah tryout terpakai saat kamu memulai sesi premium baru. Melanjutkan sesi yang sama atau mengerjakan latihan tidak mengurangi jatah.</span>
                </p>
            </div>
        </section>

        <section class="card card--amber" aria-labelledby="checkout-summary-title">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[var(--pop-amber-050)] text-xl text-[var(--ink)]" aria-hidden="true"><svg data-icon="clipboard"></svg></span>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.12em] text-[var(--primary-700)]">Langkah terakhir</p>
                    <h2 id="checkout-summary-title" class="font-[var(--font-display)] text-xl! font-extrabold text-[var(--ink)]">Ringkasan pembelian</h2>
                </div>
            </div>

            <div class="mt-6! border-t border-[var(--border)] pt-5!">
                <p class="text-xs font-bold uppercase tracking-[.1em] text-[var(--text-soft)]">Akan aktif untuk</p>
                <p class="mt-2! font-bold text-[var(--ink)]">{{ auth()->user()->name }}</p>
                <p class="break-all text-sm text-[var(--text-soft)]">{{ auth()->user()->email }}</p>
            </div>

            <div class="mt-6! rounded-2xl bg-[var(--ink)] p-5 text-white">
                <div class="flex items-center justify-between gap-3 text-sm text-white/80">
                    <span>Total pembayaran</span>
                    <svg data-icon="credit-card" class="size-5" aria-hidden="true"></svg>
                </div>
                <strong class="mt-3! block font-[var(--font-display)] text-3xl leading-none font-extrabold tracking-tight sm:text-4xl">{{ $package->formattedPrice() }}</strong>
                <span class="mt-2! block text-xs text-white/70">Sekali bayar untuk paket ini</span>
            </div>

            @if ($alreadyOwned)
                <p class="mt-5! text-sm leading-relaxed text-[var(--text)]">Paket ini sudah aktif di akunmu.</p>
                <a class="btn btn--primary btn--block mt-5!" href="{{ route('packages.index') }}">Lihat akses paket <svg data-icon="arrow-right"></svg></a>
            @elseif (! $paymentAvailable)
                <p class="mt-5! text-sm leading-relaxed text-[var(--text)]" role="status">Pembayaran belum tersedia. Silakan hubungi admin untuk mendapatkan paket ini.</p>
            @else
                <p class="mt-5! text-sm leading-relaxed text-[var(--text)]">{{ $package->price === 0 ? 'Paket gratis akan langsung diaktifkan untuk akunmu.' : 'Kamu akan memilih metode pembayaran di Midtrans. Paket aktif otomatis setelah pembayaran terverifikasi.' }}</p>
                <form action="{{ route('checkout.store') }}" method="POST" class="mt-6!">
                    @csrf
                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                    <button class="btn btn--primary btn--block" type="submit">{{ $package->price === 0 ? 'Aktifkan paket gratis' : 'Lanjut ke pembayaran' }} <svg data-icon="arrow-right"></svg></button>
                </form>
            @endif

            <a class="btn btn--ghost btn--block mt-3!" href="{{ route('packages.index') }}"><svg data-icon="arrow-left"></svg> Kembali ke paket</a>
            <p class="mt-5! border-t border-[var(--border)] pt-4! text-xs leading-relaxed text-[var(--text-soft)]">Harga, masa aktif, jatah tryout, dan konten paket dikunci saat pesanan dibuat. Masa aktif dihitung sejak akses diaktifkan.</p>
        </section>
    </div>
</div>
@endsection
