const settings = document.querySelector('[data-typography-settings]');

if (settings) {
    const heading = settings.querySelector('[name="heading_font"]');
    const body = settings.querySelector('[name="body_font"]');
    const sample = settings.querySelector('[data-font-sample]');
    const status = settings.querySelector('[data-font-loading]');
    const loaded = new Set(['outfit', 'plus-jakarta-sans']);
    let revision = 0;

    function stack(select) {
        const option = select.selectedOptions[0];
        const fallback = option.dataset.category === 'Serif' ? 'Georgia, serif' : 'ui-sans-serif, system-ui, sans-serif';
        return `"${option.dataset.family}", ${fallback}`;
    }

    function update() {
        const currentRevision = ++revision;
        const fonts = [...new Set([heading.value, body.value])].filter(font => !loaded.has(font));
        if (fonts.length) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.bunny.net/css?family=${fonts.map(font => `${font}:400,700`).join('|')}&display=swap`;
            status.textContent = 'Memuat font preview…';
            link.onload = async () => {
                fonts.forEach(font => loaded.add(font));
                try {
                    await Promise.all([
                        document.fonts.load(`700 20px "${heading.selectedOptions[0].dataset.family}"`),
                        document.fonts.load(`400 14px "${body.selectedOptions[0].dataset.family}"`),
                    ]);
                    if (currentRevision === revision) status.textContent = 'Preview siap. Perubahan berlaku setelah disimpan.';
                } catch {
                    if (currentRevision === revision) status.textContent = 'Font belum dapat dimuat. Preview menggunakan font cadangan.';
                }
            };
            link.onerror = () => {
                link.remove();
                if (currentRevision === revision) status.textContent = 'Font belum dapat dimuat. Periksa koneksi internet untuk melihat preview yang akurat.';
            };
            document.head.appendChild(link);
        } else {
            status.textContent = 'Preview siap. Perubahan berlaku setelah disimpan.';
        }

        settings.querySelectorAll('[data-font-preview]').forEach(preview => {
            const enabled = settings.querySelector(`[data-font-scope="${preview.dataset.fontPreview}"]`).checked;
            preview.style.setProperty('--font-display', enabled ? stack(heading) : '"Outfit", ui-sans-serif, system-ui, sans-serif');
            preview.style.setProperty('--font-body', enabled ? stack(body) : '"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif');
            preview.style.fontFamily = 'var(--font-body)';
            preview.querySelector('[data-font-status]').textContent = enabled ? 'Font pilihan' : 'Font bawaan';
            preview.querySelector('[data-font-preview-text]').textContent = sample.value || 'Contoh teks pembelajaran dan latihan CPNS.';
        });
    }

    settings.querySelectorAll('[data-font-select], [data-font-scope]').forEach(input => input.addEventListener('change', update));
    sample.addEventListener('input', () => {
        settings.querySelectorAll('[data-font-preview-text]').forEach(element => {
            element.textContent = sample.value || 'Contoh teks pembelajaran dan latihan CPNS.';
        });
    });
    settings.querySelector('[data-font-reset]').addEventListener('click', () => {
        heading.value = 'outfit';
        body.value = 'plus-jakarta-sans';
        update();
    });
    update();
}
