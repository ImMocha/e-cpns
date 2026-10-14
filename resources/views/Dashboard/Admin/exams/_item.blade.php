@php($examRoute = $exam->kind === 'quiz' ? 'admin.materials.quizzes' : 'admin.exams')
<article class="content-exam-row">
  <span class="content-exam-icon {{ $exam->kind === 'tryout' ? 'is-tryout' : '' }}"><svg data-icon="{{ $exam->kind === 'quiz' ? 'list-checks' : 'timer' }}"></svg></span>
  <div class="content-exam-info">
    <a class="content-material-title" href="{{ route($examRoute.'.edit', $exam) }}">{{ $exam->title }}</a>
    @if($exam->kind === 'quiz')<div class="content-material-path">{{ $exam->topic ? $exam->topic->section?->name.' / '.$exam->topic->name : 'Latihan umum · belum terhubung ke topik' }}</div>@endif
    <div class="content-material-meta"><span>{{ $exam->question_count }} soal per pengerjaan</span><span>{{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</span><span>{{ $exam->access_level->label() }}</span>@if($exam->kind === 'quiz')<span>{{ $exam->requires_topic_completion ? 'Setelah semua materi selesai' : 'Bisa dikerjakan kapan saja' }}</span>@endif</div>
    @if($exam->available_count < $exam->question_count)<span class="content-exam-warning"><svg data-icon="alert"></svg> {{ $exam->available_count }} soal terbit tersedia dari {{ $exam->question_count }} yang dibutuhkan</span>@endif
  </div>
  <span class="badge {{ $exam->status->badgeClass() }} badge--flat">{{ $exam->status->label() }}</span>
  <div class="content-row-actions">
    <a class="btn btn--ghost btn--sm" href="{{ route($examRoute.'.edit', $exam) }}">Kelola</a>
    <details class="content-menu"><summary class="content-icon-button" aria-label="Tindakan untuk {{ $exam->title }}"><svg data-icon="more"></svg></summary><div class="content-menu__items">
      <a href="{{ route($examRoute.'.edit', $exam) }}"><svg data-icon="pencil"></svg> Edit Pengaturan</a>
      <a href="{{ route($examRoute.'.edit', $exam) }}#soal-sesi"><svg data-icon="layers"></svg> Kelola Soal</a>
      <form method="POST" action="{{ route($examRoute.'.destroy', $exam) }}" data-content-confirm data-confirm-title="Arsipkan {{ $exam->kind === 'quiz' ? 'latihan' : 'tryout' }}?" data-confirm-body="Sesi disembunyikan dari katalog. Riwayat dan sesi yang sudah berjalan tetap tersimpan." data-confirm-ok="Ya, arsipkan">@csrf @method('DELETE')<button type="submit" class="is-danger"><svg data-icon="trash"></svg> Arsipkan</button></form>
    </div></details>
  </div>
</article>
