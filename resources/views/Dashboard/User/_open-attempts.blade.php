@if ($openAttempts->isNotEmpty())
<section class="card">
        <div class="card__head"><div><div class="card__title">Sesi Belum Selesai</div><div class="card__sub">Lanjutkan jawaban yang tersimpan</div></div></div>
        <div class="grid grid-cols-1" style="--gap:.75rem">
          @foreach ($openAttempts as $attempt)
            <a class="card card--tight card--dashed" href="{{ $attempt->attemptUrl() }}"><strong>{{ $attempt->title }}</strong><span class="text-xs text-soft" style="display:block;margin-top:.25rem">{{ count($attempt->answers ?? []) }} dari {{ count($attempt->question_snapshot['questions']) }} soal dijawab</span></a>
          @endforeach
        </div>
      </section>
@endif
