@php($examRoute = \App\Enums\ExamKind::from($exam->kind)->adminRoutePrefix())
<article @class(['content-exam-row', 'content-exam-row--cover' => $exam->kind !== 'quiz'])>
  @if($exam->kind !== 'quiz')
  <img class="exam-admin-cover" src="{{ $exam->coverUrl() }}" alt="" width="96" height="64" loading="lazy" decoding="async">
  @else
  <span class="content-exam-icon"><svg data-icon="list-checks"></svg></span>
  @endif
  <div class="content-exam-info">
    <a class="content-material-title" href="{{ route($examRoute.'.edit', $exam) }}">{{ $exam->title }}</a>
    @if($exam->kind === 'quiz')<div class="content-material-path">{{ $exam->topic ? $exam->topic->section?->name.' / '.$exam->topic->name : 'Latihan Soal umum · belum terhubung ke topik' }}</div>@endif
    <div class="content-material-meta"><span>{{ $exam->question_count }} soal per pengerjaan</span><span>{{ $exam->duration_minutes ? $exam->duration_minutes.' menit' : 'Tanpa timer' }}</span><span @class(['badge badge--amber badge--flat' => $exam->free_claim_enabled])>{{ $exam->accessLabel() }}</span>@if($exam->kind === 'quiz')<span>{{ $exam->requires_topic_completion ? 'Setelah semua materi selesai' : 'Bisa dikerjakan kapan saja' }}</span>@endif</div>
    @if($exam->free_claim_enabled)<p class="text-xs text-soft" style="margin-top:.4rem">Peserta wajib memenuhi syarat, upload bukti, dan menunggu persetujuan admin sebelum sesi terbuka.</p>@endif
    @if($exam->available_count < $exam->question_count)<span class="content-exam-warning"><svg data-icon="alert"></svg> {{ $exam->available_count }} soal terbit tersedia dari {{ $exam->question_count }} yang dibutuhkan</span>@endif
  </div>
  <span class="badge {{ $exam->status->badgeClass() }} badge--flat">{{ $exam->status->label() }}</span>
  <div class="content-row-actions">
    <a class="btn btn--ghost btn--sm" href="{{ route($examRoute.'.edit', $exam) }}">Kelola</a>
    <details class="content-menu"><summary class="content-icon-button" aria-label="Tindakan untuk {{ $exam->title }}"><svg data-icon="more"></svg></summary><div class="content-menu__items">
      <a href="{{ route($examRoute.'.edit', $exam) }}"><svg data-icon="pencil"></svg> Edit Pengaturan</a>
      <a href="{{ route($examRoute.'.edit', $exam) }}#soal-sesi"><svg data-icon="layers"></svg> Kelola Soal</a>
      <form method="POST" action="{{ route($examRoute.'.destroy', $exam) }}" data-content-confirm data-confirm-title="Arsipkan {{ strtolower(\App\Enums\ExamKind::from($exam->kind)->label()) }}?" data-confirm-body="Sesi disembunyikan dari katalog. Riwayat dan sesi yang sudah berjalan tetap tersimpan." data-confirm-ok="Ya, arsipkan">@csrf @method('DELETE')<button type="submit" class="is-danger"><svg data-icon="trash"></svg> Arsipkan</button></form>
    </div></details>
  </div>
</article>
