<section class="card card--pop card--amber member-dashboard__tryout">
    <div class="member-dashboard__tryout-heading">
        <span class="card__icon ico--coral"><svg data-icon="timer" aria-hidden="true"></svg></span>
        <div>
            <span class="badge badge--premium"><svg data-icon="zap" aria-hidden="true"></svg> Simulasi CAT</span>
            <h2 class="card__title">{{ $featuredTryout?->title ?? 'Tryout CAT' }}</h2>
        </div>
    </div>
    <p class="text-sm text-soft">Uji kemampuanmu melalui sesi latihan dan tryout.</p>
    @if ($featuredTryout)
        <dl class="grid grid-cols-2" style="--gap:.75rem">
            <div class="member-dashboard__fact"><dt>Jumlah soal</dt><dd>{{ $featuredTryout->question_count }} soal</dd></div>
            <div class="member-dashboard__fact"><dt>Durasi</dt><dd>{{ $featuredTryout->duration_minutes }} menit</dd></div>
        </dl>
    @endif
    <a class="btn btn--primary" href="{{ route('tryouts.index') }}">Lihat Tryout<span class="btn__icon"><svg data-icon="arrow-right" aria-hidden="true"></svg></span></a>
</section>
