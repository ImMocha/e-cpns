@extends('layouts.user.main')
@section('title', 'Leaderboard — Ganesha Nusa')

@push('head')
    <style>
        .leaderboard-title {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .leaderboard-help-toggle {
            display: inline-grid;
            place-items: center;
            flex: none;
            width: 2rem;
            height: 2rem;
            border: 1px solid var(--primary-300);
            border-radius: 50%;
            background: var(--surface-2);
            color: var(--ink);
            font-weight: 800;
            cursor: pointer;
        }

        .leaderboard-help-toggle:hover,
        .leaderboard-help-toggle[aria-expanded="true"] {
            background: var(--primary-050);
        }

        .leaderboard-help-toggle:focus-visible {
            outline: 2px solid var(--primary-600);
            outline-offset: 3px;
        }

        .leaderboard-help-panel {
            margin-bottom: 1rem;
            padding: .9rem 1rem;
            border: 1px solid var(--primary-300);
            border-radius: var(--r-sm);
            background: var(--surface-2);
            line-height: 1.6;
        }

        .leaderboard-help-panel h3 {
            margin-bottom: .45rem;
            color: var(--ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .leaderboard-help-panel ul {
            max-width: 90ch;
            padding-inline-start: 1.15rem;
            list-style: disc;
        }

        .leaderboard-help-panel li + li {
            margin-top: .35rem;
        }

        @media (pointer: coarse) {
            .leaderboard-help-toggle {
                width: 2.75rem;
                height: 2.75rem;
            }
        }
    </style>
@endpush

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
            <div data-leaderboard-help>
                <div class="card__head">
                    <div>
                        <div class="leaderboard-title">
                            <h2 class="card__title">{{ $scope === 'local' ? 'Leaderboard '.$province->value : 'Leaderboard Nasional' }}</h2>
                            <button type="button" class="leaderboard-help-toggle" aria-label="Cara kerja leaderboard" aria-expanded="false" aria-controls="leaderboard-help-panel">
                                <span aria-hidden="true">?</span>
                            </button>
                        </div>
                        <p class="card__sub">{{ $referenceTryout ? 'Acuan: '.$referenceTryout->title.' · '.$maximumScore.' poin maksimal' : 'Gabungan seluruh tryout · satu nilai terbaik per peserta' }}</p>
                    </div>
                    <span class="badge badge--primary badge--flat"><svg data-icon="trophy"></svg> {{ $scope === 'local' ? 'Lokal' : 'Nasional' }}</span>
                </div>
                <div id="leaderboard-help-panel" class="leaderboard-help-panel text-sm text-soft" hidden>
                    <h3>Cara kerja leaderboard</h3>
                    <ul>
                        @if ($referenceTryout)
                            <li><strong>Skor acuan.</strong> Sistem memilih skor maksimal tertinggi dari tryout aktif. Nilai terbaikmu dari tryout aktif dengan maksimum yang sama akan dihitung. Jika baru punya hasil dari skala lain atau tryout yang sudah tidak aktif, nilaimu tampil 0 / {{ $maximumScore }}. Hasil lama tetap ada di riwayat.</li>
                        @else
                            <li><strong>Skor terbaik.</strong> Sistem mengambil nilai tertinggimu dari tryout yang selesai dan menampilkannya sesuai skor maksimal sesi.</li>
                        @endif
                        <li><strong>Urutan peringkat.</strong> Skor sama berbagi peringkat, misalnya 1, 1, 2, 3. Nilai latihan tidak dihitung.</li>
                        @if ($simulatedCount > 0)
                            <li><strong>Simulasi.</strong> Peserta virtual memakai nilai contoh pada skala {{ $maximumScore ?? 550 }} poin dan ikut dihitung selama simulasi aktif.</li>
                        @endif
                        <li><strong>Cakupan daftar.</strong> Daftar menampilkan maksimal {{ number_format($displayLimit, 0, ',', '.') }} peserta. Peringkatmu tetap dihitung dari seluruh peserta dalam cakupan yang dipilih.</li>
                    </ul>
                </div>
            </div>
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

@push('scripts')
    <script>
        (() => {
            const help = document.querySelector('[data-leaderboard-help]');
            if (!help) {
                return;
            }

            const toggle = help.querySelector('.leaderboard-help-toggle');
            const panel = document.getElementById(toggle.getAttribute('aria-controls'));
            let pinned = false;

            const show = (open) => {
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', String(open));
            };

            const close = () => {
                pinned = false;
                show(false);
            };

            toggle.addEventListener('pointerenter', (event) => {
                if (event.pointerType === 'mouse') {
                    show(true);
                }
            });

            help.addEventListener('pointerleave', (event) => {
                if (event.pointerType === 'mouse' && !pinned) {
                    show(false);
                }
            });

            toggle.addEventListener('click', () => {
                pinned = !pinned;
                show(pinned);
            });

            document.addEventListener('click', (event) => {
                if (!panel.hidden && !help.contains(event.target)) {
                    close();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !panel.hidden) {
                    close();
                }
            });
        })();
    </script>
@endpush
