@props(['topic', 'banks'])
@if ($banks->isNotEmpty())
<section class="topic-quizzes" aria-label="Latihan Soal topik {{ $topic->name }}">
    <header class="topic-quizzes__header">
        <span class="topic-quizzes__icon" aria-hidden="true"><svg data-icon="list-checks"></svg></span>
        <div class="topic-quizzes__intro">
            <h4>Latihan Soal Topik</h4>
            <p>Uji pemahamanmu lewat soal yang berkaitan dengan materi di topik ini.</p>
        </div>
        <span class="badge badge--primary badge--flat topic-quizzes__count">{{ $banks->count() }} latihan</span>
    </header>

    <div class="topic-quizzes__list">
        @foreach ($banks as $item)
            @php($exam = $item['exam'])
            <article class="topic-quiz">
                <div class="topic-quiz__header">
                    <div class="topic-quiz__intro">
                        <h5>{{ $exam->title }}</h5>
                        @if ($exam->description)<p>{{ $exam->description }}</p>@endif
                    </div>
                    <span class="badge badge--{{ $exam->access_level === \App\Enums\MaterialAccess::Premium ? 'violet' : 'mint' }} badge--flat">{{ $exam->access_level->label() }}</span>
                </div>

                <div class="topic-quiz__meta">
                    <span><svg data-icon="clipboard" aria-hidden="true"></svg> {{ $exam->question_count }} soal</span>
                    <span><svg data-icon="clock" aria-hidden="true"></svg> {{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</span>
                    @unless ($exam->requires_topic_completion)<span class="topic-quiz__anytime"><svg data-icon="unlock" aria-hidden="true"></svg> Bisa dikerjakan kapan saja</span>@endunless
                </div>

                @if ($exam->requires_topic_completion)
                    <div class="topic-quiz__requirement">
                        <svg data-icon="{{ $item['isAdminPreview'] ? 'eye' : ($item['topicReady'] ? 'check-circle' : 'lock') }}" aria-hidden="true"></svg>
                        <p>{{ $item['isAdminPreview'] ? 'Pratinjau admin: bisa dibuka tanpa menyelesaikan materi.' : ($item['topicReady'] ? 'Semua materi selesai. Latihan Soal terbuka.' : 'Selesaikan semua materi untuk membuka latihan.') }}</p>
                        <span>{{ $exam->topic->completed_learning_materials_count }}/{{ $exam->topic->learning_materials_count }} materi selesai</span>
                    </div>
                @endif

                <div class="topic-quiz__footer">
                    @if ($item['last'])
                        <div class="topic-quiz__result"><svg data-icon="trending-up" aria-hidden="true"></svg><span>Nilai terakhir <strong>{{ $item['last']->score }} / {{ $item['last']->max_score }}</strong></span></div>
                    @endif
                    <x-exam-actions :item="$item" kind="quiz" />
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
