const documents = new Map(
    [...document.querySelectorAll('dialog[data-legal-document]')]
        .map(dialog => [dialog.dataset.legalDocument, dialog]),
);
let returnFocus = null;

function openDocument(name, opener = null) {
    const dialog = documents.get(name);
    if (!dialog) return;
    const current = [...documents.values()].find(item => item.open);
    if (current === dialog) return;
    if (!current) returnFocus = opener;
    current?.close();
    dialog.querySelector('.legal-dialog__body').scrollTop = 0;
    dialog.showModal();
    document.documentElement.classList.add('has-legal-dialog');
}

document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const opener = event.target.closest('[data-legal-open]');
    if (opener && documents.has(opener.dataset.legalOpen)) {
        event.preventDefault();
        openDocument(opener.dataset.legalOpen, opener);
        return;
    }
    const closer = event.target.closest('[data-legal-close]');
    closer?.closest('dialog[data-legal-document]')?.close();
});

for (const dialog of documents.values()) {
    dialog.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const focusable = [...dialog.querySelectorAll('a[href], button:not([disabled]), [tabindex="0"]')];
        const first = focusable[0];
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        if ([...documents.values()].some(item => item.open)) return;
        document.documentElement.classList.remove('has-legal-dialog');
        if (returnFocus?.isConnected) returnFocus.focus();
    });
}

const requestedDocument = {
    '#syarat-ketentuan': 'terms',
    '#kebijakan-privasi': 'privacy',
}[window.location.hash];
if (requestedDocument) openDocument(requestedDocument);
