@props(['item', 'kind'])
@php($exam = $item['exam'])
<div class="btn-row flex-wrap exam-actions">
@if ($item['learningLocked'] ?? false)
    <span class="badge badge--amber badge--flat"><svg data-icon="lock"></svg> Soal terkunci · Khusus Premium</span>
    <a class="btn btn--ghost" href="{{ route('packages.index') }}">Lihat paket Premium</a>
@elseif ($item['open'])
    <a class="btn btn--primary" href="{{ $item['open']->attemptUrl() }}">{{ $item['isAdminPreview'] ? 'Lanjutkan pratinjau' : 'Lanjutkan' }}</a>
@elseif ($exam->free_claim_enabled && ! $item['hasAccess'])
    @php($claim = $item['claim'] ?? null)
    @if ($claim?->status === 'pending')
        <span class="badge badge--amber badge--flat" role="status">Menunggu verifikasi admin</span><a class="btn btn--ghost" href="{{ route('exam-claims.create', $exam) }}">Lihat pengajuan</a>
    @elseif ($claim?->status === 'rejected')
        <span class="badge badge--coral badge--flat">Bukti perlu diperbaiki</span><a class="btn btn--primary" href="{{ route('exam-claims.create', $exam) }}">Perbaiki bukti &amp; unlock</a>
    @else
        <a class="btn exam-actions__unlock" href="{{ route('exam-claims.create', $exam) }}"><svg data-icon="lock"></svg> Unlock gratis</a>
    @endif
@elseif ($item['tokenPackage'] ?? null)
    <form class="exam-actions__token-form" method="POST" action="{{ route('tryout-tokens.redeem', [$item['tokenPackage'], $exam]) }}" data-content-confirm data-confirm-title="{{ $item['isTokenRefill'] ? 'Isi ulang percobaan dengan 1 token?' : 'Buka tryout dengan 1 token?' }}" data-confirm-body="Gunakan 1 token dari {{ $item['tokenPackage']->name }} (pembelian #{{ $item['tokenPackage']->id }}) untuk {{ $exam->title }}. Jatah yang siap dipakai: {{ $item['tokenPackage']->tryout_quota === null ? 'tanpa batas' : $item['tokenPackage']->tryout_quota.' percobaan' }}. Riwayat sebelumnya tetap tersimpan." data-confirm-ok="Ya, gunakan 1 token">
        @csrf
        <button class="btn btn--primary" type="submit"><svg data-icon="{{ $item['isTokenRefill'] ? 'timer' : 'lock' }}"></svg> {{ $item['isTokenRefill'] ? 'Isi ulang dengan 1 token' : 'Buka dengan 1 token' }}</button>
    </form>
    <div class="exam-actions__token-info">
        <span>Token dari <strong>{{ $item['tokenPackage']->name }}</strong> · pembelian #{{ $item['tokenPackage']->id }}</span>
        <span class="exam-actions__remaining">{{ $item['tokenPackage']->tryout_quota === null ? 'Percobaan tanpa batas' : $item['tokenPackage']->tryout_quota.' percobaan untuk tryout ini' }}</span>
    </div>
@elseif (! $item['hasAccess'])
    <span class="badge badge--amber badge--flat">Memerlukan paket aktif</span><a class="btn btn--ghost" href="{{ route('packages.index') }}">Lihat paket</a>
@elseif (! $item['topicReady'])
    <span class="badge badge--amber badge--flat"><svg data-icon="lock"></svg> Selesaikan materi topik dahulu</span>
    <a class="btn btn--ghost btn--sm" href="{{ route('materials.index', ['topic_id' => $exam->topic_id]).'#topic-'.$exam->topic_id }}">Lihat materi topik</a>
@elseif (! $item['canStart'])
    <span class="badge badge--amber badge--flat">Kuota tryout habis</span><a class="btn btn--ghost" href="{{ route('packages.index') }}">Beli paket lagi</a>
@elseif ($exam->available_count >= $exam->question_count)
    <form method="POST" action="{{ route(\App\Enums\ExamKind::from($kind)->routePrefix().'.start', $exam->slug) }}"
        @if ($kind === 'tryout')
            id="tryout-start-{{ $exam->id }}"
            data-tryout-start="{{ $exam->title }}"
            data-question-count="{{ $exam->question_count }}"
            data-duration-minutes="{{ $exam->duration_minutes }}"
        @endif
    >
        @csrf
        <button class="btn btn--primary" type="submit" @if ($kind === 'tryout') aria-haspopup="dialog" aria-controls="tryout-ready" @endif>
            {{ $item['isAdminPreview'] ? 'Pratinjau sebagai admin' : 'Mulai '.strtolower(\App\Enums\ExamKind::from($kind)->label()) }} <svg data-icon="arrow-right"></svg>
        </button>
    </form>
@else
    <span class="badge badge--amber badge--flat">Belum cukup soal terbit</span>
@endif
@if ($item['last'])<a class="btn btn--ghost w-full basis-full" href="{{ $item['last']->resultUrl() }}">Hasil terakhir</a>@endif
</div>
