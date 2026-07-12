// Small DOM helpers, modal and toast primitives.

export function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') node.className = v;
    else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
    else if (v !== null && v !== undefined) node.setAttribute(k, v);
  }
  for (const c of children.flat()) {
    if (c === null || c === undefined || c === false) continue;
    node.append(c.nodeType ? c : document.createTextNode(c));
  }
  return node;
}

// Turn text that may contain literal "<br>" line breaks (card text from the
// CSV) into safe DOM nodes — text nodes plus real <br> elements — with no
// HTML parsing, so no other substring of the text can ever be interpreted
// as markup.
export function textWithBreaks(text) {
  const parts = String(text).split('<br>');
  const nodes = [];
  parts.forEach((part, i) => {
    if (i > 0) nodes.push(el('br'));
    if (part) nodes.push(document.createTextNode(part));
  });
  return nodes;
}

export function clear(node) { while (node.firstChild) node.removeChild(node.firstChild); return node; }

let modalStack = [];
export function openModal(content, { onClose = null, closable = true } = {}) {
  const root = document.getElementById('modal-root');
  const overlay = el('div', { class: 'overlay' });
  const box = el('div', { class: 'modal' });
  box.append(content);
  overlay.append(box);
  if (closable) {
    overlay.addEventListener('mousedown', e => { if (e.target === overlay) close(); });
  }
  root.append(overlay);
  const entry = { overlay, onClose };
  modalStack.push(entry);
  function close() { closeModal(entry); }
  return { close, box };
}
export function closeModal(entry = null) {
  if (!entry) entry = modalStack[modalStack.length - 1];
  if (!entry) return;
  modalStack = modalStack.filter(e => e !== entry);
  entry.overlay.remove();
  entry.onClose?.();
}
export function closeAllModals() { while (modalStack.length) closeModal(); }

export function toast(text, kind = '') {
  const root = document.getElementById('toast-root');
  const t = el('div', { class: `toast ${kind}` }, text);
  root.append(t);
  setTimeout(() => t.remove(), 4000);
  while (root.children.length > 4) root.firstChild.remove();
}

export function banner(text) {
  const b = document.getElementById('turn-banner');
  b.textContent = text;
  b.classList.remove('hidden');
  b.style.animation = 'none';
  void b.offsetWidth; // restart animation
  b.style.animation = '';
  clearTimeout(b._t);
  b._t = setTimeout(() => b.classList.add('hidden'), 2100);
}

// d10 roll overlay; resolves when the animation is done.
export function showDice(roll, need, ok, label) {
  return new Promise(resolve => {
    const o = document.getElementById('dice-overlay');
    clear(o);
    o.classList.remove('hidden');
    o.append(
      el('div', { class: 'die' }, String(roll)),
      el('div', { class: 'die-caption' },
        label, el('br'),
        'rolled ', el('b', {}, String(roll)), ` — needed ≤ ${need}`, el('br'),
        el('b', { class: ok ? 'ok' : 'no' }, ok ? 'LAUNCH SUCCESS' : 'LAUNCH FAILURE')),
    );
    setTimeout(() => { o.classList.add('hidden'); resolve(); }, 2100);
  });
}
