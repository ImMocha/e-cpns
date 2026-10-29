@extends('layouts.user.main')
@section('title', 'Paket Belajar — Ganesha Nusa')
@section('content')
<header class="page-head"><span class="eyebrow">Akses Belajar</span><h1 style="margin-top:.5rem">Paket Belajar</h1><p>Pilih konten belajar sesuai kebutuhanmu dan pantau masa aktif paket.</p></header>
@if ($ownedPackages->isNotEmpty())
<section class="question-section owned-packages-carousel" data-package-carousel role="region" aria-roledescription="carousel" aria-label="Paket Saya">
    <div class="package-section-head"><div><h2 class="card__title">Paket Saya</h2><p class="text-sm text-soft">Semua akses belajarmu, dalam satu tempat.</p></div><span class="badge badge--primary badge--flat">{{ $ownedPackages->count() }} paket</span></div>
    <div class="owned-packages-carousel__track" data-carousel-track tabindex="0" aria-label="Geser untuk melihat paket lainnya">
    @foreach ($ownedPackages as $owned)
    <article class="card owned-package" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $ownedPackages->count() }}">
        <header class="owned-package__head">
            <div class="owned-package__identity"><span class="owned-package__icon" aria-hidden="true"><svg data-icon="package"></svg></span><div><span class="eyebrow">Paket Belajar · #{{ $owned->id }}</span><h3 class="owned-package__name">{{ $owned->name }}</h3></div></div>
            <span class="badge badge--{{ $owned->isActive() ? 'mint' : 'amber' }} badge--flat">{{ $owned->statusLabel() }}</span>
        </header>
        <div class="owned-package__body">
            @if ($owned->tier)<x-package-benefits :package="$owned" />@endif
            <dl class="owned-package__stats">
                <div><dt><svg data-icon="clock" aria-hidden="true"></svg> Aktif sejak</dt><dd><x-local-time :value="$owned->starts_at" format="d M Y" /><small><x-local-time :value="$owned->starts_at" format="H:i" /></small></dd></div>
                <div><dt><svg data-icon="calendar" aria-hidden="true"></svg> Berlaku sampai</dt><dd><x-local-time :value="$owned->expires_at" format="d M Y" fallback="Tanpa batas waktu" />@if ($owned->expires_at)<small><x-local-time :value="$owned->expires_at" format="H:i" /></small>@endif</dd></div>
                <div><dt><svg data-icon="timer" aria-hidden="true"></svg> Kuota tryout tersisa</dt><dd>{{ $owned->remainingTryouts() ?? 'Tanpa batas' }}@if ($owned->tryout_quota !== null)<small>dari {{ $owned->tryout_quota }} sesi</small>@else<small>Tanpa batas sesi</small>@endif</dd></div>
                <div><dt><svg data-icon="layers" aria-hidden="true"></svg> Konten belajar</dt><dd>{{ $owned->tier === \App\Enums\PackageTier::Premium ? 'Seluruh konten SKD' : ($owned->materials->count() + $owned->exams->count()).' konten' }}<small>{{ $owned->materials->count() }} materi · {{ $owned->exams->count() }} sesi soal</small></dd></div>
            </dl>
            <div class="owned-package__leaderboard"><span class="owned-package__trophy" aria-hidden="true"><svg data-icon="trophy"></svg></span><div><span>Akses leaderboard</span><strong>{{ $owned->leaderboard_access->label() }}</strong></div></div>
            <div class="owned-package__allowance">
                @if ($owned->tryout_tokens > 0)
                    <strong>{{ $owned->tryout_tokens }} token pilihan tryout{{ $owned->isActive() ? ' tersisa' : '' }}</strong>
                    <p>Gunakan 1 token untuk membuka 1 tryout premium pilihanmu. Kuota percobaan terpisah dari token dan mengikuti status akses paket.</p>
                @elseif ($owned->tryout_quota === null)
                    <strong>Kuota tryout tanpa batas</strong>
                    <p>Percobaan tryout dalam paket ini tidak dibatasi. Akses tetap mengikuti status dan masa aktif paket.</p>
                @else
                    <strong>Tryout dalam paket</strong>
                    <p>{{ $owned->remainingTryouts() }} percobaan tersisa dari {{ $owned->tryout_quota }} sesi. Pilih tryout yang tercantum dalam konten paket saat akses aktif.</p>
                @endif
                @if ($owned->isActive() && $owned->tryout_tokens > 0)
                    <a class="btn btn--primary btn--sm" href="{{ route('tryouts.index') }}">Pilih tryout</a>
                @else
                    <span class="badge badge--{{ $owned->isActive() ? 'primary' : 'amber' }} badge--flat">{{ $owned->isActive() ? 'Sesuai konten paket' : 'Akses '.$owned->statusLabel() }}</span>
                @endif
            </div>
            @if ($owned->tier === \App\Enums\PackageTier::Premium)<p class="text-sm text-soft">Seluruh materi, latihan, dan tryout SKD terbit tersedia selama paket aktif, termasuk konten baru.</p>@endif
        </div>
        @if ($owned->isActive())
        <footer class="owned-package__footer"><a class="btn btn--primary btn--sm" href="{{ route('materials.index') }}"><svg data-icon="book" aria-hidden="true"></svg> Mulai belajar <svg data-icon="arrow-right" aria-hidden="true"></svg></a><a class="btn btn--ghost btn--sm" href="{{ route('tryouts.index') }}"><svg data-icon="timer" aria-hidden="true"></svg> Buka tryout</a></footer>
        @else
        <footer class="owned-package__footer"><span class="owned-package__access-note"><svg data-icon="lock" aria-hidden="true"></svg> Status akses: {{ $owned->statusLabel() }}. Konten hanya untuk ditinjau.</span><a class="btn btn--ghost btn--sm" href="#katalog-paket">Lihat paket</a></footer>
        @endif
    </article>
    @endforeach
    </div>
    <div class="owned-packages-carousel__controls" data-carousel-controls hidden>
        <button class="btn btn--ghost btn--sm" type="button" data-carousel-previous aria-label="Paket sebelumnya"><svg data-icon="chevron-left"></svg></button>
        <span class="owned-packages-carousel__position" data-carousel-position role="status" aria-live="polite" aria-atomic="true"></span>
        <button class="btn btn--ghost btn--sm" type="button" data-carousel-next aria-label="Paket berikutnya"><svg data-icon="chevron-right"></svg></button>
    </div>
</section>
@endif
<section id="katalog-paket">
    <h2 class="card__title" style="margin-bottom:1rem">Katalog Paket</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 price-grid" style="--gap:1.5rem">
    @forelse ($packages as $package)
    <article class="card price">
        <div><span class="badge badge--premium badge--flat">Paket Belajar</span><h3 class="card__title" style="margin-top:.6rem">{{ $package->name }}</h3><p class="text-sm text-soft" style="margin-top:.5rem">{{ $package->description }}</p></div>
        <div><x-package-original-price :package="$package" /><div class="price__amount">{{ $package->formattedPrice() }}</div><div class="price__period">{{ $package->duration_days === null ? 'Tanpa batas waktu' : 'Akses '.$package->durationLabel().' sejak aktivasi' }}</div></div>
        @if ($package->tier)
        <x-package-benefits :package="$package" />
        @else
        <ul class="price__list">
            <li><span class="price__check"><svg data-icon="{{ $package->leaderboard_access === \App\Enums\LeaderboardAccess::None ? 'lock' : 'trophy' }}"></svg></span> {{ $package->leaderboard_access->label() }}</li>
            @if ($package->materials->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->materials->count() }} materi &amp; video premium</li>@endif
            @if ($package->exams->where('kind', 'practice')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'practice')->count() }} latihan soal</li>@endif
            @if ($package->exams->where('kind', 'quiz')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'quiz')->count() }} latihan topik</li>@endif
            @if ($package->tryout_tokens === 0 || $package->exams->where('kind', 'tryout')->isNotEmpty())<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->exams->where('kind', 'tryout')->count() }} paket soal tryout</li>@endif
            @if ($package->tryout_tokens > 0)<li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_tokens }} token untuk {{ $package->tryout_tokens }} tryout pilihanmu</li>@endif
            <li><span class="price__check"><svg data-icon="check"></svg></span> {{ $package->tryout_quota === null ? 'Kuota tryout tanpa batas' : 'Total kuota '.$package->tryout_quota.' sesi tryout' }}</li>
            @if ($package->price > 0)<li><span class="price__check"><svg data-icon="check"></svg></span> Kunci jawaban &amp; pembahasan</li>@endif
        </ul>
        @endif
        <div class="price__foot">
            @if ($package->hasAvailableContent())
                <a class="btn btn--primary btn--block" href="{{ route('checkout', ['package' => $package->id]) }}">{{ $ownedPackages->contains('package_id', $package->id) ? 'Beli lagi' : ($package->price === 0 ? 'Aktifkan paket' : 'Pilih paket') }} <svg data-icon="arrow-right"></svg></a>
            @else
                <span class="badge badge--amber badge--flat">Konten belum tersedia</span>
                <p class="text-sm text-soft" style="margin-top:.75rem">Paket belum bisa dibeli. Silakan pilih paket lain atau hubungi admin.</p>
            @endif
        </div>
    </article>
    @empty
    <div class="card exam-empty"><h3 class="card__title">Paket belum tersedia</h3><p class="text-soft" style="margin-top:.5rem">Lihat katalog materi dan tryout sambil menunggu paket tersedia.</p><a class="btn btn--primary" style="margin-top:1rem" href="{{ route('materials.index') }}">Buka materi</a></div>
    @endforelse
    </div>
</section>
<section class="card" style="margin-top:1.5rem"><div class="card__title">Cara kerja paket</div><p class="text-sm text-soft" style="margin-top:.75rem">Kamu boleh membeli paket yang sama maupun paket lain berulang kali. Setiap pembelian menyimpan ketentuan, token, kuota percobaan, dan masa aktifnya sendiri. Akses aktif setelah pembayaran terverifikasi. Jika paketmu berisi token, gunakan 1 token untuk membuka 1 tryout premium pilihanmu atau mengisi ulang tryout yang percobaannya sudah habis, menggunakan kuota dari pembelian baru. Pilihan yang dikonfirmasi tidak bisa ditukar. Kuota percobaan dipakai saat memulai tryout premium baru; lanjut sesi yang sama dan latihan tidak memotong kuota. Riwayat hasil tryout sebelumnya tetap tersimpan.</p></section>
@endsection
