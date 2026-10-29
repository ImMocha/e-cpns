<div class="exam-source-intro">
    <div><h3>Sumber soal</h3><p>Buka sesi untuk melihat soalnya. Soal yang dipakai di beberapa sesi cukup dipilih sekali.</p></div>
    <div class="exam-source-tools"><button class="btn btn--ghost btn--sm" type="button" data-exam-sources-expand>Buka semua</button><button class="btn btn--ghost btn--sm" type="button" data-exam-sources-collapse>Tutup semua</button></div>
</div>
<div class="exam-question-pool exam-source-pool" data-exam-question-pool>
    @foreach ($questionGroups as $sourceKey => $sourceGroup)
        @php
            $source = $sourceGroup['exam'];
            $sourceKind = $source ? \App\Enums\ExamKind::from($source->kind) : null;
            $sourceTitle = $source?->title ?? 'Belum masuk sesi';
            $sourceSelectedCount = $sourceGroup['questions']->whereIn('id', $questionOrder)->count();
        @endphp
        <details class="exam-source" data-exam-source="{{ $sourceKey }}" data-source-title="{{ $sourceTitle }} {{ $sourceKind?->label() ?? 'Bank soal' }}" @if($sourceGroup['questions']->isEmpty()) hidden @endif>
            <summary class="exam-source__summary">
                <span class="exam-source__icon" aria-hidden="true"><svg data-icon="{{ $sourceKind === \App\Enums\ExamKind::Tryout ? 'timer' : ($sourceKind ? 'list-checks' : 'layers') }}"></svg></span>
                <span class="exam-source__heading"><span class="exam-source__type">{{ $sourceKind?->label() ?? 'Bank soal' }}@if($source && $source->id === $exam->id) · Sesi ini @endif @if($source?->status === \App\Enums\QuestionStatus::Draft) · Draft @endif</span><strong>{{ $sourceTitle }}</strong><span class="exam-source__count" data-source-count>{{ $sourceGroup['questions']->count() }} soal</span></span>
                <span class="exam-source__selected" data-source-selected @if(!$sourceSelectedCount) hidden @endif>{{ $sourceSelectedCount }} dipilih</span>
                <svg class="exam-source__chevron" data-icon="chevron-down" aria-hidden="true"></svg>
            </summary>
            <div class="exam-source__items" data-exam-source-items>
                @foreach ($sourceGroup['questions'] as $question)
                    @include('Dashboard.Admin.exams._question', ['question' => $question, 'isSelected' => in_array($question->id, $questionOrder)])
                @endforeach
            </div>
        </details>
    @endforeach
</div>
