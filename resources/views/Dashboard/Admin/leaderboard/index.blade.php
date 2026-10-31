@extends('layouts.admin.main')
@section('title', 'Kelola Leaderboard — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Leaderboard</span></nav>

<header class="page-head">
    <span class="eyebrow">Pengaturan Peringkat</span>
    <h1 style="margin-top:.5rem">Peserta virtual</h1>
    <p>Isi papan peringkat awal dengan profil contoh dari setiap provinsi. Kamu bisa menghentikannya kapan saja.</p>
</header>

@if (session('success'))
    <div class="card card--tight" role="status" style="margin-bottom:1.25rem;border-color:var(--mint)">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4" style="margin-bottom:1.5rem">
    <section class="card card--pop lg:col-span-2">
        <div class="card__head">
            <div><h2 class="card__title">Tampilan leaderboard</h2><p class="card__sub">Satu tombol untuk menampilkan atau menyembunyikan seluruh peserta virtual.</p></div>
            <span class="badge {{ $simulationEnabled ? 'badge--mint' : 'badge--flat' }}">{{ $simulationEnabled ? 'Aktif' : 'Nonaktif' }}</span>
        </div>
        <p class="text-sm text-soft" style="margin-bottom:1.25rem">Profil virtual memakai skor contoh berupa poin bulat, dengan acuan tryout 550 poin. Skor bot baru bervariasi antara 231–534 poin. Keterangan peserta virtual ditampilkan di atas tabel leaderboard. Saat dimatikan, semuanya hilang dari peringkat lokal dan nasional; nilai serta riwayat peserta nyata tetap tersimpan.</p>
        <form method="POST" action="{{ route('admin.leaderboard-simulation.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="enabled" value="{{ $simulationEnabled ? '0' : '1' }}">
            <button class="btn {{ $simulationEnabled ? 'btn--ghost' : 'btn--primary' }}" type="submit"><svg data-icon="{{ $simulationEnabled ? 'close' : 'check' }}"></svg> {{ $simulationEnabled ? 'Nonaktifkan peserta virtual' : 'Aktifkan peserta virtual' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.leaderboard-simulation.update') }}" style="margin-top:1.5rem">
            @csrf
            @method('PATCH')
            <input type="hidden" name="enabled" value="{{ $simulationEnabled ? '1' : '0' }}">
            <h3 class="card__title">Jumlah bot nasional dan lokal</h3>
            <p class="text-sm text-soft" style="margin:.5rem 0 1rem">Jumlah nasional berlaku untuk seluruh Indonesia. Jumlah lokal berlaku untuk setiap provinsi. Profil yang sama dapat dipakai pada kedua cakupan; peserta nyata tetap ikut dihitung.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="field">
                    <label class="label" for="virtual-minimum">Nasional</label>
                    <input class="input" id="virtual-minimum" name="national_bot_count" type="number" min="0" max="5000" required value="{{ old('national_bot_count', $populationCounts['national']) }}">
                    @error('national_bot_count')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label class="label" for="virtual-maximum">Lokal per provinsi</label>
                    <input class="input" id="virtual-maximum" name="local_bot_count" type="number" min="0" max="100" required value="{{ old('local_bot_count', $populationCounts['local']) }}">
                    @error('local_bot_count')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
            <button class="btn btn--ghost" type="submit" style="margin-top:1rem">Simpan jumlah</button>
        </form>
    </section>
    <section class="card">
        <div class="stat__label">Profil contoh tersedia</div>
        <div class="stat__value" style="margin-top:.5rem">{{ number_format($botCount, 0, ',', '.') }}</div>
        <p class="text-sm text-soft" style="margin-top:.5rem">{{ $botCount ? 'Mencakup '.$provinceCounts->count().' provinsi. Profil akan tetap tersimpan saat fitur dimatikan.' : 'Profil dibuat otomatis untuk setiap provinsi saat fitur pertama kali diaktifkan.' }}</p>
    </section>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <section class="card">
        <div class="card__head"><div><h2 class="card__title">Contoh peserta</h2><p class="card__sub">Nilai contoh dipakai dalam peringkat selama fitur aktif.</p></div></div>
        <div class="table-wrap" style="border:none;box-shadow:none">
            <table class="table"><thead><tr><th>Nama</th><th>Provinsi</th><th class="num">Nilai</th></tr></thead>
                <tbody>
                @forelse ($sampleBots as $bot)
                    <tr><td><strong>{{ $bot->name }}</strong> <span class="badge badge--amber badge--flat">Virtual</span></td><td>{{ $bot->province->value }}</td><td class="num">{{ number_format((float) $bot->score, 0, ',', '.') }} poin</td></tr>
                @empty
                    <tr><td colspan="3" class="text-soft">Profil contoh akan disiapkan saat fitur diaktifkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="card">
        <div class="card__head"><div><h2 class="card__title">Sebaran provinsi</h2><p class="card__sub">Leaderboard lokal memakai provinsi peserta.</p></div></div>
        <div class="table-wrap" style="border:none;box-shadow:none;max-height:360px;overflow:auto">
            <table class="table"><thead><tr><th>Provinsi</th><th class="num">Profil virtual</th></tr></thead>
                <tbody>
                @forelse ($provinceCounts as $provinceCount)
                    <tr><td>{{ $provinceCount->province->value }}</td><td class="num">{{ $provinceCount->total }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-soft">Belum ada profil contoh.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
