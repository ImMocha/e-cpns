@props(['item', 'kind'])
@php($exam = $item['exam'])
<div class="btn-row flex-wrap">
@if ($item['open'])
    <a class="btn btn--primary" href="{{ $item['open']->attemptUrl() }}">{{ $item['isAdminPreview'] ? 'Lanjutkan pratinjau' : 'Lanjutkan' }}</a>
@elseif (! $item['hasAccess'])
    <span class="badge badge--amber badge--flat">Memerlukan paket aktif</span><a class="btn btn--ghost" href="{{ route('packages.index') }}">Lihat paket</a>
@elseif (! $item['topicReady'])
    <span class="badge badge--amber badge--flat"><svg data-icon="lock"></svg> Selesaikan materi topik dahulu</span>
    <a class="btn btn--ghost btn--sm" href="{{ route('materials.index', ['topic_id' => $exam->topic_id]).'#topic-'.$exam->topic_id }}">Lihat materi topik</a>
@elseif (! $item['canStart'])
    <span class="badge badge--amber badge--flat">Kuota tryout habis</span><a class="btn btn--ghost" href="{{ route('packages.index') }}">Lihat paket</a>
@elseif ($exam->available_count >= $exam->question_count)
    <form method="POST" action="{{ route($kind === 'quiz' ? 'quizzes.start' : 'tryouts.start', $exam->slug) }}">@csrf<button class="btn btn--primary">{{ $item['isAdminPreview'] ? 'Pratinjau sebagai admin' : 'Mulai '.($kind === 'quiz' ? 'latihan' : 'tryout') }} <svg data-icon="arrow-right"></svg></button></form>
@else
    <span class="badge badge--amber badge--flat">Belum cukup soal terbit</span>
@endif
@if ($item['last'])<a class="btn btn--ghost" href="{{ $item['last']->resultUrl() }}">Hasil terakhir</a>@endif
</div>
