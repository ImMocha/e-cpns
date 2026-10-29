const zones = {
  'Asia/Jakarta': ['Asia/Jakarta', 'WIB'],
  'Asia/Pontianak': ['Asia/Jakarta', 'WIB'],
  'Asia/Makassar': ['Asia/Makassar', 'WITA'],
  'Asia/Ujung_Pandang': ['Asia/Makassar', 'WITA'],
  'Asia/Jayapura': ['Asia/Jayapura', 'WIT'],
};
const formatters = new Map();

export function resolveTimeZone(browserZone) {
  return Object.hasOwn(zones, browserZone) ? zones[browserZone] : zones['Asia/Jakarta'];
}

export function detectTimeZone() {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch {
    return undefined;
  }
}

export function formatLocalTime(value, format = 'd M Y H:i', browserZone) {
  if (typeof value !== 'string' || value.trim() === '') return null;
  const date = new Date(value);
  if (!Number.isFinite(date.getTime())) return null;
  const [timeZone, label] = resolveTimeZone(browserZone);
  if (!formatters.has(timeZone)) {
    formatters.set(timeZone, {
      date: new Intl.DateTimeFormat('id-ID', {
        timeZone, year: 'numeric', month: 'long', day: '2-digit', weekday: 'long',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
      }),
      month: new Intl.DateTimeFormat('id-ID', { timeZone, month: '2-digit' }),
    });
  }
  const formatter = formatters.get(timeZone);
  const parts = Object.fromEntries(formatter.date.formatToParts(date).map(part => [part.type, part.value]));
  const month = formatter.month.format(date);
  const tokens = {
    d: parts.day, j: String(Number(parts.day)), m: month, n: String(Number(month)),
    Y: parts.year, y: parts.year.slice(-2), H: parts.hour, i: parts.minute, s: parts.second,
    l: parts.weekday, F: parts.month, M: parts.month.slice(0, 3),
  };
  return [...format].map(token => tokens[token] ?? token).join('') + (format.includes('H') ? ` ${label}` : '');
}

export function localizeTimes(scope, browserZone = detectTimeZone()) {
  const elements = [...scope.querySelectorAll('[data-local-time]')];
  if (scope.matches?.('[data-local-time]')) elements.unshift(scope);
  for (const element of elements) {
    const formatted = formatLocalTime(element.getAttribute('datetime'), element.dataset.timeFormat, browserZone);
    if (formatted !== null && element.textContent !== formatted) element.textContent = formatted;
  }
}

if (typeof document !== 'undefined') {
  localizeTimes(document);
  new MutationObserver(records => {
    for (const record of records) {
      for (const node of record.addedNodes) {
        if (node.nodeType === 1) localizeTimes(node);
      }
    }
  }).observe(document.body, { childList: true, subtree: true });
}
