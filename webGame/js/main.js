// Space Agency Race — app orchestration.
import { loadCards, CARDS, cardOf, cidOf, hasTag, NODES, TW_CYCLE, twCost, handLimit,
         eventId, basicCost, craftEngine, craftPayload, tankRange, craftCards } from './data.js';
import { api, gameCall, session, ApiError } from './api.js';
import { el, clear, openModal, closeModal, closeAllModals, toast, banner, showDice } from './ui.js';
import { renderCard, zoomCard, hintFor } from './cards.js';
import { renderBoard } from './board.js';
import { openBuilder, openPlanner } from './planner.js';

const $ = id => document.getElementById(id);

const st = {
  g: null,            // filtered game state
  version: 0,
  lastLogSeq: 0,
  board: null,
  polling: null,
  planningSel: new Set(),
  busy: false,
  gameOverShown: false,
  wakeLock: null,
  autoFsDone: false,
};

const isMobile = () => matchMedia('(max-width: 860px)').matches;
const wantsFullscreen = () =>
  !matchMedia('(display-mode: standalone)').matches && matchMedia('(pointer: coarse)').matches;

// Keep the screen awake during a game (supported on Android + iOS 16.4+).
// The OS can release the lock at any time (screen off, tab hidden, fullscreen
// change), so we re-acquire it on visibility, on release, and on every tap.
async function keepAwake() {
  try {
    if (!navigator.wakeLock || st.wakeLock) return;
    st.wakeLock = await navigator.wakeLock.request('screen');
    st.wakeLock.addEventListener('release', () => { st.wakeLock = null; });
  } catch { st.wakeLock = null; /* not critical */ }
}

// Enter device fullscreen. MUST be called from within a user gesture, so we
// invoke it synchronously from tap handlers (before any await) rather than
// automatically at load, which browsers reject.
function goFullscreen() {
  const root = document.documentElement;
  const req = root.requestFullscreen || root.webkitRequestFullscreen
    || root.mozRequestFullScreen || root.msRequestFullscreen;
  if (!req || document.fullscreenElement || document.webkitFullscreenElement) return;
  try { Promise.resolve(req.call(root)).catch(() => {}); } catch { /* unsupported */ }
}
// On a phone, take fullscreen when the user starts/enters a game.
function wantFullscreen() { if (wantsFullscreen()) goFullscreen(); }

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    if (st.g) keepAwake();
    if (st.g) poll(true).catch(() => {});
  }
});
// Some devices drop the wake lock across a fullscreen transition — re-grab it.
document.addEventListener('fullscreenchange', () => { if (st.g) keepAwake(); });
// Any in-game tap re-establishes the wake lock (and, once, fullscreen). This is
// the reliable path on Android: the first tap after entering a game promotes it
// to fullscreen even though auto-fullscreen at load is not permitted.
document.addEventListener('pointerdown', () => {
  if (!st.g) return;
  keepAwake();
  if (!st.autoFsDone && wantsFullscreen()) { st.autoFsDone = true; goFullscreen(); }
}, true);

// ---------------------------------------------------------------- lobby

function showLobby() {
  $('screen-lobby').classList.remove('hidden');
  $('screen-game').classList.add('hidden');
  const box = $('lobby-content');
  clear(box);

  let tab = 'create';
  const tabs = el('div', { class: 'lobby-tabs' });
  const form = el('div', { class: 'lobby-form' });
  const mk = (id, label) => el('button', { class: id === tab ? 'active' : '', onclick: () => { tab = id; render(); } }, label);

  function render() {
    clear(tabs);
    tabs.append(mk('create', 'Create online game'), mk('join', 'Join with code'), mk('hotseat', 'Hot-seat (1 device)'));
    clear(form);
    if (tab === 'create') {
      const name = el('input', { placeholder: 'Your agency director name', maxlength: 20 });
      form.append(el('label', {}, 'Name'), name,
        el('button', { class: 'btn', onclick: async () => {
          wantFullscreen();
          try {
            const r = await api('create', { name: name.value.trim(), mode: 'online' });
            session.save(r.room, r.token, 'online');
            enterGame();
          } catch (e) { toast(e.message, 'bad'); }
        } }, 'Create room'),
        el('p', { class: 'help-note' }, 'You get a 5-letter room code to share. 2–4 players; the game starts when the host presses Start.'));
    } else if (tab === 'join') {
      const code = el('input', { placeholder: 'ROOM CODE', maxlength: 5, style: 'text-transform:uppercase; letter-spacing:4px;' });
      const name = el('input', { placeholder: 'Your name', maxlength: 20 });
      form.append(el('label', {}, 'Room code'), code, el('label', {}, 'Name'), name,
        el('button', { class: 'btn', onclick: async () => {
          wantFullscreen();
          try {
            const r = await api('join', { room: code.value.trim().toUpperCase(), name: name.value.trim() });
            session.save(r.room, r.token, 'online');
            enterGame();
          } catch (e) { toast(e.message, 'bad'); }
        } }, 'Join game'));
    } else {
      const inputs = [0, 1, 2, 3].map(i => el('input', { placeholder: `Player ${i + 1}${i > 1 ? ' (optional)' : ''}`, maxlength: 20 }));
      form.append(el('label', {}, 'Players (share this device)'), ...inputs,
        el('button', { class: 'btn', onclick: async () => {
          const names = inputs.map(i => i.value.trim()).filter(Boolean);
          if (names.length < 2) return toast('Enter at least 2 player names', 'bad');
          wantFullscreen();
          try {
            const r = await api('create', { mode: 'hotseat', names });
            session.save(r.room, r.token, 'hotseat');
            enterGame();
          } catch (e) { toast(e.message, 'bad'); }
        } }, 'Start hot-seat setup'),
        el('p', { class: 'help-note' }, 'All players share this screen and take turns with the device.'));
    }
  }
  render();
  box.append(tabs, form);

  if (session.room) {
    box.append(el('div', { style: 'margin-top:16px; display:flex; gap:8px; align-items:center;' },
      el('button', { class: 'btn gold', onclick: () => { wantFullscreen(); enterGame(); } }, `Rejoin ${session.room}`),
      el('button', { class: 'btn ghost', onclick: () => { session.clear(); showLobby(); } }, 'Forget')));
  }

  // Fullscreen tips for phones
  const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
  const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
  if (!standalone && isMobile()) {
    box.append(el('p', { class: 'help-note', style: 'margin-top:14px;' }, isIOS
      ? '📱 For fullscreen play: tap the Share button in Safari and choose "Add to Home Screen", then launch the game from that icon.'
      : '📱 Tip: use the ⛶ button in-game (or install the app from the browser menu) for fullscreen play.'));
  }
}

function showWaitingRoom() {
  $('screen-lobby').classList.remove('hidden');
  $('screen-game').classList.add('hidden');
  const box = $('lobby-content');
  clear(box);
  const g = st.g;
  box.append(
    el('p', { class: 'help-note', style: 'text-align:center;' }, 'Share this room code:'),
    el('div', { class: 'room-code' }, g.room),
    el('div', { class: 'lobby-players' },
      g.players.map(p => el('div', { class: 'lobby-player' },
        el('span', { class: 'dot', style: `background:${p.color}` }),
        el('span', {}, p.name), p.isYou ? el('span', { class: 'pp-you' }, 'YOU') : ''))),
  );
  const isHost = g.players[0]?.isYou;
  if (isHost) {
    box.append(el('button', { class: 'btn gold', disabled: g.players.length < 2 ? '' : null,
      onclick: async () => {
        wantFullscreen();
        try { await gameCall('start'); poll(true); } catch (e) { toast(e.message, 'bad'); }
      } }, g.players.length < 2 ? 'Waiting for players…' : `Start game (${g.players.length} players)`));
  } else {
    box.append(el('p', { class: 'help-note', style: 'text-align:center;' }, 'Waiting for the host to start…'));
  }
  box.append(el('button', { class: 'btn ghost', style: 'margin-top:10px;',
    onclick: () => { session.clear(); stopPolling(); showLobby(); } }, 'Leave'));
}

// ---------------------------------------------------------------- polling

async function enterGame() {
  stopPolling();
  keepAwake();
  st.version = 0; st.lastLogSeq = 0; st.gameOverShown = false; st.autoFsDone = false;
  try { await poll(true); } catch (e) {
    // Only a definitive rejection ends the session (bad room code, room gone,
    // not a member). A busy or briefly broken server must never log the
    // player out — in hot-seat mode that would lose the host token for good.
    if (e instanceof ApiError && [400, 401, 403, 404].includes(e.status)) {
      toast(e.message, 'bad');
      session.clear(); showLobby(); return;
    }
    toast(`${e.message} — retrying…`, 'bad');
  }
  st.pollBusy = false; st.pollFails = 0; st.pollSkip = 0;
  st.polling = setInterval(pollTick, 2200);
}
function stopPolling() { if (st.polling) clearInterval(st.polling); st.polling = null; }

// One poll at a time, and back off when the server is failing: a slow or
// overloaded host must not accumulate a queue of overlapping state requests
// (each stuck request occupies a PHP process on the server — enough of them
// takes the whole site down on shared hosting).
async function pollTick() {
  if (st.pollBusy) return;
  if (st.pollSkip > 0) { st.pollSkip--; return; }
  st.pollBusy = true;
  try {
    await poll();
    st.pollFails = 0;
  } catch {
    st.pollFails++;
    st.pollSkip = Math.min(2 ** st.pollFails, 16) - 1; // 4.4s → 8.8s → … → ~35s
  } finally {
    st.pollBusy = false;
  }
}

async function poll(force = false) {
  const r = await gameCall('state', { since: force ? 0 : st.version });
  if (r.unchanged) return;
  applyState(r);
}

function applyState(r) {
  const prev = st.g;
  st.version = r.version;
  st.g = r.state;
  const g = st.g;
  if (g.status === 'lobby') { showWaitingRoom(); return; }
  $('screen-lobby').classList.add('hidden');
  $('screen-game').classList.remove('hidden');
  animateNewLog(prev);
  renderGame();
}

// whose input is needed right now (drives hand display in hot-seat)
function activeSeat() {
  const g = st.g;
  if (!g) return null;
  if (g.pending) return g.mySeats.includes(g.pending.seat) ? g.pending.seat : null;
  if (g.phase === 'planning') {
    for (const p of g.players) if (!p.planningDone && g.mySeats.includes(p.seat)) return p.seat;
    return null;
  }
  if (g.phase === 'action') return g.mySeats.includes(g.turnSeat) ? g.turnSeat : null;
  return null;
}
const mySeat = () => st.g.mySeats[0] ?? 0;
const viewSeat = () => activeSeat() ?? mySeat();

// ---------------------------------------------------------------- animations from log

function animateNewLog(prev) {
  const g = st.g;
  const fresh = g.log.filter(l => l.seq > st.lastLogSeq);
  st.lastLogSeq = g.log.length ? g.log[g.log.length - 1].seq : st.lastLogSeq;
  if (!prev) return; // initial load: no replay
  (async () => {
    for (const l of fresh) {
      if (l.type === 'roll' && l.data) {
        await showDice(l.data.roll, l.data.need, l.data.ok, l.text.split(':')[0]);
      } else if (l.type === 'missionDone') {
        banner('MISSION COMPLETE');
        toast(l.text, 'gold');
      } else if (l.type === 'milestone') {
        toast(l.text, 'gold');
      } else if (l.type === 'fail') {
        toast(l.text, 'bad');
      } else if (l.type === 'event' && l.data?.card) {
        toast(l.text);
      } else if (l.type === 'phase' && l.data?.phase === 'planning') {
        banner(`ROUND ${l.data.round}`);
      }
    }
  })();
  // your-turn banner
  const target = activeSeat();
  if (target !== null && g.phase === 'action') {
    const was = prev && prev.phase === 'action' && prev.turnSeat === g.turnSeat && prev.mySeats.includes(prev.turnSeat);
    if (!was) banner(st.g.mode === 'hotseat' ? `${g.players[target].name.toUpperCase()} — YOUR TURN` : 'YOUR TURN');
  }
}

// ---------------------------------------------------------------- rendering

function renderGame() {
  const g = st.g;
  if (!document.body.dataset.mtab) document.body.dataset.mtab = 'map';
  renderTopbar();
  renderLeft();
  renderRight();
  renderBottom();
  renderLog();
  renderMobileTabs();
  if (!st.board) {
    st.board = renderBoard($('board-wrap'), {
      onCraftClick: id => craftMenu(id),
      onNodeClick: () => {},
    });
  }
  st.board.setTw(g, viewSeat());
  st.board.drawCrafts(g, { mySeats: g.mySeats });

  if (g.pending && g.mySeats.includes(g.pending.seat)) showRerollModal();
  if (g.status === 'finished' && !st.gameOverShown) { st.gameOverShown = true; showGameOver(); }
}

function renderTopbar() {
  const g = st.g;
  const bar = clear($('topbar'));
  const pips = el('div', { class: 'tb-round' });
  for (let r = 1; r <= 8; r++) pips.append(el('div', { class: 'pip' + (r === g.round ? ' now' : r < g.round ? ' past' : '') }, String(r)));
  bar.append(el('div', { class: 'tb-item' }, 'Round', pips));
  bar.append(el('div', { class: 'phase-chip' }, 'Phase: ', el('b', {}, g.phase === 'planning' ? 'Planning' : g.phase === 'action' ? 'Action' : g.phase)));
  const tw = twCost(g, viewSeat());
  bar.append(el('div', { class: 'tw-chip', title: 'Transfer Window: extra Range cost to cross Sun Orbit ↔ Mars ZOI. Cycle: ' + TW_CYCLE.join('→') },
    'Transfer Window ', el('b', {}, String(tw))));
  if (g.event) {
    const c = cardOf(g.event);
    bar.append(el('div', { class: 'event-chip', title: c.text, onclick: () => zoomCard(g.event) },
      '⚡ ', el('b', {}, c.name), ` — ${c.text}`));
  }
  const right = el('div', { class: 'tb-item', style: 'margin-left:auto' },
    `Deck ${g.decks.component} · Room `, el('b', {}, g.room));
  // Fullscreen toggle (Android/desktop; iPhone uses Add-to-Home-Screen instead)
  if (document.fullscreenEnabled && matchMedia('(pointer: coarse)').matches
      && !matchMedia('(display-mode: standalone)').matches) {
    right.append(el('button', { class: 'btn small ghost', style: 'margin-left:8px', title: 'Fullscreen',
      onclick: () => {
        if (document.fullscreenElement || document.webkitFullscreenElement) {
          (document.exitFullscreen || document.webkitExitFullscreen)?.call(document);
        } else goFullscreen();
      } }, '⛶'));
  }
  right.append(el('button', { class: 'btn small ghost', style: 'margin-left:8px', onclick: () => {
    if (confirm('Leave this game on this device? (The room keeps running.)')) { session.clear(); stopPolling(); showLobby(); }
  } }, 'Exit'));
  bar.append(right);
}

function renderMobileTabs() {
  const g = st.g;
  const tabs = clear($('mobile-tabs'));
  const needsMe = activeSeat() !== null && g.status === 'playing';
  const cur = document.body.dataset.mtab || 'map';
  const defs = [
    ['map', '🗺', 'Map'],
    ['deals', '📋', 'Contracts'],
    ['crew', '🏢', 'Agencies'],
    ['log', '📡', 'Log'],
  ];
  for (const [id, ico, label] of defs) {
    const b = el('button', { class: id === cur ? 'active' : '', onclick: () => {
      document.body.dataset.mtab = id;
      renderMobileTabs();
    } }, el('span', { class: 'ico' }, ico), label);
    if (id === 'map' && needsMe && cur !== 'map') b.append(el('span', { class: 'dot-alert' }));
    tabs.append(b);
  }
}

function renderLeft() {
  const g = st.g;
  const col = clear($('left-col'));
  col.append(el('div', { class: 'side-label' }, 'Agencies'));
  for (const p of g.players) {
    const isCur = g.phase === 'action' && g.turnSeat === p.seat && g.status === 'playing';
    const pp = el('div', { class: 'pp' + (isCur ? ' current' : '') });
    pp.append(el('div', { class: 'pp-head' },
      el('span', { class: 'dot', style: `background:${p.color}` }),
      el('span', { class: 'pp-name' }, p.name),
      p.isYou && g.mode !== 'hotseat' ? el('span', { class: 'pp-you' }, 'YOU') : ''));
    pp.append(el('div', { class: 'pp-stats' },
      el('span', { class: 'vp' }, 'VP ', el('b', {}, String(p.vp))),
      el('span', { class: 'cr' }, 'Cr ', el('b', {}, String(p.credits))),
      el('span', {}, 'Lv ', el('b', {}, String(p.level) + (p.pendingLevel ? `→${p.pendingLevel}` : ''))),
      el('span', {}, 'Hand ', el('b', {}, String(p.handCount))),
      el('span', {}, 'Missions ', el('b', {}, String(p.missionsCompleted)))));
    if (g.phase === 'action') {
      const total = p.commandTurns;
      const turns = el('div', { class: 'turns', title: `Command turns: ${total - p.turnsUsed} of ${total} left` });
      for (let i = 0; i < total; i++) turns.append(el('div', { class: 'turn-pip' + (i >= p.turnsUsed && !p.passed ? ' free' : '') }));
      pp.append(turns);
    }
    if (p.tableau.length) {
      const tech = el('div', { class: 'pp-tech' });
      for (const t of p.tableau) tech.append(el('span', { class: 'chip', onclick: () => zoomCard(t) }, cardOf(t).name));
      pp.append(tech);
    }
    col.append(pp);
  }

  // my craft / hangar
  const seat = viewSeat();
  const mine = Object.values(g.crafts).filter(c => c.owner === seat);
  const hangar = mine.filter(c => c.node === 'assembly');
  const flying = mine.filter(c => c.node !== 'assembly');
  if (hangar.length || flying.length) {
    col.append(el('div', { class: 'side-label' }, 'Your craft'));
    const list = el('div', { class: 'craft-list' });
    for (const c of hangar) {
      list.append(el('div', { class: 'craft-item', onclick: () => craftMenu(c.id) },
        el('div', { class: 'ci-name' }, `🔧 ${c.name}`),
        el('div', { class: 'ci-sub' }, `on the pad · ${c.cards.length} parts · Range ${tankRange(c)}`)));
    }
    for (const c of flying) {
      list.append(el('div', { class: 'craft-item', onclick: () => craftMenu(c.id) },
        el('div', { class: 'ci-name' }, `${c.deployed ? (c.isStation ? '🛰' : '📡') : '🚀'} ${c.name}`),
        el('div', { class: 'ci-sub' }, `${NODES[c.node].name} · R${c.range} E${c.energy}` +
          (c.activated ? ' · used' : ''))));
    }
    col.append(list);
  }
}

function renderRight() {
  const g = st.g;
  const col = clear($('right-col'));
  const seat = viewSeat();

  const mp = el('div', { class: 'stack-panel' });
  mp.append(el('div', { class: 'side-label' }, `Missions (${g.decks.mission} in deck)`,
    el('span', { class: 'tip', title: 'Public contracts: any agency may complete them. They resolve automatically when a craft meets every condition. If nobody completes a mission in a round, the oldest is discarded.' }, '?')));
  const mrow = el('div', { class: 'cards' });
  for (const m of g.missions) if (m) mrow.append(renderCard(m));
  if (!g.missions.length) mrow.append(el('span', { class: 'help-note' }, 'No missions available.'));
  mp.append(mrow);
  col.append(mp);

  if (g.standing && g.standing.length) {
    const sp = el('div', { class: 'stack-panel' });
    sp.append(el('div', { class: 'side-label' }, 'Standing contracts ',
      el('span', { class: 'tip', title: 'Always available. Each agency may complete each of these once per game — a guaranteed early job that needs no drawn contract or payload.' }, '?')));
    const srow = el('div', { class: 'cards' });
    const done = g.players[seat] ? (g.players[seat].standingDone || []) : [];
    for (const mid of g.standing) {
      const card = renderCard(mid + '#1');
      if (done.includes(mid)) { card.style.opacity = '0.45'; card.title = 'Already completed by your agency'; }
      srow.append(card);
    }
    sp.append(srow);
    col.append(sp);
  }

  const mk = el('div', { class: 'stack-panel' });
  const mkLabel = el('div', { class: 'side-label' },
    el('span', {}, 'Card market ',
      el('span', { class: 'tip', title: 'Acquire Card action: pay the cost, take the card to hand. The slot refills immediately. Basic cards (Sterling Booster, Standard Tank, Heat Shield, Basic Battery, and the Light/Standard/Heavy Payloads) are always available via the Basic shop.' }, '?')));
  if (canAct()) {
    const p = g.players[seat];
    const flushed = p.flushedTurn === p.turnsUsed;
    mkLabel.append(el('button', {
      class: 'btn small ghost',
      title: 'Free action: pay 2 Credits to discard all 7 market cards and reveal 7 new ones. Once per command turn — does not use the turn.',
      disabled: flushed || p.credits < 2 ? '' : null,
      onclick: () => doAction({ type: 'flush_market' }),
    }, flushed ? '♻ Flushed' : '♻ Flush 2 Cr'));
  }
  mk.append(mkLabel);
  const row = el('div', { class: 'cards' });
  g.market.forEach((uid, i) => {
    if (!uid) return;
    const canBuy = canAct() && g.players[seat].credits >= basicCost(g, seat, cardOf(uid));
    const card = renderCard(uid, { onClick: () => zoomCard(uid, canBuy ? [
      el('button', { class: 'btn', onclick: () => { closeAllModals(); doAction({ type: 'acquire', slot: i, uid }); } },
        `Buy for ${basicCost(g, seat, cardOf(uid))} Cr (1 turn)`)] : []) });
    if (!canBuy && canAct()) card.classList.add('dim');
    row.append(card);
  });
  mk.append(row);
  col.append(mk);
}

function canAct() {
  const g = st.g;
  return g.status === 'playing' && g.phase === 'action' && !g.pending && g.mySeats.includes(g.turnSeat) && !st.busy;
}

function renderBottom() {
  const g = st.g;
  const bar = clear($('action-bar'));
  const hand = clear($('hand-area'));
  const seat = viewSeat();
  const p = g.players[seat];
  if (!p) return;

  if (g.status === 'finished') {
    bar.append(el('div', { class: 'info' }, 'Game over.'),
      el('button', { class: 'btn gold', onclick: showGameOver }, 'Final scores'));
    return;
  }

  if (g.phase === 'planning') {
    const me = g.players[seat];
    const limit = handLimit(g);
    if (me.planningDone || !g.mySeats.includes(seat)) {
      bar.append(el('div', { class: 'info' }, 'Waiting for other agencies to finish planning…'));
    } else {
      const over = (me.hand?.length || 0) - st.planningSel.size - limit;
      const sellCount = Math.min(2, st.planningSel.size);
      bar.append(el('div', { class: 'info' },
        el('b', {}, `${me.name} — Planning: `),
        `select cards to drop — the first ${sellCount || 'two'} sell for 1 Cr each` +
        (over > 0 ? ` · MUST drop ${over} more (hand limit ${limit})` : '')));
      bar.append(el('button', { class: 'btn gold', disabled: over > 0 ? '' : null, onclick: async () => {
        const sel = [...st.planningSel];
        const sell = sel.slice(0, 2), discard = sel.slice(2);
        st.planningSel.clear();
        await doAction({ type: 'planning_done', sell, discard }, seat);
      } }, st.planningSel.size ? `Ready (sell ${sellCount} → +${sellCount} Cr)` : 'Ready'));
      if (st.planningSel.size) bar.append(el('button', { class: 'btn ghost small', onclick: () => { st.planningSel.clear(); renderBottom(); } }, 'clear'));
    }
  } else if (g.phase === 'action') {
    if (!canAct()) {
      const cur = g.players[g.turnSeat];
      bar.append(el('div', { class: 'info' }, `Waiting: `, el('b', {}, cur?.name || ''), ` is taking a command turn…`));
    } else {
      const total = p.commandTurns;
      bar.append(el('div', { class: 'info' }, el('b', {}, `${p.name}`), ` — command turn ${p.turnsUsed + 1}/${total} · ${p.credits} Cr`));
      bar.append(
        el('button', { class: 'btn', title: 'Assemble a rocket from hand cards, then optionally launch it in the same turn.',
          onclick: () => openBuilderFlow(null) }, '🚀 Build / Launch'),
        el('button', { class: 'btn ghost', title: 'Always-available Basic cards', onclick: basicShop }, '🛒 Basic shop'),
        el('button', { class: 'btn ghost', title: 'Develop a Technology card from your hand (pay its cost).', onclick: developMenu }, '🔬 Develop'),
        el('button', { class: 'btn ghost', title: `Level 2 costs 6 Cr (3 turns/round), Level 3 costs 14 Cr (4 turns/round). Takes effect next round.`,
          onclick: expandConfirm }, '🏢 Expand'),
        el('button', { class: 'btn ghost', onclick: () => doAction({ type: 'pass' }) }, 'Pass'),
      );
    }
  }

  // hand
  const hd = g.players[seat].hand;
  if (hd) {
    for (const uid of hd) {
      const selectable = g.phase === 'planning' && !g.players[seat].planningDone && g.mySeats.includes(seat);
      const card = renderCard(uid, {
        onClick: () => {
          if (selectable) {
            st.planningSel.has(uid) ? st.planningSel.delete(uid) : st.planningSel.add(uid);
            renderBottom();
          } else handCardMenu(uid);
        },
      });
      if (st.planningSel.has(uid)) card.classList.add('selected');
      hand.append(card);
    }
    if (!hd.length) hand.append(el('span', { class: 'help-note' }, 'Hand is empty — acquire cards from the market.'));
  }
}

function renderLog() {
  const g = st.g;
  const body = $('log-body');
  clear(body);
  for (const l of g.log.slice(-120)) {
    body.append(el('div', { class: `l-${l.type}` }, l.text));
  }
  body.scrollTop = body.scrollHeight;
}
$('log-head').addEventListener('click', () => {
  const b = $('log-body');
  b.classList.toggle('hidden');
});

// ---------------------------------------------------------------- action flows

async function doAction(action, seat = null) {
  if (st.busy) return;
  st.busy = true;
  try {
    const r = await gameCall('action', { seat: seat ?? viewSeat(), action });
    applyState(r);
  } catch (e) {
    toast(e.message, 'bad');
  } finally {
    st.busy = false;
    renderGame();
  }
}

function handCardMenu(uid) {
  const g = st.g;
  const c = cardOf(uid);
  const acts = [];
  if (canAct()) {
    if (c.type === 'Tech') {
      acts.push(el('button', { class: 'btn', disabled: g.players[viewSeat()].credits < c.cost ? '' : null,
        onclick: () => { closeAllModals(); doAction({ type: 'develop', card: uid }); } },
        `Develop for ${c.cost} Cr (1 turn)`));
    }
    if (['Engine', 'Tank', 'Payload', 'Support'].includes(c.type)) {
      acts.push(el('button', { class: 'btn', onclick: () => { closeAllModals(); openBuilderFlow(null); } }, 'Open Rocket Builder'));
    } else {
      // Any card can still fly as jury-rigged hardware (v0.5.1 §9).
      acts.push(el('button', { class: 'btn ghost', onclick: () => { closeAllModals(); openBuilderFlow(null); } },
        'Open Rocket Builder (jury-rig)'));
    }
  }
  zoomCard(uid, acts);
}

function basicShop() {
  const g = st.g, seat = viewSeat();
  const basics = Object.values(CARDS).filter(c => c.tags.includes('Basic'));
  const content = el('div', {},
    el('h2', {}, 'Basic supply'),
    el('div', { class: 'm-sub' }, 'Basic cards are always available at printed cost (Acquire Card action — 1 command turn).'),
    el('div', { class: 'picker-cards' },
      basics.map(c => {
        const cost = basicCost(g, seat, c);
        const card = renderCard(c.id + '#x', { onClick: () => {
          if (g.players[seat].credits < cost) return toast('Not enough Credits', 'bad');
          closeModal();
          doAction({ type: 'acquire', basic: c.id });
        } });
        return el('div', { style: 'text-align:center;' }, card, el('div', { style: 'margin-top:4px; font-size:12px;' }, `${cost} Cr`));
      })));
  openModal(content);
}

function developMenu() {
  const g = st.g, seat = viewSeat();
  const techs = (g.players[seat].hand || []).filter(u => cardOf(u).type === 'Tech');
  if (!techs.length) return toast('No Technology cards in hand — acquire them from the market.', '');
  const content = el('div', {},
    el('h2', {}, 'Develop Technology'),
    el('div', { class: 'm-sub' }, 'Pay the cost; the tech applies to all your craft permanently. You cannot develop two techs with the same name.'),
    el('div', { class: 'picker-cards' },
      techs.map(uid => renderCard(uid, { onClick: () => {
        const c = cardOf(uid);
        if (g.players[seat].credits < c.cost) return toast('Not enough Credits', 'bad');
        closeModal();
        doAction({ type: 'develop', card: uid });
      } }))));
  openModal(content);
}

function expandConfirm() {
  const g = st.g, seat = viewSeat();
  const p = g.players[seat];
  const cur = p.pendingLevel ?? p.level;
  if (cur >= 3) return toast('Already at Level 3.', '');
  const cost = cur === 1 ? 6 : 14;
  const content = el('div', {},
    el('h2', {}, `Expand to Agency Level ${cur + 1}`),
    el('div', { class: 'm-sub' }, `Pay ${cost} Credits. From next round you get ${cur + 1 === 2 ? 3 : 4} command turns per round.` +
      (cur + 1 === 2 && !g.tier2Unlocked ? ' This unlocks Tier 2 missions for everyone!' : '') +
      (cur + 1 === 3 && !g.tier3Unlocked ? ' This unlocks Tier 3 missions for everyone!' : '')),
    el('div', { class: 'modal-actions' },
      el('button', { class: 'btn ghost', onclick: () => closeModal() }, 'Cancel'),
      el('button', { class: 'btn gold', disabled: p.credits < cost ? '' : null,
        onclick: () => { closeModal(); doAction({ type: 'expand' }); } }, `Pay ${cost} Cr (1 turn)`)));
  openModal(content);
}

function openBuilderFlow(craftId) {
  const g = st.g, seat = viewSeat();
  if (!canAct()) return toast('Wait for your command turn.', '');
  openBuilder(g, seat, craftId, {
    onEngineering(diff) {
      if (!diff.add.length && !diff.remove.length && !diff.sideways && !diff.unrig) return toast('No changes made.', '');
      doAction({ type: 'engineering', craft: diff.craft, add: diff.add, remove: diff.remove,
                 sideways: diff.sideways, unrig: diff.unrig });
    },
    onLaunch(diff, mounted, sideways) {
      openPlanner(g, seat, {
        mode: 'launch', cards: mounted, engDiff: diff, sideways,
        onSubmit(plan, engDiff) {
          if (engDiff.craft) {
            doAction({ type: 'launch', craft: engDiff.craft, components: engDiff.add, remove: engDiff.remove,
                       sideways: engDiff.sideways, unrig: engDiff.unrig, plan });
          } else {
            doAction({ type: 'launch', components: engDiff.add, sideways: engDiff.sideways, plan });
          }
        },
      });
    },
  });
}

function craftMenu(craftId) {
  const g = st.g;
  const c = g.crafts[craftId];
  if (!c) return;
  const mineTurn = canAct() && c.owner === g.turnSeat;
  const content = el('div', { style: 'max-width: 720px;' });
  content.append(el('h2', {}, c.name),
    el('div', { class: 'm-sub' },
      `${g.players[c.owner].name} · ${c.node === 'assembly' ? 'on the pad' : NODES[c.node].name}` +
      ` · Range ${c.range} · Energy ${c.energy}` +
      (c.isStation ? ' · ON-ORBIT STATION' : c.deployed ? ' · deployed asset' : '') +
      (c.activated && c.node !== 'assembly' ? ' · already activated this round' : '')));
  const cardrow = el('div', { class: 'cardrow' },
    c.cards.map(uid => renderCard(uid, { size: 'small-face' })));
  if (c.sideways) {
    cardrow.append(el('div', { style: 'position:relative;' },
      renderCard(c.sideways, { size: 'small-face' }),
      el('div', { style: 'position:absolute; top:6px; left:6px; background:#000c; color:#ffd166; padding:2px 6px; border-radius:4px; font-size:11px; pointer-events:none;' },
        '⚒ jury-rigged')));
  }
  content.append(cardrow);
  const acts = el('div', { class: 'modal-actions' });
  if (mineTurn && c.node === 'assembly') {
    acts.append(
      el('button', { class: 'btn ghost', onclick: () => { closeModal(); openBuilderFlow(craftId); } }, '🔧 Modify (1 turn)'),
      el('button', { class: 'btn gold', onclick: () => {
        closeModal();
        openPlanner(g, viewSeat(), {
          mode: 'launch', cards: [...c.cards], engDiff: { craft: craftId, add: [], remove: [] },
          sideways: c.sideways,
          onSubmit(plan) { doAction({ type: 'launch', craft: craftId, plan }); },
        });
      } }, '🚀 Launch (1 turn)'));
  }
  if (mineTurn && c.node !== 'assembly' && !c.activated) {
    acts.append(el('button', { class: 'btn gold', onclick: () => {
      closeModal();
      openPlanner(g, viewSeat(), {
        mode: 'activate', craftId,
        onSubmit(plan) { doAction({ type: 'activate', craft: craftId, plan }); },
      });
    } }, '🛰 Activate (1 turn)'));
  }
  if (acts.children.length) content.append(acts);
  const hints = [];
  if (c.deployed && !craftCards(c, null, 'Power').length && !c.isStation)
    hints.push('This asset has no Power card: it cannot pay income during Maintenance.');
  if (c.node !== 'assembly' && !craftEngine(c) && c.range > 0)
    hints.push('No engine: the craft cannot maneuver, only operate in place.');
  if (hints.length) content.append(el('div', { class: 'hint-box' }, hints.join(' ')));
  openModal(content);
}

function showRerollModal() {
  const g = st.g;
  if (document.querySelector('#modal-root .reroll')) return;
  const content = el('div', { class: 'reroll' },
    el('h2', {}, 'Launch failure!'),
    el('div', { class: 'm-sub' }, `The reliability roll failed (needed ≤ ${g.pending.rel}). Your Launch Abort System can save the flight: pay 2 Credits to reroll once.`),
    el('div', { class: 'modal-actions' },
      el('button', { class: 'btn ghost', onclick: () => { closeModal(); doAction({ type: 'decision', accept: false }, g.pending.seat); } }, 'Accept failure'),
      el('button', { class: 'btn gold', onclick: () => { closeModal(); doAction({ type: 'decision', accept: true }, g.pending.seat); } }, 'Pay 2 Cr — reroll')));
  openModal(content, { closable: false });
}

function showGameOver() {
  const g = st.g;
  const rows = g.finalScores.map((s, i) => el('tr', {},
    el('td', { style: 'padding:6px 14px;' }, i === 0 ? '🏆' : `${i + 1}.`),
    el('td', { style: `padding:6px 14px; color:${g.players[s.seat].color}; font-weight:700;` }, s.name),
    el('td', { style: 'padding:6px 14px; text-align:right;' }, `${s.vp} VP`),
    el('td', { style: 'padding:6px 14px; text-align:right; color:var(--dim);' }, `${s.missions} missions · ${s.credits} Cr · ${s.assets} assets`)));
  const content = el('div', {},
    el('h2', {}, 'Final scores'),
    el('div', { class: 'm-sub' }, 'Ties break by completed missions, then remaining Credits. Deployed assets scored +1 VP each.'),
    el('table', { style: 'border-collapse:collapse; font-size:15px;' }, ...rows),
    el('div', { class: 'modal-actions' },
      el('button', { class: 'btn ghost', onclick: () => { session.clear(); stopPolling(); closeAllModals(); showLobby(); } }, 'Back to lobby'),
      el('button', { class: 'btn', onclick: () => closeModal() }, 'Review the board')));
  openModal(content);
}

// ---------------------------------------------------------------- boot

(async function boot() {
  // Never leave the page blank: if the card database can't be fetched (server
  // overloaded / offline), show the problem in the lobby and offer a retry.
  for (;;) {
    try { await loadCards(); break; }
    catch (e) {
      const box = $('lobby-content');
      clear(box);
      await new Promise(retry => {
        box.append(
          el('p', { class: 'help-note' },
            'Could not reach the game server — it may be down or overloaded. ' +
            '(' + e.message + ')'),
          el('button', { class: 'btn', onclick: retry }, 'Retry'));
      });
    }
  }
  if (session.room) enterGame();
  else showLobby();
})();
