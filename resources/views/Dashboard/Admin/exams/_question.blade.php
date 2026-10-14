@php($available = $question->status === \App\Enums\QuestionStatus::Published && $question->published_at?->lessThanOrEqualTo(now()) && $question->section?->is_active)
<article class="exam-pool-item" data-exam-pool-item data-question-id="{{ $question->id }}" data-section-id="{{ $question->section_id }}">
  <label class="exam-pool-choice"><input type="checkbox" name="question_ids[]" value="{{ $question->id }}" @checked($isSelected)><span><strong>{{ $question->title }}</strong><span class="exam-pool-meta">{{ $question->section?->name ?? 'Bidang dihapus' }} · {{ $question->difficulty->label() }}{{ $question->isWeighted() ? ' · Skor pilihan 1–5' : '' }}{{ $question->video_path ? ' · Video' : '' }}{{ $question->image_path ? ' · Gambar' : '' }}</span><span class="exam-pool-status {{ $available ? 'is-ready' : '' }}">{{ $available ? 'Terbit' : 'Tidak tersedia untuk sesi baru' }} · {{ $question->exams_count }} sesi memakai soal ini</span></span></label>
  <div class="exam-pool-actions">
    <button class="content-icon-button" type="button" data-edit-question="{{ route('admin.questions.show', $question) }}" aria-label="Edit soal {{ $question->title }}" title="Edit soal"><svg data-icon="pencil"></svg></button>
    <button class="content-icon-button exam-pool-archive" type="button" data-archive-question="{{ route('admin.questions.destroy', $question) }}" data-usage-count="{{ $question->exams_count }}" aria-label="Arsipkan soal {{ $question->title }}" title="Arsipkan soal dari bank"><svg data-icon="trash"></svg></button>
  </div>
</article>
