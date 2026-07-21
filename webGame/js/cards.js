// Card rendering + teaching hints ("what will the game do with this card?").

import { cardOf, cidOf } from './data.js';
import { el, openModal } from './ui.js';

const TYPE_ICON = {
  Engine: 'engine', Tank: 'tank', Payload: 'payload', Support: 'support',
  Tech: 'tech', Mission: 'mission', Event: 'event',
};

// Hand-written gameplay hints per card, shown on zoom. These describe what the
// *engine* will actually do, so new players learn the scripted behaviors.
const HINTS = {
  E01: 'Reusable: if the craft lands back on Earth, this engine returns to your hand on touchdown (free relaunches later).',
  E02: 'Cheap and 90% reliable, but single-use: it is discarded when the craft is recovered. As a Basic card you can always buy one, even if it is not in the market.',
  E03: 'Refuses to launch without at least one Cryo Tank on the rocket. Big thrust for heavy stacks.',
  E04: 'Only Thrust 3 — fine for light probes (Mass ≤ 3), useless for heavy stacks. 90% reliable.',
  E05: 'Gains +1 Thrust (6→7) if the rocket carries a Cryogenic tank. Reusable: returns to hand after an Earth recovery.',
  E06: 'Monster thrust but only 60% launch reliability. Consider a Flight Computer (+1) or Precision Guidance tech.',
  E07: 'Stage it pre-flight: +2 Range and it still counts as your Engine for the whole flight (checks, landings, missions). Discarded afterwards.',
  T01: 'The workhorse: Range 5, Mass 2. Basic — always purchasable.',
  T02: 'Range 8 for Mass 3, and it unlocks the Hydrogen Core engine. Counts as Cryogenic for techs and engine bonuses.',
  T03: 'Tiny tank. Mid-flight you can jettison it for +1 bonus Range (and the craft gets lighter for relaunch checks).',
  T04: 'Jettison mid-flight for +2 bonus Range. Great for stretching a Mars or Moon trip.',
  T05: 'Required to carry any Crewed payload (Crew Capsule). Range 5, Mass 2.',
  T06: 'Huge Range 12, but Mass 4 demands a powerful engine (Thrust ≥ 4 + payload).',
  P01: 'Deploy it at an orbital node: it becomes its own craft. Every Maintenance it automatically pays 1 Credit (at Earth ZOI or closer) or 1 VP (beyond) if it has 1 Energy — give it a Solar Panel. It also earns 1 Credit whenever a rival flies beyond Earth ZOI (once per round).',
  P02: 'Deploy in orbit or beyond: pays 1 Credit near Earth or 1 VP beyond Earth ZOI every Maintenance, automatically, as long as it has Energy (attach a Power card).',
  P03: 'While at Moon Orbit or beyond, activate it (2 Energy, once per round) for 1 VP — 2 VP during a storm event. Deadweight: the craft flies with −1 Range while it stays attached.',
  P04: 'Enables Crewed missions (needs a Pressurized Tank on the rocket). Spends 1 Energy at every launch/relaunch — pack a Battery. Returns to hand after Earth recovery.',
  P05: 'Cheap Mass-1 satellite. No income, but it counts as a deployed asset: +1 VP at game end and it satisfies satellite-deployment missions.',
  P06: 'Lets the craft land on a surface without burning extra Range for a propulsive landing. Satisfies "Lander" mission requirements.',
  P07: 'Needed for Lunar Sample Return. Reusable: comes back to your hand after Earth recovery.',
  P08: 'The station core. Park it at High Orbit (GEO) with a Power card, a LifeSupport card and one more Scientific/Electronics card attached, and it auto-designates as an On-Orbit Station (missions + docking target + 1 Credit per Maintenance).',
  P09: 'Deploy on the Moon or Mars surface. Pays 1 VP every Maintenance if it has Energy — pack an RTG (solar panels burn up entering Mars atmosphere).',
  P10: 'Deploy at GEO or beyond. Pays 1 VP per Maintenance (2 VP during storm events) while it has Energy.',
  P11: 'Deploy in orbit. Once per round one of your craft at the same node may draw 1 of its Energy for +2 Range. Give it Solar Panels. Deadweight: −1 Range while it rides on the carrier — regained the moment it is deployed.',
  S01: 'Reentry heat protection — survives atmospheric entry and can be jettisoned to aerobrake (+2 Range). It does NOT land the craft: pair it with a parachute, airbags, a Lander, or a propulsive landing. Basic — always purchasable.',
  S02: 'Parachute — Earth-only landing (needs thick air). Pays +1 Credit after touchdown. Single use.',
  S03: 'Reusable reentry heat shield (Earth or Mars). Survives one aerobrake per flight and returns to hand after Earth recovery. Not a landing device on its own.',
  S04: 'Guided parachute — Earth-only landing; +1 Credit after touchdown; returns to hand after recovery.',
  S05: 'Required for Docking missions. Docking costs 1 Energy. Reusable.',
  S06: 'Docking hardware + engine tug: when you activate the craft in orbit, spend 1 Energy for +1 Range. Reusable.',
  S07: 'Generates 2 Energy every round while in space — the cheapest way to keep satellites paying out. Burns up if the craft enters an atmosphere.',
  S08: 'Generates 3 Energy every round, anywhere (even on Mars). Mass 1 counts toward launch checks.',
  S09: 'One-shot +2 Energy, usable anywhere and any time — the game spends it automatically when a craft is short on Energy (launching a Crew Capsule, paying a mission op).',
  S10: 'Spend 1 Energy at launch for +1 Reliability on that check. Pair with risky engines.',
  S11: 'Activate for 1 Energy (once per round): +1 VP during a storm event or at Sun Orbit and beyond. Required by Deep Space Probe / Asteroid Rendezvous missions.',
  S12: 'Station module (LifeSupport). On your GEO station it also gives +1 VP to crewed missions that dock there.',
  S13: 'Station module (Scientific). On a GEO station it automatically pays 1 VP every Maintenance for 1 Energy.',
  S14: 'Propulsive landings cost no extra Range (still need an Engine). Counts as a Lander for mission requirements.',
  S15: 'One-shot +1 Energy, usable anywhere and any time — spent automatically when the craft is short on Energy. Mass 1 counts toward launch checks. Basic — always purchasable.',
  C01: 'Your Reusable engines get +1 Reliability, and every Reusable card you recover on Earth pays 1 Credit.',
  C02: 'Rockets with a Cryo Tank get +1 Reliability at launch.',
  C03: '+1 Reliability on every launch check. The simplest safety upgrade.',
  C04: 'Payload Mass counts as 1 less (min 1) for Thrust checks — small engines can lift bigger payloads.',
  C05: 'Transfer Window crossings cost you 1 less Range (min 0). Mars becomes much cheaper.',
  C06: 'When a launch roll fails you may pay 2 Credits to reroll it once. The game will ask you.',
  C07: '+1 Credit every time you complete a Commercial mission.',
  C08: 'Basic cards (Sterling Booster, Standard Tank, Heat Shield, Basic Battery) cost you 1 Credit less (minimum 1).',
  C09: 'All your Stage bonuses give +1 extra Range.',
  C10: 'Your deployed assets beyond Earth ZOI get +1 Power — deep-space probes work every round.',
  P12: 'A plain payload with no abilities — just fills the "carry a payload" requirement. Basic: always purchasable for 1 Credit. Mass 1.',
  P13: 'A plain payload, Mass 2 — the cheap way to satisfy "payload Mass 2+" missions. Basic — always purchasable.',
  P14: 'A plain heavy payload, Mass 3 — for Mass-3 requirements, but it stresses your Thrust budget and adds Deadweight (−1 Range while attached). Basic — always purchasable.',
  M21: 'Standing contract, always available: fly Earth → Sub-Orbital → Earth and land (parachute or propulsive). No payload needed. Each agency may claim it once per game for 2 Credits + 1 VP — a guaranteed first job.',
  S16: 'Airbag landing for an Uncrewed craft, Earth or Mars — no parachute or engine needed. Single use. Also counts as a Lander for missions.',
  S17: 'Splashdown Kit — reusable Earth-only water landing; +1 Credit after recovery; returns to hand.',
  EV14: 'Starter Event (round 1, revealed at setup): every craft that lands safely on Earth this round returns ALL its unstaged parts to hand, Reusable or not. Landing devices expended during touchdown stay discarded.',
  EV15: 'Starter Event (round 1, revealed at setup): every agency immediately gains 3 Credits — a funding-first opening.',
  EV16: 'Starter Event (round 1, revealed at setup): every agency has 1 extra command turn this round — a tempo opening.',
};

export function hintFor(uid) {
  const cid = cidOf(uid);
  if (HINTS[cid]) return HINTS[cid];
  const c = cardOf(uid);
  if (c.type === 'Mission') return 'Missions complete automatically the moment one of your craft meets every listed condition — the rewards are paid instantly and the mission leaves the display.';
  if (c.type === 'Event') return 'Events are revealed each Planning Phase and apply to everyone for the round.';
  return c.text;
}

export function renderCard(uid, { size = '', onClick = null, zoomable = true } = {}) {
  const c = cardOf(uid);
  if (!c) return el('div', { class: 'card' }, '?');
  const root = el('div', { class: `card type-${c.type} ${size}` });
  const icon = `assets/icons/${TYPE_ICON[c.type]}.png`;

  const top = el('div', { class: 'c-top' },
    el('img', { class: 'c-typeicon', src: icon, alt: c.type }),
    el('div', { class: 'c-name' }, c.name),
  );
  if (c.type !== 'Mission' && c.type !== 'Event') top.append(el('div', { class: 'c-cost', title: `Costs ${c.cost} Credits` }, String(c.cost)));
  root.append(top);
  if (c.tier) root.append(el('div', { class: 'c-tier' }, c.tier.replace('Tier ', 'T')));

  const art = el('div', { class: 'c-art' });
  if (c.art) art.style.backgroundImage = `url("${c.art}")`;
  else art.append(el('img', { class: 'ph', src: icon, alt: '' }));
  root.append(art);

  if (c.tags.length) root.append(el('div', { class: 'c-tags' }, c.tags.join(' · ')));

  const stats = el('div', { class: 'c-stats' });
  const stat = (cls, iconName, val, title) => stats.append(
    el('div', { class: `stat ${cls}`, title },
      el('img', { src: `assets/icons/${iconName}.png`, alt: '' }), String(val)));
  if (c.thrust != null) stat('s-thrust', 'thrust', c.thrust, 'Thrust — must be ≥ total rocket Mass to launch');
  if (c.range != null) stat('s-range', 'range', c.range, c.range < 0
    ? 'Deadweight — Range penalty while this card is attached; regained when it leaves the craft in flight'
    : 'Range — travel budget (1 per node crossing)');
  if (c.mass != null) stat('s-mass', 'mass', c.mass, 'Mass — weighs against engine Thrust');
  if (c.reliability != null) stat('s-rel', 'reliability', c.reliability, `Reliability — launch succeeds on a d10 roll of ${c.reliability} or less`);
  if (c.energy != null) stat('s-energy', 'energy', (c.energyMode === 'Gen' ? '+' : '') + Math.abs(c.energy),
    c.energyMode === 'Gen' ? 'Generates Energy each round' : c.energyMode === 'Burst' ? 'One-shot Energy burst' : 'Energy cost to operate');
  if (c.type === 'Mission') {
    stat('s-vp', 'class', c.vp + ' VP', 'Victory Point reward');
    if (c.rewardCredits) stat('s-cr', 'credits', c.rewardCredits + ' Cr', 'Credit reward');
  }
  if (stats.children.length) root.append(stats);

  if (c.text) root.append(el('div', { class: 'c-text', html: c.text.replaceAll('<br>', '<br>') }));

  root.addEventListener('click', e => {
    if (onClick) onClick(uid, e);
    else if (zoomable) zoomCard(uid);
  });
  return root;
}

export function zoomCard(uid, extraActions = []) {
  const c = cardOf(uid);
  const wrap = el('div', { style: 'display:flex; gap:18px; flex-wrap:wrap; align-items:flex-start;' });
  const big = renderCard(uid, { zoomable: false });
  big.style.setProperty('--cw', '300px');
  big.style.cursor = 'default';
  const side = el('div', { style: 'flex:1; min-width: 240px; max-width: 380px;' },
    el('h2', {}, c.name),
    el('div', { class: 'm-sub' }, `${c.type}${c.tier ? ' · ' + c.tier : ''}${c.tags.length ? ' · ' + c.tags.join(', ') : ''}`),
    el('div', { class: 'hint-box' }, '💡 ', hintFor(uid)),
    c.flavor ? el('div', { class: 'm-sub', style: 'font-style: italic;' }, c.flavor) : null,
  );
  if (extraActions.length) {
    const acts = el('div', { class: 'modal-actions', style: 'justify-content:flex-start;' });
    for (const a of extraActions) acts.append(a);
    side.append(acts);
  }
  wrap.append(big, side);
  return openModal(wrap);
}
