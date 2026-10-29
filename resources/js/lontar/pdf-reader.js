/* ==========================================================================
   LONTAR · pdf-reader.js
   Penampil PDF milik sendiri: tanpa tombol unduh, tanpa tombol cetak.

   Kenapa tidak pakai <object>/<embed>/<iframe> langsung ke PDF?
   Viewer bawaan browser (PDFium di Chrome, pdf.js di Firefox) selalu
   menampilkan toolbar berisi Unduh dan Cetak. Selama browser diberi PDF
   mentah, mengunduhnya tinggal satu klik. Jadi PDF-nya tidak diserahkan ke
   browser sebagai dokumen — pdf.js merender halaman ke <canvas> dan toolbar
   ditentukan di sini.

   Batasnya harus jujur disebut: pdf.js tetap menarik byte PDF ke browser untuk
   merendernya. Orang yang bertekad bisa mengambilnya lewat DevTools. Yang hilang
   adalah jalan pintas yang paling sering dipakai tanpa sengaja — tombol unduh,
   tombol cetak, dan Ctrl+S / Ctrl+P. Untuk salinan yang tetap bocor, tiap
   halaman diberi watermark identitas peserta.

   pdf.js di-import dinamis supaya chunk-nya hanya diunduh di halaman baca materi.
   ========================================================================== */

/**
 * URL worker pdf.js.
 *
 * Import harus static: suffix `?url` hanya dipahami Vite pada import statis.
 * Yang diimpor hanyalah alamat filenya — berkasnya baru diambil pdf.js saat
 * worker benar-benar dibuat, jadi halaman lain tidak ikut memuatnya.
 *
 * Dipakai `?url` (bukan `?worker`) karena pdf.js v6 mewajibkan
 * `GlobalWorkerOptions.workerSrc` berupa string, bukan objek Worker.
 */
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { bindReaderFullscreen } from './reader-fullscreen.js';

const state = {
    pdf: null,
    zoomScale: 1,
    fitWidth: true,
    page: 1,
    numPages: 0,
    baseWidth: 0,
    rendered: new Map(),
    rendering: new Set(),
observer: null,
    container: null,
    root: null,
    watermark: '',
};

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

function hydrateIcons(root = document) {
    if (window.LIcons && typeof window.LIcons.hydrate === 'function') {
        window.LIcons.hydrate(root);
    }
}

function notify(message, tone = 'info') {
    if (typeof window.toast === 'function') {
        window.toast(message, tone);
    }
}

/* ------------------------------------------------------------------ watermark */

/**
 * Watermark identitas peserta, digambar di atas tiap halaman.
 *
 * Tidak untuk menghentikan pencuri, tapi supaya salinan yang bocor bisa
 * ditelusuri balik ke pemiliknya.
 */
function drawWatermark(context, width, height, label) {
    if (!label) return;

    context.save();
    context.globalAlpha = 0.14;
    context.fillStyle = '#16324F';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.font = `700 ${Math.max(13, Math.round(width / 36))}px "Plus Jakarta Sans", system-ui, sans-serif`;

    for (let y = height * 0.12; y < height; y += height * 0.24) {
        for (let x = -width * 0.25; x < width; x += width * 0.45) {
            context.save();
            context.translate(x, y);
            context.rotate(-Math.PI / 8);
            context.fillText(label, 0, 0, width * 0.6);
            context.restore();
        }
    }

    context.restore();
}

/* -------------------------------------------------------------------- render */

/**
 * Lebar tampilan dalam piksel.
 *
 * Zoom selalu relatif terhadap **lebar saat ini**, bukan lebar intrinsik PDF.
 * PDF A4 lebarnya 595px, sedangkan tampilan awal sudah sesuaikan-lebar 1050px —
 * kalau zoom dihitung dari 595px, tombol "perbesar" justru mengecilkan halaman.
 */
function displayWidth() {
    const fit = Math.max(320, state.container.clientWidth - 56);

    return Math.round(fit * state.zoomScale);
}

/** Lebar CSS satu halaman, mengikuti lebar tampilan. */
function cssWidthFor(page) {
    const base = page.getViewport({ scale: 1 });

    // Halaman berukuran lain mengikuti skala yang sama supaya proporsinya tetap.
    return displayWidth() * (base.width / (state.baseWidth || base.width));
}

/**
 * Antrean render.
 *
 * pdf.js memproses tiap halaman sebagai operasi berat; memicu 48 render
 * bersamaan saat dokumen dibuka membuat CPU melonjak dan halaman terasa macet.
 * Antrean ini membatasi jumlah render yang jalan bersamaan.
 */
const RENDER_CONCURRENCY = 2;
let active = 0;
const queue = [];

function schedule(pageNumber) {
    queue.push(pageNumber);
    drain();
}

function drain() {
    while (active < RENDER_CONCURRENCY && queue.length > 0) {
        const pageNumber = queue.shift();

        active += 1;

        void renderPage(pageNumber).finally(() => {
            active -= 1;
            drain();
        });
    }
}

async function renderPage(pageNumber) {
    if (!state.pdf || state.rendered.has(pageNumber) || state.rendering.has(pageNumber)) {
        return;
    }

    state.rendering.add(pageNumber);

    try {
        const page = await state.pdf.getPage(pageNumber);
        const base = page.getViewport({ scale: 1 });
        const ratio = cssWidthFor(page) / base.width;
        const ratioDpr = ratio * Math.min(window.devicePixelRatio || 1, 2);

        const holder = $(`#pdfview-page-${pageNumber}`);

        if (!holder) return;

        let canvas = $('canvas', holder);

        if (!canvas) {
            holder.innerHTML = '';
            canvas = document.createElement('canvas');
            holder.prepend(canvas);
        }

        canvas.width = Math.floor(base.width * ratioDpr);
        canvas.height = Math.floor(base.height * ratioDpr);
        canvas.style.width = `${Math.floor(base.width * ratio)}px`;
        canvas.style.height = `${Math.floor(base.height * ratio)}px`;

        // Halaman dengan ukuran berbeda dari tebakan awal menyesuaikan cangkangnya
        // sendiri; yang lain tetap memakai perkiraan yang sudah dipatok.
        if (Math.abs(base.width - state.baseWidth) > 1) {
            holder.style.width = `${Math.floor(base.width * ratio)}px`;
            holder.style.aspectRatio = String(base.height / base.width);
        }

        const context = canvas.getContext('2d', { alpha: false });

        await page.render({ canvasContext: context, viewport: page.getViewport({ scale: ratioDpr }) }).promise;

        drawWatermark(context, canvas.width, canvas.height, state.watermark);

        if (holder.dataset.textLayer !== '1') {
            holder.dataset.textLayer = '1';
            await paintTextLayer(holder, page, ratio);
        }

        state.rendered.set(pageNumber, canvas);
    } catch (error) {
        console.error('[pdf-reader] gagal merender halaman', pageNumber, error);
    } finally {
        state.rendering.delete(pageNumber);
    }
}

/**
 * Lapisan teks di atas canvas.
 *
 * Tanpa ini peserta tidak bisa menyorot atau mencari kata di dalam PDF —
 * turunannya pengalaman baca PDF jadi jauh lebih buruk.
 */
async function paintTextLayer(holder, page, ratio) {
    const content = await page.getTextContent();
    const layer = document.createElement('div');
    layer.className = 'pdfview__text';

    const height = page.getViewport({ scale: 1 }).height;

    for (const item of content.items) {
        if (!item.str) continue;

        const span = document.createElement('span');
        span.textContent = item.str;

        const fontSize = Math.hypot(item.transform[2], item.transform[3]) * ratio;

        span.style.left = `${item.transform[4] * ratio}px`;
        span.style.top = `${(height - item.transform[5]) * ratio - fontSize}px`;
        span.style.fontSize = `${fontSize}px`;
        span.style.transform = `rotate(${Math.atan2(item.transform[1], item.transform[0])}rad)`;

        layer.appendChild(span);
    }

    holder.appendChild(layer);
}

async function renderCurrentAndNext() {
    if (!state.pdf) return;

    schedule(state.page);

    if (state.page < state.numPages) {
        schedule(state.page + 1);
    }
}

/**
 * Render halaman saat mendekati viewport.
 *
 * Dua hal yang wajib beres di sini:
 *
 * 1. **Cangkang halaman harus punya tinggi sejak awal.** Kalau masih kosong,
 *    semuanya menumpuk di koordinat yang sama sehingga semua dianggap
 *    "terlihat" — dokumen 48 halaman langsung dirender semua sekaligus.
 *    Tinggi diambil dari rasio halaman pertama (lihat `applyPageRatio`).
 * 2. **Hanya halaman yang dibaca.** Render berat, jadi antrean dibatasi dua
 *    render berjalan bersamaan; sisanya menunggu giliran.
 */
function observePages() {
    state.observer?.disconnect();
    state.observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;

            const number = Number(entry.target.dataset.page);

            if (number) schedule(number);
        }
    }, {
        root: state.container,
        rootMargin: '500px 0px 500px 0px',
        threshold: 0,
    });

    $$('.pdfview__page', state.container).forEach((node) => state.observer.observe(node));
}

/**
 * Beri semua cangkang halaman rasio ukuran yang sama dengan halaman pertama.
 *
 * Hampir semua materi PDF memakai ukuran yang sama, jadi menaksir dari halaman
 * pertama membuat tata letak langsung benar — tidak ada lompatan saat halaman
 * berikutnya selesai dirender.
 */
function applyPageRatio(page) {
    const base = page.getViewport({ scale: 1 });

    state.baseWidth = base.width;
    state.container.style.setProperty('--pdf-page-ratio', String(base.height / base.width));
    state.container.classList.add('pdfview__pages--sized');

    applyPageWidth();
}

/**
 * Lebar cangkang halaman, mengikuti mode yang sedang aktif.
 *
* Disamakan dengan lebar canvas yang akan dipakai, supaya halaman yang sudah
     * dirender dan yang masih kosong mengisi ruang yang persis sama.
 */
function applyPageWidth() {
    state.container.style.setProperty('--pdf-page-width', `${displayWidth()}px`);
}

function resetPages() {
    state.rendered.clear();
    queue.length = 0;

    applyPageWidth();

    $$('.pdfview__page', state.container).forEach((node) => {
        node.innerHTML = '<span class="pdfview__page-no"></span><span class="pdfview__spinner"></span>';
        node.dataset.textLayer = '';
        $('.pdfview__page-no', node).textContent = node.dataset.page;
    });

    observePages();
    schedule(state.page);
}

/* ------------------------------------------------------------------ toolbar */

function syncToolbar() {
    const input = $('#pdfview-page-input');
    const total = $('#pdfview-total');

    if (input) input.value = String(state.page);
    if (total) total.textContent = String(state.numPages);

const prev = $('#pdfview-prev');
    const next = $('#pdfview-next');

    if (prev) prev.disabled = state.page <= 1;
    if (next) next.disabled = state.page >= state.numPages;
}

/**
 * Lompat ke halaman tertentu.
 *
 * Lompatannya **langsung**, bukan halus. Dengan `behavior: 'smooth'`,
 * melompat dari halaman 1 ke 30 berarti menganimasikan 29 tinggi halaman —
 * butuh puluhan detik dan terasa seperti macet, bukan seperti navigasi.
 * Tiap halaman sendiri sudah sama tinggi, jadi lompatan instan terbaca wajar.
 */
function goTo(pageNumber) {
    const target = Math.min(Math.max(1, pageNumber), state.numPages);

    if (target === state.page) return;

    state.page = target;
    state.pendingScroll = target;
    syncToolbar();

    $(`#pdfview-page-${target}`)?.scrollIntoView({ block: 'start' });
}

function zoomBy(factor) {
    // Keluar dari mode sesuaikan-lebar dengan mengambil lebar saat ini
    // sebagai titik acuan, lalu melanjutkan dari sana.
    const next = Math.min(Math.max(0.35, state.zoomScale * factor), 3);

    if (next === state.zoomScale) return;

    state.zoomScale = next;
    state.fitWidth = false;

    $('#pdfview-fit')?.classList.remove('is-active');

    resetPages();
    notify(`Zoom ${Math.round(next * 100)}%`);
}

function toggleFit() {
    state.fitWidth = !state.fitWidth;

    if (state.fitWidth) {
        state.zoomScale = 1;
    }

    $('#pdfview-fit')?.classList.toggle('is-active', state.fitWidth);

    resetPages();
}

function wireToolbar() {
    $('#pdfview-prev')?.addEventListener('click', () => goTo(state.page - 1));
    $('#pdfview-next')?.addEventListener('click', () => goTo(state.page + 1));
    $('#pdfview-zoom-in')?.addEventListener('click', () => zoomBy(1.2));
    $('#pdfview-zoom-out')?.addEventListener('click', () => zoomBy(1 / 1.2));
    $('#pdfview-fit')?.addEventListener('click', toggleFit);

$('#pdfview-page-input')?.addEventListener('change', (event) => {
        const wanted = Number.parseInt(event.target.value, 10) || 1;

        // Dikembalikan ke nilai sebenarnya supaya angka di kotak tidak
        // menyesatkan saat target di luar jangkauan.
        goTo(wanted);
    });

    /*
     * Navigasi papan ketik.
     *
     * Tiga syarat sebelum tombol dibaca, karena tanpa itu panah kiri/kanan
     * justru merusak pengalaman:
     *
     * 1. Fokus harus ada di dalam viewer — di halaman ini ada kotak pencarian di
     *    navbar dan form lain; menekan panah di sana harus mengubah angka atau
     *    menyorot teks, bukan membalik halaman.
     * 2. Jangan menabrak Caps Lock / Shift / Alt / Ctrl — itu kombinasi
     *    milik browser dan sistem operasi.
     * 3. Kalau ada teks tersorot, biarkan panah memindahkan kursor.
     *
     * `ArrowDown`/`ArrowUp` sengaja tidak ditangani: area baca sudah bisa
     * digulir sendiri, sedangkan panah atas/bawah yang dipaksa pindah halaman
     * terasa melompat.
     */
    document.addEventListener('keydown', (event) => {
        if (!state.root?.isConnected || !state.numPages) return;

        if (event.altKey || event.ctrlKey || event.metaKey) return;

        // Cakupannya seluruh viewer — termasuk toolbar-nya, supaya panah tetap
        // berfungsi setelah peserta menekan tombol halaman.
        if (!state.root.contains(event.target)) return;

        const tag = event.target instanceof HTMLElement ? event.target.tagName : '';

        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;

        if (window.getSelection()?.toString()) return;

        const nextKeys = ['ArrowRight', 'PageDown'];
        const prevKeys = ['ArrowLeft', 'PageUp'];

        if (nextKeys.includes(event.key)) {
            event.preventDefault();
            goTo(state.page + 1);
        } else if (prevKeys.includes(event.key)) {
            event.preventDefault();
            goTo(state.page - 1);
        } else if (event.key === 'Home') {
            event.preventDefault();
            goTo(1);
        } else if (event.key === 'End') {
            event.preventDefault();
            goTo(state.numPages);
        }
    });

/*
     * Nomor halaman mengikuti posisi gulir.
     *
     * Dua hal yang pernah bikin ini salah:
     *
     * 1. Menghitung **semua** cangkang halaman, bukan hanya yang sudah
     *    ter-render. Kalau hanya yang ter-render, melompat ke halaman yang
     *    belum dirender membuat nomor melompat balik — peserta menekan "halaman
     *    40" lalu nomornya menyatakan 2.
     * 2. Memakai `getBoundingClientRect`, bukan `offsetTop`: canvas berada di
     *    dalam `.pdfview__page` yang `position: relative`, jadi `offsetTop`
     *    canvas relatif ke cangkangnya sendiri — selalu mendekati nol.
     *
     * Cangkang yang belum ter-render tetap punya tinggi benar karena sudah
     * diberi rasio A4, jadi posisinya layak dipakai.
     */
    let scrollTick = false;

    state.container.addEventListener('scroll', () => {
        if (scrollTick) return;

        scrollTick = true;

        window.requestAnimationFrame(() => {
            scrollTick = false;

            // Garis baca: 60px dari atas area baca.
            const line = state.container.getBoundingClientRect().top + 60;
            let closest = 1;

            for (const holder of $$('.pdfview__page', state.container)) {
                // Cangkang berurutan ke bawah, jadi yang pertama melewati garis
                // ini sudah cukup — sisanya pasti lebih jauh di bawah.
                if (holder.getBoundingClientRect().top > line) break;

                closest = Number(holder.dataset.page) || closest;
            }

            if (closest !== state.page) {
                state.page = closest;
                syncToolbar();
            }
        });
    }, { passive: true });

let resizeTimer;

    function resizeReader() {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            if (state.pdf) resetPages();
        }, 220);
    }

    bindReaderFullscreen(state.root, $('#pdfview-fullscreen'), {
        onChange: () => {
            hydrateIcons($('#pdfview-fullscreen'));
            resizeReader();
        },
        onError: notify,
    });

    // Berlaku juga saat bukan mode sesuaikan-lebar: zoom dihitung relatif
    // terhadap lebar saat ini, jadi perubahan lebar jendela harus
    // menggambar ulang supaya proporsinya tetap benar.
    window.addEventListener('resize', resizeReader);
}

/* ---------------------------------------------------------------------- init */

function buildPages(count) {
    state.container.innerHTML = Array.from({ length: count }, (_, index) => {
        const number = index + 1;

        return `
          <div class="pdfview__page" id="pdfview-page-${number}" data-page="${number}">
            <span class="pdfview__page-no">${number}</span>
            <span class="pdfview__spinner"></span>
          </div>`;
    }).join('');
}

function showFailure(message) {
    state.container.innerHTML = `
      <div class="pdfview__state">
        <span class="pdfview__state-icon pdfview__state-icon--bad"><svg data-icon="alert"></svg></span>
        <strong style="color:var(--ink);font-family:var(--font-display);margin-top:1rem">Dokumen tidak bisa dibuka</strong>
        <p class="text-sm text-soft" style="margin-top:.4rem;max-width:46ch">${message}</p>
      </div>`;

    hydrateIcons(state.container);
}

async function init() {
    const root = $('[data-pdf-reader]');

    if (!root) return;

state.container = $('#pdfview-pages', root);
    state.root = root;
    state.watermark = root.dataset.watermark ?? '';
    state.page = Number.parseInt(root.dataset.startPage ?? '1', 10) || 1;

    if (!state.container) return;

    hydrateIcons(root);
    wireToolbar();
    syncToolbar();

    try {
        const pdfjs = await import('pdfjs-dist');

        pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

        state.pdf = await pdfjs.getDocument({ url: root.dataset.src }).promise;
        state.numPages = state.pdf.numPages;

// Rasio halaman pertama menentukan tinggi semua cangkang, sehingga
        // IntersectionObserver bisa menentukan halaman mana yang terlihat.
        applyPageRatio(await state.pdf.getPage(1));

        buildPages(state.numPages);
        syncToolbar();

        $('#pdfview-total').textContent = String(state.numPages);

        observePages();
        await renderCurrentAndNext();

        root.dataset.pdfReady = '1';
    } catch (error) {
        console.error('[pdf-reader]', error);

        showFailure(
            'Berkas PDF mungkin rusak atau formatnya tidak didukung browser ini. Coba muat ulang halaman, atau hubungi admin Ganesha Nusa.',
        );
    }
}

/**
 * Matikan pintasan unduh dan cetak bawaan browser.
 *
 * Penghadang, bukan benteng: "Save page as" tetap menyimpan halaman yang sudah
 * dirender, dan orang yang sengaja memakai DevTools tetap bisa. Yang ditahan
 * di sini adalah jalan pintas yang paling sering terpakai tanpa sengaja.
 */
function guardShortcuts() {
    if (!$('[data-pdf-reader]')) return;

    document.addEventListener('keydown', (event) => {
        const pressed = event.ctrlKey || event.metaKey;

        if (pressed && ['s', 'p'].includes(event.key.toLowerCase())) {
            event.preventDefault();
            notify('Unduh dan cetak dinonaktifkan untuk materi ini.');
        }
    });

    document.addEventListener('contextmenu', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-pdf-reader] canvas')) {
            event.preventDefault();
        }
    });
}

function start() {
    guardShortcuts();
    void init();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
