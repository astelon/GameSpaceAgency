// Rocket Builder + Flight Planner modals.
import {
  cardOf, cidOf, hasTag, NODES, neighborsOf, isSurface, edgeBetween,
  craftEngine, craftPayload, craftCards, craftMass, craftThrust, craftReliability,
  tankRange, simulatePlan, twCost, missionPreview, stageBonus, inSpace,
} from './data.js';
import { el, clear, openModal, closeModal } from './ui.js';
import { renderCard, zoomCard } from './cards.js';
import { renderBoard } from './board.js';

// ---------------------------------------------------------------- builder

// mode: 'new' (build from hand) or existing assembly craft id.
export function openBuilder(g, seat, craftId, { onEngineering, onLaunch }) {
  const player = g.players[seat];
  const baseCards = craftId ? [...g.crafts[craftId].cards] : [];
  let mounted = [...baseCards];

  const wrap = el('div', { style: 'min-width: min(880px, 90vw);' });
  wrap.append(el('h2', {}, craftId ? 'Modify Rocket' : 'Rocket Assembly'),
    el('div', { class: 'm-sub' },
      'A rocket: 1 Engine · 1–3 Fuel Tanks · 0–1 Payload · 0–3 Support. The Engine\'s Thrust must cover the total Mass of tanks, payload and heavy support.'));

  const body = el('div', { class: 'builder' });
  const slots = el('div', { class: 'slots' });
  const statsPanel = el('div', { class: 'stats-panel' });
  body.append(slots, statsPanel);
  wrap.append(body);

  const actions = el('div', { class: 'modal-actions' });
  const btnEng = el('button', { class: 'btn ghost', onclick: () => { closeModal(); onEngineering(diff()); } }, '🔧 Save configuration (1 turn)');
  const btnLaunch = el('button', { class: 'btn gold', onclick: () => { closeModal(); onLaunch(diff(), mounted); } }, '🚀 Plan Launch (1 turn)');
  actions.append(btnEng, btnLaunch);
  wrap.append(actions);

  function diff() {
    return {
      craft: craftId,
      add: mounted.filter(u => !baseCards.includes(u)),
      remove: baseCards.filter(u => !mounted.includes(u)),
    };
  }

  const SLOT_DEFS = [
    ['Engine', 'Engine', 1], ['Tank', 'Fuel Tanks', 3], ['Payload', 'Payload', 1], ['Support', 'Support', 3],
  ];

  function handAvailable(type) {
    return player.hand.filter(u => cardOf(u).type === type && !mounted.includes(u));
  }

  function render() {
    clear(slots);
    for (const [type, label, max] of SLOT_DEFS) {
      const cur = mounted.filter(u => cardOf(u).type === type);
      const row = el('div', { class: 'slot-row' });
      row.append(el('div', { class: 'slot-label' }, `${label} (${cur.length}/${max})`));
      const box = el('div', { class: 'slot-box' });
      for (const uid of cur) {
        const c = cardOf(uid);
        box.append(el('div', { class: 'mini', title: 'Click to unmount', onclick: () => { mounted = mounted.filter(u => u !== uid); render(); } },
          `${c.name}`, el('span', { style: 'color:#ff6b6b' }, '✕')));
      }
      if (cur.length < max) {
        const avail = handAvailable(type);
        if (avail.length) {
          const addBtn = el('button', { class: 'btn small ghost', onclick: () => pick(type) }, `+ add from hand (${avail.length})`);
          box.append(addBtn);
        } else {
          box.append(el('span', { style: 'color:#55689a; font-size:12px; align-self:center;' }, 'no matching cards in hand'));
        }
      }
      row.append(box);
      slots.append(row);
    }
    renderStats();
  }

  function pick(type) {
    const avail = handAvailable(type);
    const content = el('div', {},
      el('h2', {}, `Choose a ${type}`),
      el('div', { class: 'picker-cards' },
        avail.map(uid => renderCard(uid, { onClick: () => { mounted.push(uid); closeModal(); render(); } }))));
    openModal(content);
  }

  function renderStats() {
    clear(statsPanel);
    const fake = { owner: seat, cards: mounted, node: 'assembly', history: [] };
    const mass = craftMass(g, fake);
    const thrust = craftThrust(g, fake);
    const range = tankRange(fake);
    const [rel, mods] = craftReliability(g, fake, false);
    const eng = craftEngine(fake);
    const pl = craftPayload(fake);
    const row = (k, v, cls = '') => el('div', { class: 'row' }, el('span', {}, k), el('b', { class: cls }, v));
    statsPanel.append(
      row('Total Mass', String(mass)),
      row('Thrust', String(thrust), thrust >= mass && eng ? 'ok' : 'no'),
      row('Lift check', eng ? (thrust >= mass ? '✔ can lift' : '✘ too heavy') : '✘ no engine', thrust >= mass && eng ? 'ok' : 'no'),
      row('Range', String(range)),
      row('Reliability', eng ? `${Math.max(0, Math.min(10, rel))}0%` : '—', rel >= 8 ? 'ok' : rel <= 5 ? 'no' : ''),
    );
    const notes = [];
    if (eng && cidOf(eng) === 'E03' && !craftCards(fake, 'Tank', 'Cryogenic').length)
      notes.push('⚠ Hydrogen Core needs a Cryo Tank.');
    if (pl && cardOf(pl).tags.includes('Crewed') && !craftCards(fake, 'Tank', 'Pressurized').length)
      notes.push('⚠ Crewed payloads need a Pressurized Tank for crewed missions.');
    if (!craftCards(fake, 'Tank').length) notes.push('⚠ No fuel tank: Range 0.');
    if (mods.length > 1) notes.push('Reliability: ' + mods.join(', '));
    if (notes.length) statsPanel.append(el('div', { class: 'hint-box', style: 'margin-top:8px;' }, notes.join(' ')));
    btnLaunch.disabled = !eng || thrust < mass || (eng && cidOf(eng) === 'E03' && !craftCards(fake, 'Tank', 'Cryogenic').length);
    btnEng.disabled = mounted.length === 0 && !craftId;
  }

  render();
  openModal(wrap);
}

// ---------------------------------------------------------------- flight planner

// mode 'launch': craft described by cards (not yet in flight); engineering diff supplied.
// mode 'activate': existing in-flight craft.
export function openPlanner(g, seat, { mode, cards = null, engDiff = null, craftId = null, onSubmit }) {
  const startNode = mode === 'launch' ? 'earth' : g.crafts[craftId].node;
  const virtualCraft = () => mode === 'launch'
    ? { id: '_new', owner: seat, cards: [...cards], node: 'earth', range: tankRange({ cards }),
        energy: 0, history: ['earth'], deployed: false, isStation: false }
    : JSON.parse(JSON.stringify(g.crafts[craftId]));

  let path = [startNode];
  let stagingChoice = {};   // uid -> 'pre' | hopIndex
  let aeroChoice = {};      // uid -> hopIndex
  let landingChoice = {};   // hopIndex -> {method, card}
  let deploys = [];         // {step, payload, supports[]}
  let dockStep = null;
  let operate = new Set();  // uids
  let flightComputer = false, tug = false, depotId = null;

  const wrap = el('div', { style: 'width: min(1180px, 94vw); display:flex; flex-direction:column; gap:10px;' });
  wrap.append(el('h2', {}, mode === 'launch' ? '🚀 Flight Plan — Launch' : '🛰 Flight Plan — Activate'),
    el('div', { class: 'm-sub' }, 'Click connected nodes on the map to extend the route; click an earlier node to back up. You may stop anywhere — unspent Range stays with the craft for later rounds.'));
  const cols = el('div', { style: 'display:flex; gap:14px; flex-wrap:wrap;' });
  const boardBox = el('div', { class: 'planner-board', style: 'flex: 1 1 520px; min-width: min(480px, 100%); background:#0a1122; border:1px solid var(--line); border-radius:10px;' });
  const side = el('div', { class: 'planner-side', style: 'flex: 1 1 300px; min-width: min(300px, 100%); max-height: 62vh; overflow-y:auto;' });
  cols.append(boardBox, side);
  wrap.append(cols);

  const actions = el('div', { class: 'modal-actions' });
  const submitBtn = el('button', { class: 'btn gold' }, 'Confirm flight');
  const cancelBtn = el('button', { class: 'btn ghost', onclick: () => closeModal() }, 'Cancel');
  actions.append(cancelBtn, submitBtn);
  wrap.append(actions);

  const board = renderBoard(boardBox, {
    compact: true,
    onNodeClick(node) {
      const last = path[path.length - 1];
      const idx = path.lastIndexOf(node);
      if (idx !== -1 && idx < path.length - 1) {
        // clicking an earlier node truncates (but allow revisits by clicking neighbors)
        path = path.slice(0, idx + 1);
      } else if (edgeBetween(last, node)) {
        path.push(node);
      } else {
        return;
      }
      // prune choices referencing removed hops
      for (const [uid, v] of Object.entries(stagingChoice)) if (v !== 'pre' && v >= path.length) delete stagingChoice[uid];
      for (const [uid, v] of Object.entries(aeroChoice)) if (v >= path.length) delete aeroChoice[uid];
      for (const k of Object.keys(landingChoice)) if (+k >= path.length || !isSurface(path[+k])) delete landingChoice[k];
      deploys = deploys.filter(d => d.step < path.length);
      if (dockStep !== null && (dockStep >= path.length || path[dockStep] !== 'geo')) dockStep = null;
      refresh();
    },
  });
  board.setTw(g, seat);
  board.drawCrafts(g, { mySeats: [seat] });

  function buildPlan() {
    const plan = { path: [...path] };
    for (const [uid, v] of Object.entries(stagingChoice)) {
      if (v === 'pre') plan.preStage = uid;
      else (plan.midStages ||= {})[v] = uid;
    }
    for (const [uid, v] of Object.entries(aeroChoice)) (plan.aerobrake ||= {})[v] = uid;
    for (const [k, v] of Object.entries(landingChoice)) (plan.landing ||= {})[k] = v;
    if (deploys.length) plan.deploys = deploys.map(d => ({ step: d.step, payload: d.payload, supports: d.supports }));
    if (dockStep !== null) plan.dock = dockStep;
    if (operate.size) plan.operate = [...operate].map(u => ({ card: u }));
    if (flightComputer) plan.flightComputer = true;
    if (tug) plan.tug = true;
    if (depotId) plan.depot = depotId;
    return plan;
  }

  function refresh() {
    const craft = virtualCraft();
    const plan = buildPlan();
    const sim = simulatePlan(g, craft, plan);

    board.highlightPath(path, neighborsOf(path[path.length - 1]).filter(n => {
      // candidate nodes the player could click next
      return true;
    }));

    clear(side);

    // budget
    const startRange = craft.range;
    const remaining = sim.ok ? sim.finalCraft.range : null;
    side.append(el('div', { class: 'plan-step budget' },
      el('div', {}, `Range budget: `, el('b', { class: sim.ok ? 'ok' : 'no' },
        sim.ok ? `${remaining} left of ${startRange}${(sim.finalCraft.range - craft.range) > 0 ? '+' : ''}` : '—')),
      sim.ok && sim.energyUsed ? el('div', { style: 'color:var(--dim); font-size:12px;' }, `Energy used: ${sim.energyUsed}`) : null,
    ));

    // hop list
    path.forEach((node, k) => {
      if (k === 0) return;
      const stepBox = el('div', { class: 'plan-step' });
      const edge = edgeBetween(path[k-1], node);
      const twNote = edge?.tw ? ` — Transfer Window (${twCost(g, seat)})` : '';
      stepBox.append(el('div', { class: 'ps-head' },
        el('span', {}, `${k}. ${NODES[path[k-1]].name} → ${NODES[node].name}${twNote}`)));

      // landing method
      if (isSurface(node) && node !== 'moon') {
        const sel = el('select', {
          onchange: e => {
            const v = e.target.value;
            if (!v) delete landingChoice[k];
            else if (v === 'prop') landingChoice[k] = { method: 'propulsive' };
            else if (v === 'lander') landingChoice[k] = { method: 'lander' };
            else landingChoice[k] = { method: 'reentry', card: v };
            refresh();
          },
        });
        sel.append(el('option', { value: '' }, '— landing method —'));
        // Landing devices: parachutes (Earth only), airbags (uncrewed, Earth/Mars).
        // Heat shields (Reentry) survive the heat but cannot land the craft.
        const crewed = craftCards(craft, 'Payload', 'Crewed').length > 0;
        for (const uid of craftCards(craft, null, 'Parachute')) {
          if (node !== 'earth') continue;
          sel.append(el('option', { value: uid, selected: landingChoice[k]?.card === uid ? '' : null },
            `Use ${cardOf(uid).name} (parachute)`));
        }
        for (const uid of craftCards(craft, null, 'Airbag')) {
          if (crewed) continue;
          sel.append(el('option', { value: uid, selected: landingChoice[k]?.card === uid ? '' : null },
            `Use ${cardOf(uid).name} (airbags)`));
        }
        if (craftCards(craft, 'Payload', 'Lander').length) {
          sel.append(el('option', { value: 'lander', selected: landingChoice[k]?.method === 'lander' ? '' : null },
            'Set down with Lander'));
        }
        if (craftEngine(craft)) {
          const legs = craft.cards.some(u => cidOf(u) === 'S14');
          sel.append(el('option', { value: 'prop', selected: landingChoice[k]?.method === 'propulsive' ? '' : null },
            `Propulsive landing (${legs ? 'free — Landing Legs' : '+1 Range'})`));
        }
        stepBox.append(sel);
      }
      if (node === 'moon' && k === path.length - 1) {
        stepBox.append(el('div', { style: 'color:var(--dim)' }, 'Propulsive Moon landing (Engine required, no extra Range).'));
      }
      stepBox.append(...stepControls(craft, k, node));
      side.append(stepBox);
    });

    // global option controls
    const opts = el('div', { class: 'plan-step' }, el('div', { class: 'ps-head' }, el('b', {}, 'Options')));
    let any = false;

    // staging selectors
    const stageables = craftCards(virtualCraft(), null, 'Stageable');
    for (const uid of stageables) {
      any = true;
      const sel = el('select', { onchange: e => {
        const v = e.target.value;
        if (!v) delete stagingChoice[uid];
        else stagingChoice[uid] = v === 'pre' ? 'pre' : +v;
        refresh();
      }});
      sel.append(el('option', { value: '' }, `Keep ${cardOf(uid).name}`));
      if (mode === 'launch') sel.append(el('option', { value: 'pre', selected: stagingChoice[uid] === 'pre' ? '' : null },
        `Stage pre-flight (+${stageBonus(g, seat, uid)} Range)`));
      for (let k = 1; k < path.length; k++) {
        sel.append(el('option', { value: k, selected: stagingChoice[uid] === k ? '' : null },
          `Stage before hop ${k} (+${stageBonus(g, seat, uid)} Range)`));
      }
      opts.append(sel);
    }
    // aerobrake selectors
    for (const uid of craftCards(virtualCraft(), null, 'Reentry')) {
      const hops = [];
      const eChain = ['earthZoi','geo','leo','subEarth','earth'], mChain = ['marsZoi','marsHigh','marsLow','subMars','mars'];
      for (let k = 1; k < path.length; k++) {
        const a = path[k-1], b = path[k];
        if ((eChain.includes(a) && eChain.includes(b) && eChain.indexOf(b) > eChain.indexOf(a)) ||
            (mChain.includes(a) && mChain.includes(b) && mChain.indexOf(b) > mChain.indexOf(a))) hops.push(k);
      }
      if (!hops.length) continue;
      any = true;
      const sel = el('select', { onchange: e => {
        const v = e.target.value;
        if (!v) delete aeroChoice[uid];
        else aeroChoice[uid] = +v;
        refresh();
      }});
      sel.append(el('option', { value: '' }, `No aerobrake with ${cardOf(uid).name}`));
      for (const k of hops) sel.append(el('option', { value: k, selected: aeroChoice[uid] === k ? '' : null },
        `Aerobrake at hop ${k} (+2 Range${cidOf(uid) === 'S03' ? ', kept' : ', expends card'})`));
      opts.append(sel);
    }
    // flight computer / tug / depot
    if (virtualCraft().cards.some(u => cidOf(u) === 'S10') && (mode === 'launch' || isSurface(startNode))) {
      any = true;
      opts.append(checkbox('Flight Computer: 1 Energy → +1 Reliability', flightComputer, v => { flightComputer = v; refresh(); }));
    }
    if (mode === 'activate' && inSpace(startNode) && virtualCraft().cards.some(u => cidOf(u) === 'S06')) {
      any = true;
      opts.append(checkbox('Orbital Tug: 1 Energy → +1 Range', tug, v => { tug = v; refresh(); }));
    }
    const depot = Object.values(g.crafts).find(c => c.owner === seat && c.deployed && c.node === startNode &&
      c.cards.some(u => cidOf(u) === 'P11') && c.energy > 0 && c.depotUsedRound !== g.round);
    if (depot) {
      any = true;
      opts.append(checkbox(`Fuel Depot at ${NODES[startNode].name}: +2 Range`, !!depotId, v => { depotId = v ? depot.id : null; refresh(); }));
    }
    // dock
    const station = Object.values(g.crafts).find(c => c.isStation && c.node === 'geo');
    if (station && virtualCraft().cards.some(u => hasTag(u, 'Docking'))) {
      const geoSteps = path.map((n, i) => n === 'geo' ? i : -1).filter(i => i >= 0);
      if (geoSteps.length) {
        any = true;
        opts.append(checkbox(`Dock with ${station.name} at GEO (1 Energy)`, dockStep !== null,
          v => { dockStep = v ? geoSteps[geoSteps.length - 1] : null; refresh(); }));
      }
    }
    // operate abilities at final node
    for (const uid of virtualCraft().cards) {
      const cid = cidOf(uid);
      if (cid === 'P03' || cid === 'S11') {
        any = true;
        opts.append(checkbox(`Operate ${cardOf(uid).name} (${cid === 'P03' ? '2' : '1'} Energy)`, operate.has(uid),
          v => { v ? operate.add(uid) : operate.delete(uid); refresh(); }));
      }
    }
    if (any) side.append(opts);

    // deploy controls at each visited node
    const deployables = virtualCraft().cards.filter(u => {
      const c = cardOf(u);
      return c.type === 'Payload' && (c.tags.includes('Satellite') || c.tags.includes('Station'));
    });
    if (deployables.length) {
      const dep = el('div', { class: 'plan-step' }, el('div', { class: 'ps-head' }, el('b', {}, 'Deploy payload')));
      for (const uid of deployables) {
        const cur = deploys.find(d => d.payload === uid);
        const sel = el('select', { onchange: e => {
          deploys = deploys.filter(d => d.payload !== uid);
          if (e.target.value !== '') deploys.push({ step: +e.target.value, payload: uid, supports: cur?.supports || [] });
          refresh();
        }});
        sel.append(el('option', { value: '' }, `Keep ${cardOf(uid).name} aboard`));
        path.forEach((n, i) => {
          sel.append(el('option', { value: i, selected: cur && cur.step === i ? '' : null },
            `Deploy at ${NODES[n].name}${i === 0 ? ' (before moving)' : ''}`));
        });
        dep.append(sel);
        if (cur) {
          const sup = el('div', { style: 'display:flex; flex-direction:column; gap:2px; padding-left: 10px;' });
          for (const su of craftCards(virtualCraft(), 'Support')) {
            sup.append(checkbox(`bring ${cardOf(su).name}`, cur.supports.includes(su), v => {
              cur.supports = v ? [...cur.supports, su] : cur.supports.filter(x => x !== su);
              refresh();
            }));
          }
          if (sup.children.length) dep.append(sup);
          dep.append(el('div', { style: 'color:var(--dim); font-size:11.5px; padding-left:10px;' },
            '💡 an asset needs a Power support card aboard to earn income each round'));
        }
      }
      side.append(dep);
    }

    // reliability checks preview
    for (const chk of sim.checks || []) {
      const pct = Math.max(0, Math.min(10, chk.rel)) * 10;
      side.append(el('div', { class: 'plan-step' },
        el('div', {}, `🎲 Launch check at ${NODES[chk.at].name}: `,
          el('b', { style: pct >= 80 ? 'color:var(--good)' : pct <= 50 ? 'color:var(--bad)' : 'color:var(--gold)' }, `${pct}% success`)),
        el('div', { style: 'color:var(--dim); font-size:11.5px;' }, chk.mods.join(', ') + ` · Thrust ${chk.thrust} vs Mass ${chk.mass}`)));
    }

    // outcome
    if (!sim.ok) {
      side.append(el('div', { class: 'hint-box warn-box' }, '✘ ', sim.error));
    } else {
      if (sim.missions.length) {
        side.append(el('div', { class: 'hint-box ok-box' }, '🏁 This flight will complete: ' +
          sim.missions.map(m => cardOf(m).name + ` (+${cardOf(m).vp} VP, +${cardOf(m).rewardCredits} Cr)`).join(' · ')));
      }
      for (const w of sim.warnings) side.append(el('div', { class: 'hint-box' }, '💡 ', w));
      for (const s of sim.steps.filter(s => s.note && !s.from)) side.append(el('div', { class: 'hint-box' }, '· ', s.note));
      if (path.length === 1 && !operate.size && dockStep === null && !deploys.length) {
        side.append(el('div', { class: 'hint-box' }, mode === 'launch'
          ? 'Extend the route by clicking Sub-Orbital Earth.'
          : 'This will activate the craft without moving (allowed, e.g. to operate instruments).'));
      }
      if (sim.ok && sim.finalCraft.node === 'earth' && path.length > 1) {
        side.append(el('div', { class: 'hint-box ok-box' }, '🌍 The craft ends on Earth: Reusable parts return to your hand during Maintenance.'));
      }
    }

    submitBtn.disabled = !sim.ok || (mode === 'launch' && path.length === 1);
    board.setTw(g, seat);
    return sim;
  }

  function stepControls() { return []; }

  function checkbox(label, checked, onChange) {
    const cb = el('input', { type: 'checkbox' });
    cb.checked = checked;
    cb.addEventListener('change', () => onChange(cb.checked));
    return el('label', { style: 'display:flex; gap:6px; align-items:center; cursor:pointer;' }, cb, label);
  }

  submitBtn.addEventListener('click', () => {
    const plan = buildPlan();
    closeModal();
    onSubmit(plan, engDiff);
  });

  refresh();
  openModal(wrap, { closable: false });
}
