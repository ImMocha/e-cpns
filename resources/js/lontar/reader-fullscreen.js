export function bindReaderFullscreen(root, button, { onChange = () => {}, onError = () => {} } = {}) {
  if (!button) return;
  const doc = root.ownerDocument;
  let pending = false;

  function sync() {
    const active = (doc.fullscreenElement || doc.webkitFullscreenElement) === root;
    const label = active ? 'Keluar layar penuh' : 'Layar penuh';
    root.classList.toggle('is-fullscreen', active);
    button.classList.toggle('is-active', active);
    button.setAttribute('aria-pressed', String(active));
    button.setAttribute('aria-label', label);
    button.title = label;
    const icon = button.querySelector('svg');
    if (icon) icon.setAttribute('data-icon', active ? 'close' : 'maximize');
    onChange(active);
  }

  async function toggle() {
    if (pending) return;
    pending = true;
    try {
      if ((doc.fullscreenElement || doc.webkitFullscreenElement) === root) {
        const exit = doc.exitFullscreen || doc.webkitExitFullscreen;
        if (!exit) throw new Error('Fullscreen unavailable');
        await exit.call(doc);
      } else {
        const enter = root.requestFullscreen || root.webkitRequestFullscreen;
        if (!enter) throw new Error('Fullscreen unavailable');
        await enter.call(root);
      }
    } catch {
      onError('Layar penuh belum dapat dibuka. Pastikan browser mengizinkan mode layar penuh, lalu coba lagi.');
    } finally {
      pending = false;
      sync();
    }
  }

  button.addEventListener('click', toggle);
  doc.addEventListener('fullscreenchange', sync);
  doc.addEventListener('webkitfullscreenchange', sync);
  return toggle;
}
