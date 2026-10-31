@extends('layouts.user.main')
@section('title', 'Leaderboard — Ganesha Nusa')
@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="crumbs__sep"><svg data-icon="chevron-right"></svg></span><span class="crumbs__current">Leaderboard</span></nav>
<header class="page-head">
    <span class="eyebrow">Papan Peringkat</span>
    <h1 style="margin-top:.5rem">Seberapa jauh kamu melangkah?</h1>
    <p>Bandingkan nilai tryout terbaikmu dengan peserta di provinsimu atau seluruh Indonesia.</p>
</header>

@if (! $hasAccess)
    <section class="card question-form-stack">
        <span class="badge badge--amber badge--flat"><svg data-icon="lock"></svg> Akses paket diperlukan</span>
        <h2 class="card__title">Buka leaderboard lokal atau nasional</h2>
        <p class="text-soft">Pilih paket yang mencakup leaderboard lokal untuk melihat peringkat di provinsi tempat tinggalmu. Paket dengan akses nasional juga membuka peringkat seluruh Indonesia.</p>
        <div><a class="btn btn--primary" href="{{ route('packages.index') }}">Lihat paket belajar <svg data-icon="arrow-right"></svg></a></div>
    </section>
@else
    <nav class="pills" aria-label="Cakupan leaderboard" style="margin-bottom:1.5rem">
        <a class="pill{{ $scope === 'local' ? ' is-active' : '' }}" href="{{ route('leaderboard.index', ['scope' => 'local']) }}" @if ($scope === 'local') aria-current="page" @endif>Lokal{{ $province ? ' · '.$province->value : '' }}</a>
        @if ($access === \App\Enums\LeaderboardAccess::National)
            <a class="pill{{ $scope === 'national' ? ' is-active' : '' }}" href="{{ route('leaderboard.index', ['scope' => 'national']) }}" @if ($scope === 'national') aria-current="page" @endif>Nasional</a>
        @else
            <a class="pill" href="{{ route('packages.index') }}"><svg data-icon="lock"></svg> Buka akses nasional</a>
        @endif
    </nav>

    @if ($needsProvince)
        <section class="card question-form-stack">
            <h2 class="card__title">Lengkapi provinsi tempat tinggalmu</h2>
            <p class="text-soft">Kami membutuhkan provinsi hanya untuk menentukan leaderboard lokalmu. Pilih provinsi di profil, lalu kembali untuk melihat peringkat.</p>
            <div><a class="btn btn--primary" href="{{ route('profile.edit') }}">Lengkapi profil <svg data-icon="arrow-right"></svg></a></div>
        </section>
    @else
        <section class="grid grid-cols-1 md:grid-cols-3" style="--gap:1rem;margin-bottom:1.5rem">
            <article class="card card--pop">
                <div class="stat__label">Peringkatmu · {{ $scope === 'local' ? $province->value : 'Nasional' }}</div>
                <div class="stat__value" style="margin-top:.5rem">{{ $ownRank ? '#'.$ownRank->position : '—' }}</div>
                <p class="text-sm text-soft" style="margin-top:.5rem">{{ $ownRank ? 'Dari '.$totalParticipants.' entri peringkat'.($simulatedCount ? ', termasuk '.$simulatedCount.' virtual.' : ' peserta.') : 'Selesaikan satu tryout untuk mendapatkan peringkat.' }}</p>
            </article>
            <article class="card">
                <div class="stat__label">Nilai terbaikmu</div>
                <div class="stat__value" style="margin-top:.5rem">{{ $ownRank ? number_format((float) $ownRank->score, 0, ',', '.') : ($maximumScore !== null ? '0' : '—') }}{{ $maximumScore !== null ? ' / '.$maximumScore : '' }}</div>
                <p class="text-sm text-soft" style="margin-top:.5rem">{{ $maximumScore !== null ? 'Mengikuti '.$referenceTryout->title.'.' : ($ownRank ? 'Dari '.$ownRank->max_score.' poin maksimal.' : 'Nilai tryoutmu akan tampil di sini.') }}</p>
            </article>
            <article class="card">
                <div class="stat__label">Entri dalam peringkat</div>
                <div class="stat__value" style="margin-top:.5rem">{{ number_format($totalParticipants, 0, ',', '.') }}</div>
                <p class="text-sm text-soft" style="margin-top:.5rem">{{ $simulatedCount ? $simulatedCount.' peserta virtual · ' : '' }}{{ $scope === 'local' ? 'Provinsi '.$province->value : 'Seluruh Indonesia' }}</p>
            </article>
        </section>

        <section class="card">
            <div class="card__head"><div><h2 class="card__title">{{ $scope === 'local' ? 'Leaderboard '.$province->value : 'Leaderboard Nasional' }}</h2><p class="card__sub">{{ $referenceTryout ? 'Acuan: '.$referenceTryout->title.' · '.$maximumScore.' poin maksimal' : 'Gabungan seluruh tryout · satu nilai terbaik per peserta' }}</p></div><span class="badge badge--primary badge--flat"><svg data-icon="trophy"></svg> {{ $scope === 'local' ? 'Lokal' : 'Nasional' }}</span></div>
            @if ($referenceTryout)
                <p class="text-sm text-soft" style="margin-bottom:1rem">Skor maksimal otomatis mengikuti tryout aktif dengan poin maksimal tertinggi. Peringkat memakai poin terbaik dari tryout aktif dengan skor maksimal yang sama. Jika hasilmu hanya dari tryout lama atau sesi yang lebih pendek, nilai awal di sini 0 / {{ $maximumScore }} sampai tryout acuan diselesaikan. Riwayat hasil lama tetap tersimpan. Nilai sama mendapat peringkat sama; nilai latihan tidak masuk leaderboard.</p>
            @else
            <p class="text-sm text-soft" style="margin-bottom:1rem">Peringkat memakai skor poin tertinggi dari tryout yang selesai. Skor ditampilkan sesuai poin yang diperoleh dan skor maksimal sesi. Nilai sama mendapat peringkat sama, misalnya 1, 1, 3. Nilai latihan tidak masuk leaderboard.</p>
            @endif
            @if ($simulatedCount > 0)
                <p class="text-xs text-soft" style="margin-bottom:1rem">Peserta virtual memakai nilai contoh berupa poin bulat dengan acuan tryout {{ $maximumScore ?? 550 }} poin dan ikut dihitung dalam peringkat selama fitur aktif.</p>
            @endif
            <p class="text-sm text-soft" style="margin-bottom:1rem">Daftar menampilkan maksimal {{ number_format($displayLimit, 0, ',', '.') }} peserta. Peringkatmu tetap dihitung dari seluruh peserta dan ditampilkan di atas, termasuk ketika berada di luar daftar.</p>
            <div class="table-wrap" style="border:none;box-shadow:none">
                <table class="table">
                    <thead><tr><th class="num">Peringkat</th><th>Peserta</th><th>Provinsi</th><th class="num">Skor (poin)</th></tr></thead>
                    <tbody>
                    @forelse ($participants as $entry)
                        <tr @if ($entry->participant_type === 'real' && (int) $entry->id === auth()->id()) class="bg-[var(--primary-050)]" @endif>
                            <td class="num"><span class="lb-rank leaderboard-rank{{ (int) $entry->position <= 3 ? ' lb-rank--'.$entry->position : '' }}" aria-label="Peringkat {{ $entry->position }}">{{ $entry->position }}</span></td>
                            <td><strong>{{ $entry->name }}</strong>@if ($entry->participant_type === 'real' && (int) $entry->id === auth()->id()) <span class="badge badge--mint badge--flat">Kamu</span>@endif</td>
                            <td>{{ $entry->province ?? 'Belum diisi' }}</td>
                            <td class="num"><strong>{{ rtrim(rtrim(number_format((float) $entry->score, 2, ',', '.'), '0'), ',') }}{{ $entry->max_score !== null ? ' / '.$entry->max_score : ' poin' }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-soft">Belum ada peserta yang menyelesaikan tryout{{ $scope === 'local' ? ' di provinsi ini' : '' }}. Jadilah yang pertama!</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($participants->hasPages())<div class="exam-pager"><x-pager :paginator="$participants" /></div>@endif
            @unless ($ownRank)<div class="btn-row" style="margin-top:1rem"><a class="btn btn--primary" href="{{ route('tryouts.index') }}">Mulai tryout <svg data-icon="arrow-right"></svg></a></div>@endunless
        </section>
    @endif
@endif
@endsection
