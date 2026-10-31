@if ($previousMaterial || $nextMaterial)
  <nav class="topic-study__navigation" aria-label="Navigasi antar materi">
    @if ($previousMaterial)
      <a class="card topic-study__step" href="{{ route('materials.topic', ['topic' => $material->topic_id, 'material' => $previousMaterial->id, 'show_drafts' => $showDrafts ? 1 : null]) }}" rel="prev">
        <span class="topic-study__step-icon" aria-hidden="true"><svg data-icon="arrow-left"></svg></span>
        <span><small>Materi sebelumnya</small><strong>{{ $previousMaterial->title }}</strong></span>
      </a>
    @endif
    @if ($nextMaterial)
      <a class="card topic-study__step topic-study__step--next" href="{{ route('materials.topic', ['topic' => $material->topic_id, 'material' => $nextMaterial->id, 'show_drafts' => $showDrafts ? 1 : null]) }}" rel="next">
        <span><small>Materi berikutnya</small><strong>{{ $nextMaterial->title }}</strong></span>
        <span class="topic-study__step-icon" aria-hidden="true"><svg data-icon="arrow-right"></svg></span>
      </a>
    @endif
  </nav>
@endif

@if ($isLastMaterial)
  <section class="card topic-study__finish" aria-labelledby="topic-finish-title">
    <header class="topic-study__finish-heading">
      <span class="eyebrow">Lanjut belajar</span>
      <h2 id="topic-finish-title">Materi terakhir dalam topik ini</h2>
      <p class="text-soft">Setelah mempelajari {{ $material->topic->name }}, kamu bisa menguji pemahaman atau melanjutkan ke topik berikutnya.</p>
    </header>
    @if ($progress && !$progress->completed_at)
      <form class="topic-study__completion" method="POST" action="{{ route('materials.progress.store', $material) }}">
        @csrf
        <p class="text-sm text-soft">Sudah selesai mempelajari materi ini? Catat progres belajarmu terlebih dahulu.</p>
        <button class="btn btn--outline btn--sm" type="submit"><svg data-icon="check-circle"></svg> Tandai selesai</button>
      </form>
    @endif
    <div class="topic-study__choices">
      <section class="topic-study__choice" aria-label="Pilihan mengerjakan soal">
        <span class="topic-study__step-icon" aria-hidden="true"><svg data-icon="list-checks"></svg></span>
        <h3>Uji pemahamanmu</h3>
        <p class="text-sm text-soft">Kerjakan soal yang berkaitan dengan topik yang baru dipelajari.</p>
        @if ($topicQuizzes->isNotEmpty())
          <details class="topic-study__practice">
            <summary class="btn btn--primary">Kerjakan soal topik <svg data-icon="chevron-down" aria-hidden="true"></svg></summary>
            <x-topic-quizzes :topic="$material->topic" :banks="$topicQuizzes" />
          </details>
        @else
          <p class="topic-study__unavailable" role="status">Soal untuk topik ini belum tersedia.</p>
        @endif
      </section>
      <section class="topic-study__choice" aria-label="Pilihan melanjutkan topik">
        <span class="topic-study__step-icon" aria-hidden="true"><svg data-icon="book"></svg></span>
        @if ($nextTopic)
          <h3>{{ $nextTopic->name }}</h3>
          <p class="text-sm text-soft">Lanjutkan belajar ke topik berikutnya dalam bidang {{ $material->section->name }}.</p>
          <a class="btn btn--outline" href="{{ route('materials.topic', ['topic' => $nextTopic->id, 'show_drafts' => $showDrafts ? 1 : null]) }}" rel="next">Lanjut ke topik berikutnya <svg data-icon="arrow-right" aria-hidden="true"></svg></a>
        @else
          <h3>Ini topik terakhir di bidang ini</h3>
          <p class="text-sm text-soft">Kamu bisa mengulang materi atau memilih topik dari bidang lain.</p>
          <a class="btn btn--outline" href="{{ route('materials.index') }}">Pilih topik lain <svg data-icon="arrow-right" aria-hidden="true"></svg></a>
        @endif
      </section>
    </div>
  </section>
@endif
