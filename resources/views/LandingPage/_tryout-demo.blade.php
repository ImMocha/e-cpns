<section class="card card--pop tryout-demo" data-tryout-demo aria-labelledby="tryout-demo-title">
  <header class="tryout-demo__header">
    <span class="badge badge--ink"><svg data-icon="timer"></svg> Demo CAT</span>
    <span class="tryout-demo__timer" aria-label="Sisa waktu"><svg data-icon="clock"></svg><span data-demo-timer>03:00</span></span>
  </header>
  <div>
    <h3 id="tryout-demo-title">Coba 3 soal, rasakan alurnya</h3>
    <p class="text-sm text-soft">Contoh TWK, TIU, dan TKP · 3 menit · tanpa perlu masuk.</p>
  </div>
  <div class="tryout-demo__intro" data-demo-intro>
    <div class="demo-preview">
      <div class="demo-preview__scene" aria-hidden="true">
        <div class="demo-preview__sheet">
          <span class="demo-preview__sheet-title"><span><svg data-icon="monitor"></svg> CAT PREVIEW</span><span>01 / 03</span></span>
          <span class="demo-preview__question-label">TIU · DERET ANGKA</span>
          <strong class="demo-preview__question">3, 6, 12, 24, <span>…?</span></strong>
          <div class="demo-preview__answers"><span>A <b>30</b></span><span>B <b>36</b></span><span class="is-selected">C <b>48</b><svg data-icon="check-circle"></svg></span><span>D <b>54</b></span></div>
          <span class="demo-preview__sheet-footer"><span></span><span></span><span></span><small>Pilih. Kerjakan. Pahami.</small></span>
        </div>
      </div>
      <div class="demo-preview__subjects">
        <div><span class="demo-preview__subject-icon"><svg data-icon="shield"></svg></span><strong>TWK</strong><span>Wawasan kebangsaan</span></div>
        <div><span class="demo-preview__subject-icon"><svg data-icon="zap"></svg></span><strong>TIU</strong><span>Intelegensia umum</span></div>
        <div><span class="demo-preview__subject-icon"><svg data-icon="users"></svg></span><strong>TKP</strong><span>Karakteristik pribadi</span></div>
      </div>
    </div>
    <p class="text-sm">Pilih jawaban, pindah nomor soal, lalu lihat hasil dan pembahasan singkat.</p>
    <button type="button" class="btn btn--primary btn--block" data-demo-start>Mulai demo<svg data-icon="play-circle"></svg></button>
  </div>
  <div class="tryout-demo__workspace" data-demo-workspace hidden>
    <div class="tryout-demo__progress"><span class="badge badge--primary badge--flat" data-demo-category>TWK</span><span class="text-xs text-soft" data-demo-progress></span></div>
    <fieldset class="tryout-demo__question">
      <legend class="fw-700" data-demo-question tabindex="-1"></legend>
      <div class="tryout-demo__options" data-demo-options></div>
    </fieldset>
    <div class="tryout-demo__navigation">
      <button type="button" class="btn btn--ghost btn--sm" data-demo-previous aria-label="Soal sebelumnya"><svg data-icon="arrow-left"></svg></button>
      <nav class="tryout-demo__numbers" aria-label="Nomor soal demo">
        @for ($number = 1; $number <= 3; $number++)
          <button type="button" data-demo-number="{{ $number - 1 }}" aria-label="Soal {{ $number }}">{{ $number }}</button>
        @endfor
      </nav>
      <button type="button" class="btn btn--ghost btn--sm" data-demo-next aria-label="Soal berikutnya"><svg data-icon="arrow-right"></svg></button>
    </div>
    <p class="text-xs text-soft">Nomor berwarna biru sudah dijawab.</p>
    <button type="button" class="btn btn--primary btn--block" data-demo-finish>Selesai &amp; lihat hasil<svg data-icon="check"></svg></button>
  </div>
  <div class="tryout-demo__result" data-demo-result hidden>
    <h4 data-demo-score tabindex="-1"></h4>
    <p class="text-sm text-soft" data-demo-summary></p>
    <div class="tryout-demo__review" data-demo-review></div>
    <button type="button" class="btn btn--ghost btn--block" data-demo-reset>Ulangi demo<svg data-icon="refresh"></svg></button>
  </div>
  <p class="text-xs text-soft tryout-demo__notice" role="status" data-demo-status>Demo pengenalan. Hasil tidak masuk riwayat atau leaderboard.</p>
  <noscript><p class="text-sm">Aktifkan JavaScript untuk mencoba demo interaktif.</p></noscript>
  <a class="btn btn--ghost btn--block" href="{{ route('tryouts.index') }}">Lihat tryout lengkap<svg data-icon="arrow-right"></svg></a>
</section>
