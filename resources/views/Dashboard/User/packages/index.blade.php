@extends('layouts.user.main')
@section('title', 'Paket Belajar — Ganesha Nusa')
@section('content')
<header class="page-head"><span class="eyebrow">Akses Belajar</span><h1 style="margin-top:.5rem">Paket Belajar</h1><p>Pilih konten belajar sesuai kebutuhanmu dan pantau masa aktif paket.</p></header>
@if ($ownedPackages->isNotEmpty())
<section class="question-section">
    <h2 class="card__title" style="margin-bottom:1rem">Paket Saya</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2">
    @foreach ($ownedPackages as $owned)
    <article class="card question-form-stack">
        <div class="flex items-center justify-between"><h3 class="card__title">{{ $owned->name }}</h3><span class="badge badge--{{ $owned->isActive() ? 'mint' : 'amber' }} badge--flat">{{ $owned->statusLabel() }}</span></div>
        <div class="kv">
            <div class="kv__row"><span class="kv__key">Akses leaderboard</span><span class="kv__val">{{ $owned->leaderboard_access->label() }}</span></div>
            <div class="kv__row"><span class="kv__key">Aktif sejak</span><span class="kv__val">{{ $owned->starts_at->translatedFormat('d M Y, H:i') }}</span></div>
            <div class="kv__row"><span class="kv__key">Berlaku sampai</span><span class="kv__val">{{ $owned->expires_at?->translatedFormat('d M Y, H:i') ?? 'Tanpa batas waktu' }}</span></div>
            <div class="kv__row"><span class="kv__key">Kuota tryout tersisa</span><span class="kv__val">{{ $owned->remainingTryouts() ?? 'Tanpa batas' }}{{ $owned->tryout_quota === null ? '' : ' / '.$owned->tryout_quota }}</span></div>
        </div>
        @if ($owned->isActive())
        <details><summary class="text-sm">Lihat konten paket</summary><ul class="price__list">
            @foreach ($owned->materials as $material)<li><a href="{{ route('materials.show', $material) }}">{{ $material->title }}</a></li>@endforeach
            @foreach ($owned->exams as $exam)<li><a href="{{ route($exam->kind === 'quiz' ? 'quizzes.index' : 'tryouts.index') }}">{{ $exam->title }} · {{ $exam->kind === 'quiz' ? 'Latihan' : 'Tryout' }}</a></li>@endforeach
        </ul></details>
        <div class="btn-row"><a class="btn btn--primary btn--sm" href="{{ route('materials.index') }}">Belajar</a><a class="btn btn--ghost btn--sm" href="{{ route('tryouts.index') }}">Tryout</a></div>
        @endif
    </article>
    @endforeach
    </div>
</section>
@endif
<section>
    <h2 class="card__title" style="margin-bottom:1rem">Katalog Paket</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3" style="--gap:1.5rem">
    @forelse ($packages as $package)
    <article class="card price">
        <div><span class="badge badge--premium badge--flat">Paket Belajar</span><h3 class="card__title" style="margin-top:.6rem">{{ $package->name }}</h3><p class="text-sm text-soft" style="margin-top:.5rem">{{ $package->description }}</p></div>
        <div><div class="price__amount">{{ $package->formattedPrice() }}</div><div class="price__period">{{ $package->duration_days === null ? 'Tanpa batas waktu' : 'Akses '.$package->durationLabel().' sejak aktivasi' }}</div></div>
        <ul class="price__list">
            <li><span class="price__check"><svg data-icon="{{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'lock' : 'trophy' }}"></svg></span> {{ $package->leaderboard_access->label() }}</li>
            @if ($package->materials->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->materials->count() }} materi &amp; video premium</li>@endif
            @if ($package->exams->where('kind', 'quiz')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'quiz')->count() }} latihan soal</li>@endif
            <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'tryout')->count() }} paket soal tryout</li>
            <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_quota === null ? 'Kuota tryout tanpa batas' : 'Total kuota '.$package->tryout_quota.' sesi tryout' }}</li>
            @if ($package->price > 0)<li><span class="price__check"><svg data-icon="check"></svg></span> Kunci jawaban &amp; pembahasan</li>@endif
        </ul>
        <details><summary class="text-sm">Lihat daftar konten</summary><ul class="price__list">
            @foreach ($package->materials as $material)<li>{{ $material->title }}</li>@endforeach
            @foreach ($package->exams as $exam)<li>{{ $exam->title }}</li>@endforeach
        </ul></details>
        <div class="price__foot"><a class="btn btn--primary btn--block" href="{{ route('checkout', ['package' => $package->id]) }}">{{ $package->price === 0 ? 'Aktifkan paket' : 'Pilih paket' }} <svg data-icon="arrow-right"></svg></a></div>
    </article>
    @empty
    <div class="card exam-empty"><h3 class="card__title">Paket belum tersedia</h3><p class="text-soft" style="margin-top:.5rem">Kamu tetap bisa belajar lewat materi dan sesi gratis.</p><a class="btn btn--primary" style="margin-top:1rem" href="{{ route('materials.index') }}">Buka materi</a></div>
    @endforelse
    </div>
</section>
<section class="card" style="margin-top:1.5rem"><div class="card__title">Cara kerja paket</div><p class="text-sm text-soft" style="margin-top:.75rem">Harga dan konten pembelian disimpan saat checkout. Akses aktif setelah pembayaran terverifikasi. Kuota dipakai saat memulai tryout premium baru; lanjut sesi yang sama dan latihan tidak memotong kuota. Sesi yang sudah dimulai tetap bisa diselesaikan dan hasilnya tersimpan, termasuk ketika masa aktif paket berakhir.</p></section>
@endsection
