function syncPicker(picker) {
  const input = picker.querySelector('input[type="file"]');
  const clear = picker.querySelector('[data-clear-file]');
  clear.hidden = input.files.length === 0;

  const list = picker.querySelector('[data-selected-files]');
  if (!list) return;

  list.hidden = input.files.length === 0;
  list.replaceChildren(...Array.from(input.files, (file, index) => {
    const item = document.createElement('li');
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn--ghost btn--sm max-w-full whitespace-normal break-all';
    remove.dataset.removeSelectedFile = index;
    remove.textContent = 'Hapus ' + file.name;
    item.append(remove);
    return item;
  }));
}

function notifyChange(input) {
  input.dispatchEvent(new Event('change', { bubbles: true }));
}

document.addEventListener('change', event => {
  const picker = event.target.closest('[data-file-picker]');
  if (!picker) return;

  const input = picker.querySelector('input[type="file"]');
  const removeStored = picker.querySelector('[data-remove-stored-file]');
  if (event.target === input) {
    if (input.files.length && removeStored) removeStored.checked = false;
    syncPicker(picker);
  } else if (event.target === removeStored && removeStored.checked && input.files.length) {
    input.value = '';
    notifyChange(input);
  }
});

document.addEventListener('click', event => {
  const button = event.target.closest('[data-clear-file], [data-remove-selected-file]');
  if (!button) return;

  const input = button.closest('[data-file-picker]').querySelector('input[type="file"]');
  if (input.disabled || !input.files.length) return;

  if (button.hasAttribute('data-clear-file')) {
    input.value = '';
  } else {
    const remaining = new DataTransfer();
    Array.from(input.files).forEach((file, index) => {
      if (index !== Number(button.dataset.removeSelectedFile)) remaining.items.add(file);
    });
    input.files = remaining.files;
  }
  notifyChange(input);
  input.focus();
});

document.addEventListener('reset', event => {
  queueMicrotask(() => event.target.querySelectorAll('[data-file-picker]').forEach(syncPicker));
});

window.addEventListener('pageshow', () => document.querySelectorAll('[data-file-picker]').forEach(syncPicker));
