/* ==========================================================================
   LONTAR · quiz.js
   Reusable tryout / quiz engine: timer, autosave, palette navigation,
   flagging, scoring and result hand-off.

   Data source: <script id="quiz-data" type="application/json"> { … } </script>
   State:       localStorage "lontar:attempt:<id>"  (in-progress)
                localStorage "lontar:result:<id>"   (finished → read by results page)
   ========================================================================== */
(function () {
  'use strict';

  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const KEYS = 'abcdefghij';

  const store = {
    get(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del(k) { localStorage.removeItem(k); }
  };

  const esc = (s) => String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  /* ======================================================================
     BOOT
     ====================================================================== */
  function boot() {
    const stage = $('#questionStage');
    if (!stage) return;

    /* Sumber data: inline <script id="quiz-data">, atau bank global yang
       dimuat oleh <script src="assets/data/bank-*.js"> (body[data-bank]). */
    let cfg = null;
    const holder = $('#quiz-data');
    if (holder) {
      try { cfg = JSON.parse(holder.textContent); } catch (e) { console.error('quiz-data JSON tidak valid', e); }
    } else {
      const id = document.body.dataset.bank;
      cfg = (window.LONTAR_BANKS || {})[id];
    }
    if (!cfg) {
      stage.innerHTML =
        '<div class="alert alert--danger"><span class="alert__icon">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>' +
        '</span><div>Bank soal tidak ditemukan. Pastikan <code>assets/data/bank-*.js</code> ikut termuat.</div></div>';
      return;
    }

    const AKEY = 'lontar:attempt:' + cfg.id;
    const RKEY = 'lontar:result:' + cfg.id;
    const BANK = 'lontar:bank:' + cfg.id;
    const total = cfg.questions.length;

    /* Simpan bank soal agar halaman hasil bisa menyusun tampilan contoh
       meskipun belum ada sesi tryout yang selesai. */
    store.set(BANK, cfg.questions);

    let state = store.get(AKEY);
    let finished = false;
    const fresh = {
      answers: {},
      flags: {},
      current: 0,
      startedAt: Date.now(),
      timeLeft: cfg.durationSeconds || 0
    };

    if (state && state.answers && Object.keys(state.answers).length) {
      state.resumed = true;
    } else {
      state = fresh;
      store.set(AKEY, state);
    }
    if (!state.answers) state.answers = {};
    if (!state.flags) state.flags = {};

    const el = {
      stage,
      palette: $('#palette'),
      timer: $('#timer'),
      timerWrap: $('#timerWrap'),
      statAnswered: $('#statAnswered'),
      statFlagged: $('#statFlagged'),
      statLeft: $('#statLeft'),
      filterBtn: $('#filterUnanswered'),
      submitBtn: $('#submitBtn'),
      exitBtn: $('#exitBtn')
    };

    /* ---------------- persistence ---------------- */
    let saveTimer = null;
    function persist(immediate) {
      /* Setelah sesi dikumpulkan, jangan pernah menulis attempt lagi.
         Tanpa guard ini, beforeunload akan menghidupkan kembali attempt
         yang sudah selesai saat halaman dialihkan ke halaman hasil. */
      if (finished) return;
      const write = () => {
        /* Theodorinya: cek juga di dalam write, karena timer yang sudah
           dijadwalkan sebelum sesi selesai bisa saja fired belakangan. */
        if (finished) return;
        store.set(AKEY, state);
        const hint = document.querySelector('#autosaveHint');
        if (!hint) return;
        hint.classList.add('is-saving');
        hint.querySelector('span').textContent = 'Menyimpan…';
        setTimeout(() => {
          hint.classList.remove('is-saving');
          hint.classList.add('is-saved');
          hint.querySelector('span').textContent = 'Tersimpan otomatis';
          setTimeout(() => hint.classList.remove('is-saved'), 1600);
        }, 260);
      };
      clearTimeout(saveTimer);
      if (immediate) write();
      else saveTimer = setTimeout(write, 420);
    }

    /* ---------------- rendering ---------------- */
    function currentQ() { return cfg.questions[state.current]; }

    function renderQuestion() {
      const q = currentQ();
      const n = state.current + 1;
      const chosen = state.answers[q.id];
      const locked = cfg.reviewMode === true;

      const opts = q.options.map((text, i) => {
        let cls = 'opt';
        if (!locked) {
          if (chosen === i) cls += ' is-selected';
        } else {
          cls += ' is-locked';
          if (i === q.answer) cls += ' is-correct';
          else if (chosen === i) cls += ' is-wrong';
        }
        let tag = '';
        if (locked && i === q.answer) tag = '<span class="opt__tag" style="color:#0B7C5C">Jawaban benar</span>';
        if (locked && chosen === i && i !== q.answer) tag = '<span class="opt__tag" style="color:#B3282C">Jawaban kamu</span>';
        return (
          '<button type="button" class="' + cls + '" data-opt="' + i + '"' + (locked ? ' disabled' : '') + '>' +
            '<span class="opt__key">' + KEYS[i] + '</span>' +
            '<span class="opt__text">' + esc(text) + '</span>' + tag +
          '</button>'
        );
      }).join('');

      const isFlagged = !!state.flags[q.id];

      stage.innerHTML =
        '<section class="card card--pop">' +
          '<div class="qhead">' +
            '<span class="qnum">Soal ' + n + '</span>' +
            '<span class="badge badge--primary badge--flat">' + esc(q.section || 'Umum') + '</span>' +
            '<span class="text-xs text-soft fw-700">' + esc(q.topic || '') + '</span>' +
            '<span style="margin-left:auto" class="autosave" id="autosaveHint">' +
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>' +
              '<span>Tersimpan otomatis</span>' +
            '</span>' +
          '</div>' +

          '<p class="qstem">' + n + '. ' + esc(q.stem) + '</p>' +

          (q.image ? '<div class="card card--flat card--dashed" style="margin-top:1rem;display:grid;place-items:center;min-height:140px;color:#B9CFE0">' +
            '<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>' +
            '<span class="text-xs">Ilustrasi soal (prototype)</span></div>' : '') +

          '<div class="grid" style="--gap:.7rem;margin-top:1.25rem">' + opts + '</div>' +

          (locked && q.explain
            ? '<div class="explain ' + (chosen === q.answer ? 'explain--correct' : 'explain--wrong') + '" style="margin-top:1.25rem">' +
                '<div class="explain__title">' +
                  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z"/></svg>' +
                  'Pembahasan</div>' + esc(q.explain) +
              '</div>'
            : '') +

          '<div class="qfoot">' +
            '<div class="btn-row">' +
              '<button class="btn btn--ghost btn--sm" data-nav="prev"' + (state.current === 0 ? ' disabled' : '') + '>' +
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>Sebelumnya</button>' +
              '<button class="btn btn--ghost btn--sm flag-btn' + (isFlagged ? ' is-on' : '') + '" data-flag>' +
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V4s-1 1-4 1-5-2-8-2-4 1-4 1Z"/><path d="M4 22v-7"/></svg>' +
                (isFlagged ? 'Ditandai' : 'Tandai') + '</button>' +
            '</div>' +
            (state.current === total - 1
              ? '<button class="btn btn--primary" data-submit-open>Selesaik<i></i>ikan<span class="btn__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span></button>'
              : '<button class="btn btn--primary" data-nav="next">Berikutnya<span class="btn__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span></button>') +
          '</div>' +
        '</section>';

      bindQuestion();
      renderPalette();
      updateStats();
    }

    function bindQuestion() {
      $$('[data-opt]', stage).forEach((btn) => {
        btn.addEventListener('click', () => {
          if (cfg.reviewMode) return;
          const q = currentQ();
          const i = parseInt(btn.dataset.opt, 10);
          state.answers[q.id] = state.answers[q.id] === i ? undefined : i;
          if (state.answers[q.id] === undefined) delete state.answers[q.id];
          persist();
          renderQuestion();
        });
      });
      const prev = $('[data-nav="prev"]', stage);
      const next = $('[data-nav="next"]', stage);
      if (prev) prev.addEventListener('click', () => go(state.current - 1));
      if (next) next.addEventListener('click', () => go(state.current + 1));
      const flag = $('[data-flag]', stage);
      if (flag) flag.addEventListener('click', () => {
        const q = currentQ();
        if (state.flags[q.id]) delete state.flags[q.id];
        else state.flags[q.id] = true;
        persist();
        renderQuestion();
      });
      const done = $('[data-submit-open]', stage);
      if (done) done.addEventListener('click', openSubmitDialog);
    }

    /* ---------------- palette ---------------- */
    function renderPalette() {
      if (!el.palette) return;
      el.palette.innerHTML = cfg.questions
        .map((q, i) => {
          const cls = [
            'pal',
            state.answers[q.id] != null ? 'is-answered' : '',
            state.flags[q.id] ? 'is-flagged' : '',
            i === state.current ? 'is-current' : ''
          ].filter(Boolean).join(' ');
          return '<button type="button" class="' + cls + '" data-go="' + i + '" aria-label="Soal ' + (i + 1) + '">' + (i + 1) + '</button>';
        })
        .join('');
      $$('[data-go]', el.palette).forEach((b) => b.addEventListener('click', () => go(parseInt(b.dataset.go, 10))));
    }

    function updateStats() {
      const answered = cfg.questions.filter((q) => state.answers[q.id] != null).length;
      const flagged = Object.keys(state.flags).length;
      if (el.statAnswered) el.statAnswered.textContent = answered + '/' + total;
      if (el.statFlagged) el.statFlagged.textContent = flagged;
      if (el.statLeft) el.statLeft.textContent = Math.max(0, total - answered);
    }

    function go(i) {
      if (i < 0 || i >= total) return;
      state.current = i;
      persist();
      renderQuestion();
      if (window.innerWidth <= 1024) window.scrollTo({ top: 0, behavior: 'smooth' });
      else stage.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    /* ---------------- timer ---------------- */
    function hms(sec) {
      const h = Math.floor(sec / 3600);
      const m = Math.floor((sec % 3600) / 60);
      const s = sec % 60;
      return [h, m, s].map((n) => String(n).padStart(2, '0')).join(':');
    }

    function tick() {
      if (!cfg.showTimer) return;
      if (state.timeLeft <= 0) { finish(true); return; }
      state.timeLeft -= 1;
      if (el.timer) {
        el.timer.textContent = hms(state.timeLeft);
        if (state.timeLeft <= 60) {
          el.timerWrap.classList.remove('timer--warn');
          el.timerWrap.classList.add('timer--danger');
        } else if (state.timeLeft <= 300) {
          el.timerWrap.classList.add('timer--warn');
          el.timerWrap.classList.remove('timer--danger');
        }
      }
      if (state.timeLeft % 10 === 0) persist(true);
    }

    /* ---------------- scoring ----------------
       PRD §13: rumus penilaian belum dikunci client.
       Prototype memakai: benar × pointsPerCorrect, salah × penaltyWrong
       (dibatasi 0), belum dijawab 0.                        */
    function score() {
      const per = cfg.pointsPerCorrect || 5;
      const pen = cfg.penaltyWrong || 0;
      let correct = 0, wrong = 0, blank = 0;
      const bySection = {};

      cfg.questions.forEach((q) => {
        const a = state.answers[q.id];
        const key = q.section || 'Umum';
        bySection[key] = bySection[key] || { section: key, correct: 0, wrong: 0, blank: 0, points: 0 };
        if (a == null) { blank++; bySection[key].blank++; return; }
        if (a === q.answer) { correct++; bySection[key].correct++; bySection[key].points += per; }
        else { wrong++; bySection[key].wrong++; bySection[key].points -= pen; }
      });

      Object.keys(bySection).forEach((k) => {
        bySection[k].points = Math.max(0, bySection[k].points);
      });

      const raw = correct * per - wrong * pen;
      return {
        correct, wrong, blank, total,
        score: Math.max(0, Math.round(raw)),
        max: total * per,
        sections: Object.keys(bySection).map((k) => {
          const s = bySection[k];
          s.answered = s.correct + s.wrong;
          s.count = s.correct + s.wrong + s.blank;
          return s;
        })
      };
    }

    function finish(auto) {
      const res = score();
      finished = true;
      clearTimeout(saveTimer);          // batalkan autosave yang masih tertunda
      store.del(AKEY);
      res.id = cfg.id;
      res.title = cfg.title;
      res.subtitle = cfg.subtitle || '';
      res.passingScore = cfg.passingScore || null;
      res.answers = Object.assign({}, state.answers);
      res.flags = Object.assign({}, state.flags);
      res.questions = cfg.questions;
      res.durationSeconds = cfg.durationSeconds - state.timeLeft;
      res.finishedAt = Date.now();
      res.autoSubmitted = !!auto;
      res.reviewUrl = cfg.reviewUrl || 'tryout-hasil.html';
      store.set(RKEY, res);

      const url = res.reviewUrl + '?t=' + res.finishedAt;
      if (auto) {
        window.toast('Waktu habis — jawaban dikirim otomatis.', 'warn', 4000);
        setTimeout(() => (location.href = url), 1400);
      } else {
        location.href = url;
      }
    }

    function openSubmitDialog() {
      const answered = cfg.questions.filter((q) => state.answers[q.id] != null).length;
      const blank = total - answered;
      window.L.confirmAction({
        title: 'Kumpulkan jawaban?',
        body: blank > 0
          ? 'Masih ada ' + blank + ' soal belum dijawab. Jawaban yang kosong tidak dihitung. Yakin ingin mengumpulkan sekarang?'
          : 'Semua ' + total + ' soal sudah dijawab. Jawaban yang dikumpulkan tidak bisa diubah lagi.',
        ok: 'Ya, kumpulkan',
        cancel: 'Kerjakan dulu',
        tone: 'primary',
        onOk: () => finish(false)
      });
    }

    /* ---------------- wiring ---------------- */
    if (el.submitBtn) el.submitBtn.addEventListener('click', openSubmitDialog);
    if (el.filterBtn) {
      el.filterBtn.addEventListener('click', () => {
        const on = el.filterBtn.getAttribute('aria-pressed') === 'true';
        el.filterBtn.setAttribute('aria-pressed', String(!on));
        el.filterBtn.classList.toggle('btn--amber', !on);
        updateStats();
        if (!on) {
          const nextBlank = cfg.questions.findIndex((q, i) => state.answers[q.id] == null && i > state.current);
          if (nextBlank >= 0) go(nextBlank);
          else {
            const first = cfg.questions.findIndex((q) => state.answers[q.id] == null);
            if (first >= 0) go(first);
            else window.toast('Semua soal sudah dijawab.', 'success');
          }
        }
      });
    }
    if (el.exitBtn) {
      el.exitBtn.addEventListener('click', () => {
        window.L.confirmAction({
          title: 'Keluar dari tryout?',
          body: 'Progres tersimpan otomatis dan bisa dilanjutkan nanti dari halaman Daftar Tryout.',
          ok: 'Ya, simpan & keluar',
          cancel: 'Lanjut mengerjakan',
          tone: 'amber',
          onOk: () => { persist(true); location.href = cfg.exitUrl || 'dashboard.html'; }
        });
      });
    }

    document.addEventListener('keydown', (e) => {
      if (e.target.matches('input, textarea, select') || e.metaKey || e.ctrlKey || e.altKey) return;
      const q = currentQ();
      if (e.key === 'ArrowRight') { go(state.current + 1); return; }
      if (e.key === 'ArrowLeft') { go(state.current - 1); return; }
      if (e.key.toLowerCase() === 'f') { const b = $('[data-flag]', stage); if (b) b.click(); return; }
      if (cfg.reviewMode) return;
      const idx = KEYS.indexOf(e.key.toUpperCase());
      if (idx >= 0 && idx < q.options.length) $('[data-opt="' + idx + '"]', stage)?.click();
      const num = parseInt(e.key, 10);
      if (num >= 1 && num <= 9 && num <= total) go(num - 1);
    });

    document.addEventListener('visibilitychange', () => { if (document.hidden) persist(true); });
    window.addEventListener('beforeunload', () => persist(true));

    /* ---------------- start ---------------- */
    if (el.timer && cfg.showTimer) el.timer.textContent = hms(state.timeLeft);
    if (el.timerWrap && cfg.showTimer && state.timeLeft <= 300) el.timerWrap.classList.add('timer--warn');
    renderQuestion();
    if (cfg.showTimer) setInterval(tick, 1000);

    if (state.resumed) {
      setTimeout(() => window.toast('Progres sebelumnya dipulihkan.', 'info'), 500);
    }
    window.LQuiz = { cfg, state, score, finish };
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
