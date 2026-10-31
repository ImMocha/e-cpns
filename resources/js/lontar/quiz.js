import { sanitizeArticle } from './article-editor.js';

(function () {
  'use strict';
  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));
  const keys = 'abcdefghij';
  const esc = (value) => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const storage = {
    get(key) { try { return JSON.parse(localStorage.getItem(key)); } catch { return null; } },
    set(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch {} },
    del(key) { try { localStorage.removeItem(key); } catch {} }
  };

  function boot() {
    const stage = $('#questionStage');
    if (!stage) return;
    let cfg;
    try { cfg = JSON.parse($('#quiz-data').textContent); } catch {
      stage.textContent = 'Sesi tidak dapat dimuat. Silakan muat ulang halaman.';
      return;
    }
    const total = cfg.questions.length;
    const cacheKey = 'lontar:attempt:' + cfg.attemptId;
    const cached = storage.get(cacheKey);
    const state = { answers: { ...cfg.state.answers }, flags: { ...cfg.state.flags }, current: cfg.state.current };
    let pendingAnswers = { ...(cached?.pendingAnswers || {}) };
    let pendingFlags = { ...(cached?.pendingFlags || {}) };
    let dirty = cached?.pending === true || Object.keys(pendingAnswers).length > 0 || Object.keys(pendingFlags).length > 0;
    let finished = false, submitting = false, saving = null, saveTimer = null, autoRetryAt = 0, saveRetryAt = 0;
    let inFlightAnswers = {}, inFlightFlags = {};
    let hintMessage = dirty ? 'Memulihkan jawaban dari perangkat…' : 'Tersimpan di server';
    let hintClass = dirty ? 'is-saving' : 'is-saved';
    const initialTick = performance.now();
    const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content || '' };
    const validIds = new Set(cfg.questions.map(q => String(q.id)));
    for (const map of [pendingAnswers, pendingFlags]) {
      Object.keys(map).forEach(id => { if (!validIds.has(id)) delete map[id]; });
    }

    function overlay(target, changes) {
      Object.entries(changes).forEach(([id, value]) => {
        if (value === null || value === false) delete target[id];
        else target[id] = value;
      });
    }
    overlay(state.answers, pendingAnswers);
    overlay(state.flags, pendingFlags);
    if (dirty && Number.isInteger(cached.current)) state.current = cached.current;
    if (!Number.isInteger(state.current) || state.current < 0 || state.current >= total) state.current = 0;

    function cache() {
      storage.set(cacheKey, { pending: true, pendingAnswers: { ...inFlightAnswers, ...pendingAnswers }, pendingFlags: { ...inFlightFlags, ...pendingFlags }, current: state.current });
    }
    function saveHint(message = hintMessage, className = hintClass) {
      hintMessage = message; hintClass = className;
      for (const element of $$('#autosaveHint, #connectionStatus')) {
        element.classList.remove('is-saving', 'is-saved');
        if (className) element.classList.add(className);
        const text = $('span', element) || element;
        text.textContent = message;
      }
    }
    function redirectResult(url) {
      finished = true;
      storage.del(cacheKey);
      location.href = url;
    }
    function flush() {
      clearTimeout(saveTimer);
      if (saving) return saving;
      if (!dirty || finished) return Promise.resolve();
      saving = (async () => {
        while (dirty && !finished) {
          const answers = pendingAnswers, flags = pendingFlags;
          inFlightAnswers = answers; inFlightFlags = flags;
          pendingAnswers = {}; pendingFlags = {}; dirty = false;
          saveHint('Menyimpan…', 'is-saving');
          try {
            const response = await fetch(cfg.saveUrl, { method: 'PATCH', headers, credentials: 'same-origin', keepalive: true,
              body: JSON.stringify({ answers, flags, current: state.current }) });
            const data = await response.json();
            if (response.status === 409 && data.resultUrl) { redirectResult(data.resultUrl); return; }
            if (!response.ok) throw new Error(data.message || 'Jawaban belum tersimpan.');
            inFlightAnswers = {}; inFlightFlags = {};
            state.answers = { ...data.state.answers };
            state.flags = { ...data.state.flags };
            overlay(state.answers, pendingAnswers); overlay(state.flags, pendingFlags);
            if (dirty) cache(); else storage.del(cacheKey);
            refreshSelection(); renderPalette(); updateStats();
            saveHint(dirty ? 'Menyimpan…' : 'Tersimpan di server', dirty ? 'is-saving' : 'is-saved');
          } catch (error) {
            pendingAnswers = { ...answers, ...pendingAnswers };
            pendingFlags = { ...flags, ...pendingFlags };
            inFlightAnswers = {}; inFlightFlags = {};
            dirty = true; cache();
            saveRetryAt = performance.now() + 5000;
            saveHint(navigator.onLine ? 'Belum tersimpan. Tekan Simpan ulang.' : 'Offline · tersimpan di perangkat', '');
            throw error;
          }
        }
      })().finally(() => { saving = null; });
      return saving;
    }
    function persist() {
      if (finished) return;
      dirty = true; cache(); clearTimeout(saveTimer);
      saveHint('Menunggu penyimpanan…', 'is-saving');
      saveTimer = setTimeout(() => flush().catch(() => {}), 420);
    }
    function currentQuestion() { return cfg.questions[state.current]; }
    function refreshSelection() {
      const q = currentQuestion();
      $$('[data-opt]', stage).forEach(button => {
        const selected = state.answers[q.id] === Number(button.dataset.opt);
        button.classList.toggle('is-selected', selected);
        button.setAttribute('aria-pressed', String(selected));
      });
      const flag = $('[data-flag]', stage);
      if (flag) {
        flag.classList.toggle('is-on', !!state.flags[q.id]);
        flag.setAttribute('aria-pressed', String(!!state.flags[q.id]));
        flag.textContent = state.flags[q.id] ? 'Ditandai' : 'Tandai';
      }
    }
    function renderQuestion() {
      const q = currentQuestion();
      stage.innerHTML = '<section class="card card--static"><div class="qhead"><span class="qnum">Soal ' + (state.current + 1) +
        '</span><span class="badge badge--primary badge--flat">' + esc(q.section || 'Umum') +
        '</span><span class="text-xs text-soft">' + esc(q.topic) + '</span><span class="autosave" id="autosaveHint"><span></span></span></div>' +
        (q.scoringType === 'weighted' ? '<p class="exam-scoring-notice">TKP berbobot · setiap pilihan bernilai 1–5, jawaban kosong 0.</p>' : '') +
        '<div class="qstem article-prose">' + (q.stemHtml ? sanitizeArticle(q.stemHtml) : esc(q.stem)) + '</div>' +
        (q.imageUrl ? '<img class="exam-question-media' + (q.fixedOptionOrder ? ' exam-question-figure' : '') + '" src="' + esc(q.imageUrl) + '" alt="Ilustrasi soal">' : '') +
        (q.videoUrl ? '<video class="exam-question-media" controls playsinline preload="metadata" src="' + esc(q.videoUrl) + '">Browser tidak mendukung video.</video>' : '') +
        '<div class="grid" style="--gap:.7rem;margin-top:1.25rem">' + q.options.map((option, index) =>
          '<button type="button" class="opt" data-opt="' + index + '" aria-pressed="false"><span class="opt__key">' +
          keys[index].toUpperCase() + '</span><span class="opt__text">' + esc(option) +
          (q.optionImageUrls?.[index] ? '<img class="question-option-image" src="' + esc(q.optionImageUrls[index]) + '" alt="Gambar pilihan ' + keys[index].toUpperCase() + '">' : '') + '</span></button>').join('') + '</div>' +
        '<div class="qfoot"><div class="btn-row"><button type="button" class="btn btn--ghost btn--sm" data-prev' +
        (state.current === 0 ? ' disabled' : '') + '>Sebelumnya</button><button type="button" class="btn btn--ghost btn--sm flag-btn" data-flag>Tandai</button></div>' +
        '<button type="button" class="btn btn--primary" data-next>' + (state.current === total - 1 ? 'Selesaikan' : 'Berikutnya') + '</button></div></section>';
      $$('[data-opt]', stage).forEach(button => button.addEventListener('click', () => {
        if (finished || submitting || (cfg.showTimer && secondsLeft() === 0)) return;
        const index = Number(button.dataset.opt);
        const value = state.answers[q.id] === index ? null : index;
        pendingAnswers[q.id] = value; overlay(state.answers, { [q.id]: value });
        persist(); refreshSelection(); renderPalette(); updateStats();
      }));
      $('[data-flag]', stage).addEventListener('click', () => {
        if (finished || submitting || (cfg.showTimer && secondsLeft() === 0)) return;
        const value = !state.flags[q.id];
        pendingFlags[q.id] = value; overlay(state.flags, { [q.id]: value });
        persist(); refreshSelection(); renderPalette(); updateStats();
      });
      $('[data-prev]', stage).addEventListener('click', () => go(state.current - 1));
      $('[data-next]', stage).addEventListener('click', () => state.current === total - 1 ? openSubmitDialog() : go(state.current + 1));
      refreshSelection(); renderPalette(); updateStats(); saveHint();
    }
    function renderPalette() {
      const palette = $('#palette');
      if (!palette) return;
      const filter = $('#filterUnanswered')?.getAttribute('aria-pressed') === 'true';
      palette.innerHTML = cfg.questions.map((q, index) => {
        const answered = state.answers[q.id] != null, flagged = !!state.flags[q.id], current = index === state.current;
        return '<button type="button" class="pal ' + (answered ? 'is-answered ' : '') + (flagged ? 'is-flagged ' : '') +
          (current ? 'is-current' : '') + '" data-go="' + index + '" aria-label="Soal ' + (index + 1) +
          (answered ? ', sudah dijawab' : ', belum dijawab') + (flagged ? ', ditandai' : '') + '"' +
          (current ? ' aria-current="step"' : '') + (filter && answered ? ' hidden' : '') + '>' + (index + 1) + '</button>';
      }).join('');
      $$('[data-go]', palette).forEach(button => button.addEventListener('click', () => go(Number(button.dataset.go))));
    }
    function updateStats() {
      const answered = cfg.questions.filter(q => state.answers[q.id] != null).length;
      for (const [selector, text] of [['#statAnswered', answered + '/' + total], ['#statLeft', total - answered], ['#statFlagged', Object.keys(state.flags).length]]) {
        const element = $(selector); if (element) element.textContent = text;
      }
    }
    function go(index) {
      if (finished || submitting || index < 0 || index >= total) return;
      state.current = index; persist(); renderQuestion();
      stage.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function secondsLeft() {
      return Math.max(0, Math.ceil((cfg.expiresAt - cfg.serverNow - (performance.now() - initialTick)) / 1000));
    }
    function tick() {
      if (!cfg.showTimer || finished) return;
      const remaining = secondsLeft();
      const timer = $('#timer');
      if (timer) timer.textContent = [Math.floor(remaining / 3600), Math.floor(remaining % 3600 / 60), remaining % 60].map(n => String(n).padStart(2, '0')).join(':');
      $('#timerWrap')?.classList.toggle('timer--danger', remaining <= 60);
      $('#timerWrap')?.classList.toggle('timer--warn', remaining > 60 && remaining <= 300);
      if (remaining === 0 && performance.now() >= autoRetryAt) {
        autoRetryAt = performance.now() + 10000;
        finish(true);
      }
    }
    async function finish(automatic = false) {
      if (finished || submitting) return;
      submitting = true;
      try {
        await flush();
        if (finished) return;
        const response = await fetch(cfg.submitUrl, { method: 'POST', headers, credentials: 'same-origin' });
        const data = await response.json();
        if (!response.ok || !data.resultUrl) throw new Error('Jawaban belum berhasil dikumpulkan.');
        redirectResult(data.resultUrl);
      } catch (error) {
        saveHint(automatic ? 'Waktu habis · menunggu koneksi ke server' : 'Gagal mengumpulkan. Periksa koneksi lalu coba lagi.', '');
        if (!automatic) window.toast(error.message || 'Koneksi bermasalah.', 'error');
      } finally { submitting = false; }
    }
    function openSubmitDialog() {
      if (finished || submitting) return;
      const blank = cfg.questions.filter(q => state.answers[q.id] == null).length;
      window.L.confirmAction({ title: 'Kumpulkan jawaban?', body: blank ? 'Masih ada ' + blank + ' soal kosong. Setelah dikumpulkan, jawaban tidak bisa diubah.' :
        'Semua jawaban akan dikumpulkan dan tidak bisa diubah lagi.', ok: 'Ya, kumpulkan', cancel: 'Kerjakan dulu', tone: 'primary', onOk: () => finish() });
    }
    $('#submitBtn')?.addEventListener('click', openSubmitDialog);
    $('#saveNow')?.addEventListener('click', () => flush().catch(() => {}));
    $('#filterUnanswered')?.addEventListener('click', function () {
      const active = this.getAttribute('aria-pressed') !== 'true';
      this.setAttribute('aria-pressed', String(active)); this.classList.toggle('btn--amber', active); renderPalette();
      if (active) {
        const next = cfg.questions.findIndex(q => state.answers[q.id] == null);
        if (next >= 0) go(next); else window.toast('Semua soal sudah dijawab.', 'success');
      }
    });
    $('#exitBtn')?.addEventListener('click', () => window.L.confirmAction({
      title: 'Simpan dan keluar?', body: 'Sesi bisa dilanjutkan dari daftar atau riwayat. Waktu sesi bertimer tetap berjalan.',
      ok: 'Simpan & keluar', cancel: 'Lanjut mengerjakan', tone: 'amber', onOk: async () => {
        try { await flush(); if (!finished) location.href = cfg.exitUrl; } catch { window.toast('Jawaban belum tersimpan di server. Coba lagi setelah koneksi pulih.', 'warn'); }
      }
    }));
    document.addEventListener('keydown', event => {
      if (finished || submitting || document.body.classList.contains('driver-active') || event.target.closest('input,textarea,select,[contenteditable="true"]') || event.metaKey || event.ctrlKey || event.altKey ||
          $('.modal.is-open') || $$('[role="dialog"]').some(dialog => dialog.getClientRects().length && dialog.getAttribute('aria-hidden') !== 'true')) return;
      const key = event.key.toLowerCase(), q = currentQuestion();
      if (key === 'arrowright' || key === 'arrowleft') { event.preventDefault(); go(state.current + (key === 'arrowright' ? 1 : -1)); }
      else if (key === 'f' && (event.shiftKey || q.options.length <= 5)) { event.preventDefault(); $('[data-flag]', stage)?.click(); }
      else if (keys.includes(key) && keys.indexOf(key) < q.options.length) { event.preventDefault(); $('[data-opt="' + keys.indexOf(key) + '"]', stage)?.click(); }
    });
    window.addEventListener('online', () => { flush().catch(() => {}); if (cfg.showTimer && secondsLeft() === 0) finish(true); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) flush().catch(() => {}); else tick(); });
    window.addEventListener('beforeunload', () => { if (dirty || saving) cache(); if (dirty) flush().catch(() => {}); });
    renderQuestion(); tick();
    if (dirty) flush().catch(() => {});
    setInterval(() => { tick(); if (dirty && !submitting && navigator.onLine && performance.now() >= saveRetryAt) flush().catch(() => {}); }, 1000);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
