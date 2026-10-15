import { helpGuides, findHelpGuide, searchHelpGuides } from './help-content.js';

const root = document.querySelector('[data-feature-help]');

if (root) initializeHelp(root);

function initializeHelp(container) {
  const audience = container.dataset.helpAudience;
  const current = findHelpGuide(container.dataset.helpRoute || '', audience);
  const dialog = container.querySelector('[data-help-dialog]');
  const search = container.querySelector('[data-help-search]');
  const topics = container.querySelector('[data-help-topics]');
  const article = container.querySelector('[data-help-article]');
  const count = container.querySelector('[data-help-count]');
  const destinations = new Map(Array.from(container.querySelectorAll('[data-help-destination]'), link => [link.dataset.helpDestination, link.href]));
  let selected = current;
  let activeTour = null;
  let loadingTour = false;

  function node(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
  }

  function button(text, handler, className = 'btn btn--ghost btn--sm') {
    const element = node('button', className, text);
    element.type = 'button';
    element.addEventListener('click', handler);
    return element;
  }

  function renderTopics() {
    const results = searchHelpGuides(search.value, audience);
    topics.replaceChildren();
    count.textContent = `${results.length} panduan ditemukan. Pencarian mencakup isi penjelasan dan bantuan kolom.`;
    const categories = new Set(results.map(item => item.category));
    for (const category of categories) {
      topics.append(node('p', 'help-topic-category', category));
      for (const item of results.filter(result => result.category === category)) {
        const topic = button(item.title, () => selectGuide(item), 'help-topic');
        topic.dataset.guideId = item.id;
        if (item.id === selected.id) topic.setAttribute('aria-current', 'true');
        topics.append(topic);
      }
    }
    if (!results.length) {
      topics.append(node('p', 'help-empty', 'Belum ada hasil. Coba kata yang lebih singkat, misalnya “kuota”, “terbit”, atau “nilai”.'));
    }
  }

  function selectGuide(item) {
    selected = item;
    article.replaceChildren();
    article.scrollTop = 0;
    article.append(node('span', 'help-eyebrow', item.category), node('h3', 'help-article-title', item.title), node('p', 'help-article-summary', item.summary));
    const actions = node('div', 'help-article-actions');
    if (item.id === current.id) actions.append(button('Mulai tur halaman ini', () => startTour(), 'btn btn--primary btn--sm'));
    if (destinations.has(item.id)) {
      const link = node('a', 'btn btn--ghost btn--sm', 'Buka halaman fitur');
      link.href = destinations.get(item.id);
      actions.append(link);
    }
    article.append(actions);

    const contents = node('nav', 'help-contents');
    contents.setAttribute('aria-label', 'Daftar isi panduan');
    contents.append(node('strong', '', 'Di panduan ini'));
    item.sections.forEach(([title], index) => {
      const link = node('a', '', title);
      link.href = `#help-section-${index}`;
      link.addEventListener('click', event => {
        event.preventDefault();
        article.querySelector(`#help-section-${index}`)?.scrollIntoView({ block: 'start', behavior: 'instant' });
      });
      contents.append(link);
    });
    article.append(contents);
    item.sections.forEach(([title, paragraphs], index) => {
      const section = node('section', 'help-article-section');
      section.id = `help-section-${index}`;
      section.append(node('h4', '', title));
      paragraphs.forEach(paragraph => {
        if (paragraph.startsWith('1. ')) {
          const list = node('ol', 'help-workflow');
          paragraph.split(/(?=\d+\.\s)/).forEach(step => list.append(node('li', '', step.replace(/^\d+\.\s/, ''))));
          section.append(list);
        } else {
          section.append(node('p', '', paragraph));
        }
      });
      article.append(section);
    });

    if (item.fields.length) {
      const fields = node('section', 'help-article-section');
      fields.append(node('h4', '', 'Arti kolom dan kontrol halaman'));
      fields.append(node('p', '', 'Kolom hanya muncul sesuai mode tambah/edit dan jenis konten. Klik Penjelasan pada formulir untuk membaca bantuan tanpa meninggalkan isian.'));
      for (const [, title, explanation] of item.fields) {
        const detail = node('details', 'help-field-reference');
        detail.append(node('summary', '', title), node('p', '', explanation));
        fields.append(detail);
      }
      article.append(fields);
    }
    if (item.id !== current.id) {
      article.append(node('p', 'help-context-note', 'Tur mengikuti halaman yang sedang dibuka. Untuk melihat sorotan fitur ini, buka halaman fiturnya, lalu pilih Mulai tur halaman.'));
    }
    renderTopics();
  }

  function openHelp(guideId) {
    activeTour?.destroy();
    search.value = '';
    selectGuide(helpGuides.find(item => item.audience === audience && item.id === guideId) || current);
    if (!dialog.open) dialog.showModal();
    search.focus({ preventScroll: true });
  }

  function visibleTarget(selector) {
    return Array.from(document.querySelectorAll(selector)).find(element => {
      const bounds = element.getBoundingClientRect();
      return !element.closest('[hidden], [inert]') && bounds.width > 0 && bounds.height > 0
        && bounds.right > 0 && bounds.left < window.innerWidth && getComputedStyle(element).visibility !== 'hidden';
    });
  }

  function escapeHtml(value) {
    return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
  }

  async function startTour() {
    if (loadingTour) return;
    const otherDialog = Array.from(document.querySelectorAll('dialog[open]')).find(element => element !== dialog);
    if (otherDialog) {
      const message = 'Tutup editor atau dialog lain terlebih dahulu untuk memulai tur. Isianmu tidak diubah oleh bantuan.';
      if (dialog.open) article.prepend(node('p', 'help-context-note', message));
      else window.toast?.(message, 'info');
      return;
    }
    loadingTour = true;
    const origin = document.activeElement;
    try {
      const { driver } = await import('driver.js');
      activeTour?.destroy();
      if (dialog.open) dialog.close();
      const steps = [];
      const seen = new Set();
      const addStep = (selector, title, description, useField = false) => {
        const target = visibleTarget(selector);
        if (!target) return;
        const element = useField ? target.closest('.field, .question-rich-text, .question-hint') || target : target;
        if (seen.has(element)) return;
        seen.add(element);
        steps.push({ element, popover: { title: escapeHtml(title), description: escapeHtml(description), side: 'bottom', align: 'start' } });
      };
      steps.push({ popover: { title: current.title, description: escapeHtml(`${current.summary} Tur ini tidak mengubah atau menyimpan data.${document.body.dataset.attempt ? ' Timer ujian tetap berjalan selama panduan terbuka.' : ''}`) } });
      addStep('.side', 'Navigasi fitur', audience === 'admin'
        ? 'Kelompok Konten mengelola bahan belajar dan ujian. Pengguna mengelola peserta serta leaderboard. Bisnis mengelola paket, transaksi, dan rancangan voucher. Buka bantuan pada setiap halaman untuk penjelasan rinci.'
        : 'Gunakan menu untuk materi, latihan, tryout, hasil, paket, transaksi, profil, dan sertifikat. Bantuan tersedia kembali di halaman yang kamu buka.');
      addStep('main .page-head', 'Tujuan dan tindakan halaman', 'Baca judul dan deskripsi untuk memastikan konteks. Tombol tambah, kembali, simpan, atau terbitkan memiliki fungsi berbeda. Panduan ini tidak menekan tombol tindakan.');
      for (const [selector, title, description] of current.fields) addStep(selector, title, description, true);
      addStep('main form[method="GET"], main .question-filter, main .content-material-filters, main .pills', 'Cari dan filter data', 'Filter mempersempit daftar yang terlihat dan tidak menghapus data. Periksa kata pencarian, bidang, topik, status, dan halaman daftar jika data belum ditemukan.');
      addStep('main .table-wrap, main .content-topic-grid, main .content-directory, main .grid-auto', 'Membaca daftar dan tindakan', 'Periksa nama, status, akses, serta keterangan sebelum membuka atau mengubah data. Jika daftar kosong, periksa filter dan siapkan data melalui tombol yang tersedia. Baca konfirmasi sebelum tindakan hapus atau nonaktifkan.');
      steps.push({ popover: { title: 'Panduan selesai', description: 'Kamu dapat mengulang tur atau membaca penjelasan lengkap dari Bantuan & Panduan. Perubahan formulir tetap perlu disimpan melalui tombol halaman. Menyelesaikan tur tidak menyimpan data atau mengumpulkan jawaban.' } });

      activeTour = driver({
        animate: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        showProgress: true,
        progressText: 'Langkah {{current}} dari {{total}}',
        nextBtnText: 'Berikutnya →',
        prevBtnText: '← Kembali',
        doneBtnText: 'Selesai',
        closeBtnLabel: 'Tutup panduan',
        allowClose: true,
        disableActiveInteraction: true,
        overlayOpacity: 0.55,
        stagePadding: 6,
        stageRadius: 12,
        popoverClass: 'help-tour',
        onPopoverRender: popover => {
          popover.wrapper.setAttribute('role', 'dialog');
          popover.wrapper.setAttribute('aria-modal', 'true');
          popover.wrapper.setAttribute('aria-label', 'Panduan interaktif halaman');
        },
        onDestroyed: () => {
          activeTour = null;
          if (origin?.isConnected && origin.getClientRects().length) origin.focus({ preventScroll: true });
          else container.querySelector('.help-launcher').focus({ preventScroll: true });
        },
        steps,
      });
      activeTour.drive();
    } catch {
      openHelp(current.id);
      article.prepend(node('p', 'help-context-note', 'Tur belum dapat dimuat. Penjelasan lengkap tetap tersedia. Periksa koneksi lalu coba tur kembali.'));
    } finally {
      loadingTour = false;
    }
  }

  function annotateFields(guideItem, scope = document) {
    for (const [selector, title, description] of guideItem.fields) {
      for (const target of scope.querySelectorAll(selector)) {
        const field = target.closest('.field, .question-rich-text, .question-hint');
        if (!field || field.querySelector('[data-help-field]')) continue;
        const detail = node('details', 'help-field-explanation');
        detail.dataset.helpField = '';
        detail.append(node('summary', '', `Penjelasan: ${title}`), node('p', '', description));
        field.append(detail);
      }
    }
  }

  const banner = container.querySelector('[data-help-banner]');
  const heading = document.querySelector('main .page-head');
  if (heading) {
    banner.querySelector('[data-help-summary]').textContent = current.summary;
    heading.after(banner);
    banner.hidden = false;
  }
  annotateFields(current);
  const inlineQuestions = document.querySelector('[data-inline-question-form]');
  if (inlineQuestions) annotateFields(helpGuides.find(item => item.id === 'admin-questions'), inlineQuestions);

  search.addEventListener('input', renderTopics);
  container.querySelector('[data-help-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
  });
  document.addEventListener('click', event => {
    const open = event.target.closest('[data-help-open], [data-help-current]');
    if (open) { event.preventDefault(); openHelp(open.dataset.helpGuide); }
    if (event.target.closest('[data-help-tour]')) { event.preventDefault(); startTour(); }
  });
  selectGuide(current);
}
