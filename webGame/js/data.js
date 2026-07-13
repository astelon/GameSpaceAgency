// Card database + client-side mirror of the core rules (for previews/hints).
// The PHP engine is authoritative; everything here is UX support.

export let CARDS = {};

export async function loadCards() {
  const res = await fetch('data/cards.json');
  if (!res.ok) throw new Error(`Card database failed to load (HTTP ${res.status})`);
  CARDS = await res.json();
}

export function cardOf(uid) {
  return CARDS[String(uid).split('#')[0]];
}
export function cidOf(uid) { return String(uid).split('#')[0]; }
export function hasTag(uid, tag) { return cardOf(uid)?.tags.includes(tag); }

// ---------------------------------------------------------------- map
export const NODES = {
  earth:     { name: 'Earth',             short: 'Earth',    surface: true,  atmo: true },
  subEarth:  { name: 'Sub-Orbital Earth', short: 'Sub-Orb',  surface: false, atmo: true },
  leo:       { name: 'LEO',               short: 'LEO',      surface: false, atmo: false },
  geo:       { name: 'High Orbit (GEO)',  short: 'GEO',      surface: false, atmo: false },
  earthZoi:  { name: 'Earth ZOI',         short: 'ZOI',      surface: false, atmo: false },
  moonOrbit: { name: 'Moon Orbit',        short: 'Moon Orb', surface: false, atmo: false },
  subMoon:   { name: 'Sub-Orbital Moon',  short: 'Sub-Orb',  surface: false, atmo: false },
  moon:      { name: 'Moon',              short: 'Moon',     surface: true,  atmo: false },
  sunOrbit:  { name: 'Sun Orbit',         short: 'Sun Orb',  surface: false, atmo: false },
  marsZoi:   { name: 'Mars ZOI',          short: 'ZOI',      surface: false, atmo: false },
  marsHigh:  { name: 'Mars High Orbit',   short: 'High Orb', surface: false, atmo: false },
  marsLow:   { name: 'Mars Low Orbit',    short: 'Low Orb',  surface: false, atmo: false },
  subMars:   { name: 'Sub-Orbital Mars',  short: 'Sub-Orb',  surface: false, atmo: false },
  mars:      { name: 'Mars Surface',      short: 'Mars',     surface: true,  atmo: true },
};
export const EDGES = [
  ['earth','subEarth'], ['subEarth','leo'], ['leo','geo'], ['geo','earthZoi'],
  ['earthZoi','moonOrbit'], ['moonOrbit','subMoon'], ['subMoon','moon'],
  ['earthZoi','sunOrbit'], ['sunOrbit','marsZoi','tw'],
  ['marsZoi','marsHigh'], ['marsHigh','marsLow'], ['marsLow','subMars'], ['subMars','mars'],
];
export const TW_CYCLE = [3,2,1,0,1,2,3,4];
// Sub-orbital arcs decay at end of round: craft still there come down on the surface below.
export const SUBORBITAL = { subEarth: 'earth', subMoon: 'moon', subMars: 'mars' };
export const MOON_BRANCH = ['moonOrbit','subMoon','moon'];
export const MARS_BRANCH = ['marsZoi','marsHigh','marsLow','subMars','mars'];
export const EARTH_ONLY_REENTRY = ['S02','S04'];

export function edgeBetween(a, b) {
  for (const e of EDGES) {
    if ((e[0]===a && e[1]===b) || (e[0]===b && e[1]===a)) return { tw: e[2]==='tw' };
  }
  return null;
}
export function neighborsOf(node) {
  const out = [];
  for (const e of EDGES) {
    if (e[0]===node) out.push(e[1]);
    if (e[1]===node) out.push(e[0]);
  }
  return out;
}
export const isSurface = n => !NODES[n] || NODES[n].surface;
export const isAtmo = n => NODES[n] ? NODES[n].atmo : true;
export const inSpace = n => !!NODES[n] && !NODES[n].surface;
export const beyondZoi = n => !['earth','subEarth','leo','geo','earthZoi'].includes(n);

// ---------------------------------------------------------------- derived rules
export function eventId(g) { return g.event ? cidOf(g.event) : null; }
export function stormActive(g) { return ['EV01','EV06','EV09'].includes(eventId(g)); }
export function handLimit(g) { return eventId(g) === 'EV08' ? 7 : 5; }
export function hasTech(g, seat, cid) {
  return g.players[seat].tableau.some(u => cidOf(u) === cid);
}
export function twCost(g, seat) {
  let tw = TW_CYCLE[g.twIdx];
  const ev = eventId(g);
  if (ev === 'EV06') tw = Math.min(5, tw + 2);
  if (ev === 'EV07') tw = Math.max(0, tw - 2);
  if (hasTech(g, seat, 'C05')) tw = Math.max(0, tw - 1);
  return tw;
}
export function basicCost(g, seat, card) {
  let c = card.cost;
  if (card.tags.includes('Basic') && hasTech(g, seat, 'C08')) c = Math.max(1, c - 1);
  return c;
}

export function craftCards(craft, type, tag) {
  return craft.cards.filter(u => {
    const c = cardOf(u);
    if (type && c.type !== type) return false;
    if (tag && !c.tags.includes(tag)) return false;
    return true;
  });
}
export const craftEngine = c => craftCards(c, 'Engine')[0] || null;
export const craftPayload = c => craftCards(c, 'Payload')[0] || null;

export function craftMass(g, craft) {
  let mass = 0;
  for (const u of craft.cards) {
    const c = cardOf(u);
    if (c.mass == null || c.type === 'Engine') continue;
    let m = c.mass;
    if (c.type === 'Payload' && hasTech(g, craft.owner, 'C04')) m = Math.max(1, m - 1);
    mass += m;
  }
  return mass;
}
// Total Thrust of all mounted Engines (v0.5: up to 2 engines cluster).
export function craftThrust(g, craft) {
  let t = 0;
  for (const eng of craftCards(craft, 'Engine')) {
    let et = cardOf(eng).thrust || 0;
    if (cidOf(eng) === 'E05' && craftCards(craft, null, 'Cryogenic').length) et += 1;
    t += et;
  }
  return t;
}
// Mirrors sar_craft_reliability: per-engine value (base + Reusable Refurb) +
// craft-wide modifiers; a two-engine cluster uses the lowest value − 1.
export function craftReliability(g, craft, useFc) {
  const engines = craftCards(craft, 'Engine');
  if (!engines.length) return [0, ['no engine']];
  const seat = craft.owner;
  let craftMod = 0;
  const craftMods = [];
  if (hasTech(g, seat, 'C02') && craftCards(craft, null, 'Cryogenic').length) { craftMod++; craftMods.push('Cryo Handling +1'); }
  if (hasTech(g, seat, 'C03')) { craftMod++; craftMods.push('Precision Guidance +1'); }
  if (useFc) { craftMod++; craftMods.push('Flight Computer +1'); }
  const ev = eventId(g);
  if (ev === 'EV01') { craftMod -= 2; craftMods.push('Solar Storm -2'); }
  if (ev === 'EV09') { craftMod -= 1; craftMods.push('Solar Flare Watch -1'); }
  let worst = null, worstMods = [];
  for (const eng of engines) {
    const c = cardOf(eng);
    let rel = c.reliability ?? 5;
    const mods = [engines.length > 1 ? `${c.name} base ${c.reliability ?? 5}` : `base ${c.reliability ?? 5}`];
    if (hasTech(g, seat, 'C01') && c.tags.includes('Reusable')) { rel++; mods.push('Reusable Refurb +1'); }
    if (worst === null || rel < worst) { worst = rel; worstMods = mods; }
  }
  let rel = worst + craftMod;
  const mods = [...worstMods, ...craftMods];
  if (engines.length === 2) { rel -= 1; mods.push('engine cluster -1'); }
  return [rel, mods];
}
export function craftPower(g, craft) {
  let p = 0;
  for (const u of craft.cards) {
    const c = cardOf(u);
    if (c.energyMode !== 'Gen') continue;
    if (cidOf(u) === 'S07' && !inSpace(craft.node)) continue;
    p += c.energy;
  }
  if (p > 0 && craft.deployed && beyondZoi(craft.node) && hasTech(g, craft.owner, 'C10')) p++;
  return p;
}
// Launch Range: tank Range sum minus Deadweight penalties (negative printed
// Range on non-tank cards — v0.5.1), never below 0. Mirrors sar_launch_range.
export function tankRange(craft) {
  let r = 0;
  for (const u of craft.cards) {
    const c = cardOf(u);
    if (c.range == null) continue;
    if (c.type === 'Tank') r += c.range;
    else if (c.range < 0) r += c.range;
  }
  return Math.max(0, r);
}
// Total Deadweight currently attached (positive number, 0 if none).
export function deadweight(craft) {
  return craft.cards.reduce((s, u) => {
    const c = cardOf(u);
    return s + (c.type !== 'Tank' && (c.range || 0) < 0 ? -c.range : 0);
  }, 0);
}
export function stageBonus(g, seat, uid) {
  const bonus = { E07: 2, T03: 1, T04: 2 }[cidOf(uid)] ?? 0;
  return bonus > 0 && hasTech(g, seat, 'C09') ? bonus + 1 : bonus;
}

// Mirrors sar_passive_landing: how a decaying sub-orbital craft would auto-land
// at end of round without a command turn. Returns a label, or null (it crashes).
export function passiveLanding(craft, surface) {
  const engine = !!craftEngine(craft);
  const legs = craft.cards.some(u => cidOf(u) === 'S14');
  const lander = craftCards(craft, 'Payload', 'Lander').length > 0;
  const crewed = craftCards(craft, 'Payload', 'Crewed').length > 0;
  if (surface === 'moon') {
    return engine && (legs || lander) ? (legs ? 'Landing Legs + Engine' : 'Lander + Engine') : null;
  }
  const chutes = surface === 'earth' ? craftCards(craft, null, 'Parachute') : [];
  const reusable = chutes.find(u => hasTag(u, 'Reusable'));
  if (reusable) return cardOf(reusable).name;
  if (engine && legs) return 'Landing Legs + Engine';
  if (chutes.length) return cardOf(chutes[0]).name;
  if (lander) return 'Lander';
  if (!crewed && craftCards(craft, null, 'Airbag').length) return 'airbags';
  return null;
}

// ---------------------------------------------------------------- plan simulation
// Mirrors flight.php. Returns a rich result for the planner UI.
export function simulatePlan(g, craftIn, plan) {
  const craft = JSON.parse(JSON.stringify(craftIn));
  const seat = craft.owner;
  const res = { ok: true, error: null, steps: [], checks: [], deploys: [],
                warnings: [], missions: [], energyUsed: 0, assets: [] };
  const path = plan.path || [craft.node];

  const spend = (n, why) => {
    let avail = craft.energy;
    const bats = craft.cards.filter(u => cardOf(u).energyMode === 'Burst');
    for (const b of bats) avail += cardOf(b).energy;
    if (avail < n) { throw new RuleFail(`Needs ${n} Energy for ${why} (batteries included, only ${avail} available)`); }
    while (craft.energy < n && bats.length) {
      const b = bats.shift();
      craft.energy += cardOf(b).energy;
      craft.cards = craft.cards.filter(u => u !== b);
      res.warnings.push(`A ${cardOf(b).name} will be expended for ${why}.`);
    }
    craft.energy -= n;
    res.energyUsed += n;
  };
  const dwRegain = (uids) => {
    for (const u of uids) {
      const c = cardOf(u);
      if (c.type !== 'Tank' && (c.range || 0) < 0) {
        craft.range += -c.range;
        res.steps.push({ note: `Deadweight dropped (${c.name}): +${-c.range} Range` });
      }
    }
  };
  const stage = (uid, when) => {
    if (!craft.cards.includes(uid)) throw new RuleFail('Stage card not on craft');
    if (!cardOf(uid).tags.includes('Stageable')) throw new RuleFail(`${cardOf(uid).name} is not Stageable`);
    const bonus = stageBonus(g, seat, uid);
    if (cardOf(uid).type === 'Engine') craft.stagedEngineFlight = true;
    craft.cards = craft.cards.filter(u => u !== uid);
    craft.range += bonus;
    res.steps.push({ note: `Stage ${cardOf(uid).name} (${when}): +${bonus} Range` });
    dwRegain([uid]);
  };

  class RuleFail extends Error {}

  try {
    // step-0 extras
    if (plan.tug) {
      if (!craft.cards.some(u => cidOf(u) === 'S06')) throw new RuleFail('No Orbital Tug aboard');
      if (!inSpace(craft.node)) throw new RuleFail('Orbital Tug works only in orbit');
      spend(1, 'the Orbital Tug boost');
      craft.range += 1;
      res.steps.push({ note: 'Orbital Tug: +1 Range' });
    }
    if (plan.depot) {
      const depot = g.crafts[plan.depot];
      if (!depot || depot.node !== craft.node) throw new RuleFail('Fuel Depot is not at this node');
      if (depot.energy < 1) throw new RuleFail('Fuel Depot has no Energy');
      if (depot.depotUsedRound === g.round) throw new RuleFail('Fuel Depot already used this round');
      craft.range += 2;
      res.steps.push({ note: 'Fuel Depot: +2 Range' });
    }
    if (plan.preStage) stage(plan.preStage, 'pre-flight');
    doDeploys(0);

    for (let k = 1; k < path.length; k++) {
      const from = craft.node, to = path[k];
      const edge = edgeBetween(from, to);
      if (!edge) throw new RuleFail(`${NODES[from].name} is not connected to ${NODES[to].name}`);

      if (isSurface(from)) {
        const thrust = craftThrust(g, craft), mass = craftMass(g, craft);
        if (!craftEngine(craft)) throw new RuleFail(`No Engine — cannot launch from ${NODES[from].name}`);
        if (craftCards(craft, 'Engine').some(u => cidOf(u) === 'E03') && !craftCards(craft, 'Tank', 'Cryogenic').length)
          throw new RuleFail('The Hydrogen Core engine requires a Cryo Tank');
        if (thrust < mass) throw new RuleFail(`Launch check fails: Thrust ${thrust} < Mass ${mass}`);
        for (const u of craft.cards) if (cidOf(u) === 'P04') spend(1, 'the Crew Capsule launch');
        let fc = false;
        if (plan.flightComputer && craft.cards.some(u => cidOf(u) === 'S10')) {
          try { spend(1, 'the Flight Computer'); fc = true; } catch { /* optional */ }
        }
        const [rel, mods] = craftReliability(g, craft, fc);
        res.checks.push({ at: from, rel, mods, thrust, mass });
      }
      if (plan.midStages?.[k]) stage(plan.midStages[k], 'mid-flight');
      if (plan.aerobrake?.[k]) {
        const uid = plan.aerobrake[k];
        if (!craft.cards.includes(uid) || !cardOf(uid).tags.includes('Reentry')) throw new RuleFail('Aerobrake needs a Reentry card on the craft');
        const eChain = ['earthZoi','geo','leo','subEarth','earth'], mChain = MARS_BRANCH;
        const desc = (ch) => ch.includes(to) && ch.includes(from) && ch.indexOf(to) > ch.indexOf(from);
        const descE = eChain.includes(to) && (!eChain.includes(from) ? false : eChain.indexOf(to) > eChain.indexOf(from));
        if (!descE && !desc(mChain)) throw new RuleFail('Aerobraking only while descending toward Earth or Mars');
        const keep = cidOf(uid) === 'S03' && !craft.ceramicAeroUsed;
        if (keep) craft.ceramicAeroUsed = true;
        else craft.cards = craft.cards.filter(u => u !== uid);
        craft.range += 2;
        res.steps.push({ note: `Aerobrake with ${cardOf(uid).name}: +2 Range${keep ? ' (kept)' : ' (expended)'}` });
      }

      let cost = edge.tw ? twCost(g, seat) : 1;
      let landNote = '';
      if (isSurface(to)) {
        const choice = plan.landing?.[k] || {};
        const hasLegs = craft.cards.some(u => cidOf(u) === 'S14');
        const engine = !!craftEngine(craft) || craft.stagedEngineFlight;
        if (to === 'moon') {
          if (!engine) throw new RuleFail('Moon landing is propulsive — needs an Engine');
          landNote = 'propulsive Moon landing';
        } else if (choice.method === 'reentry') {
          const uid = choice.card;
          if (!uid || !craft.cards.includes(uid)) throw new RuleFail('Pick a landing device (parachute or airbags)');
          const t = cardOf(uid).tags;
          const isChute = t.includes('Parachute'), isAirbag = t.includes('Airbag');
          if (!isChute && !isAirbag) {
            throw new RuleFail(t.includes('Reentry')
              ? `${cardOf(uid).name} shields against reentry heat but cannot land the craft`
              : `${cardOf(uid).name} is not a landing device`);
          }
          if (isChute && to !== 'earth') throw new RuleFail(`${cardOf(uid).name} only works in Earth's atmosphere`);
          if (isAirbag && craftCards(craft, 'Payload', 'Crewed').length) throw new RuleFail('Airbags are uncrewed-only');
          craft.usedReentry = true;
          if (t.includes('Reusable')) craft.usedReusableReentry = true;
          else craft.cards = craft.cards.filter(u => u !== uid);
          landNote = `land with ${cardOf(uid).name}`;
        } else if (choice.method === 'lander') {
          if (!craftCards(craft, null, 'Lander').length) throw new RuleFail('No Lander on this craft');
          landNote = 'set down with Lander';
        } else if (choice.method === 'propulsive') {
          if (!engine) throw new RuleFail('Propulsive landing requires an Engine');
          cost += hasLegs ? 0 : 1;
          landNote = 'propulsive landing' + (hasLegs ? ' (Landing Legs: no extra Range)' : ' (+1 Range)');
        } else {
          throw new RuleFail(`Landing at ${NODES[to].name} needs a landing method (parachute, airbags, a Lander, or propulsive)`);
        }
      }
      if (craft.range < cost) throw new RuleFail(`Not enough Range for ${NODES[to].name} (needs ${cost}, has ${craft.range})`);
      craft.range -= cost;
      craft.node = to;
      craft.history.push(to);
      if (isAtmo(to) && !isAtmo(from)) {
        for (const u of [...craft.cards]) if (cidOf(u) === 'S07') {
          craft.cards = craft.cards.filter(x => x !== u);
          res.warnings.push('The Solar Panel will burn up entering the atmosphere.');
        }
      }
      res.steps.push({ from, to, cost, note: landNote });
      doDeploys(k);
      if (plan.dock != null && +plan.dock === k) dock();
    }
    if (path.length === 1 && plan.dock != null && +plan.dock === 0) dock();

    for (const op of plan.operate || []) {
      const cid = cidOf(op.card);
      if (cid === 'P03') {
        if (!beyondZoi(craft.node)) throw new RuleFail('Science Module needs Moon Orbit or beyond');
        spend(2, 'Science Module research');
        res.steps.push({ note: `Science Module: +${stormActive(g) ? 2 : 1} VP` });
      } else if (cid === 'S11') {
        const deep = ['sunOrbit', ...MARS_BRANCH].includes(craft.node);
        if (!stormActive(g) && !deep) throw new RuleFail('Sensor Array pays only during a storm or at Sun Orbit and beyond');
        spend(1, 'the Sensor Array');
        res.steps.push({ note: `Sensor Array: +${eventId(g) === 'EV09' ? 2 : 1} VP` });
      }
    }
  } catch (e) {
    res.ok = false;
    res.error = e.message;
  }

  // Sub-orbital arcs decay: ending the plan there means an automatic touchdown
  // (or a crash) during Maintenance unless a later command turn moves the craft.
  if (res.ok && SUBORBITAL[craft.node]) {
    const surf = SUBORBITAL[craft.node];
    const dev = passiveLanding(craft, surf);
    res.warnings.push(dev
      ? `Sub-orbital arcs are not stable orbits: if the craft is still here at the end of the round it will touch down on ${NODES[surf].name} automatically (${dev}).`
      : `⚠ Sub-orbital arcs are not stable orbits: with no parachute, airbags, Lander, or Landing Legs aboard, this craft will CRASH at the end of the round unless a later command turn lands it propulsively or climbs to orbit.`);
  }

  function dock() {
    if (craft.node !== 'geo') throw new RuleFail('Docking happens at High Orbit (GEO)');
    if (!craftCards(craft, null, 'Docking').length) throw new RuleFail('Docking needs a Docking support card');
    const station = Object.values(g.crafts).find(c => c.isStation && c.node === 'geo' && c.id !== craft.id);
    if (!station) throw new RuleFail('No On-Orbit Station is at High Orbit');
    spend(1, 'the docking maneuver');
    craft.docked = true;
    craft.dockedHab = station.cards.some(u => cidOf(u) === 'S12');
    res.steps.push({ note: `Dock with ${station.name}` + (eventId(g) === 'EV05' ? ' (+2 VP: Docking Opportunity)' : '') });
  }
  function doDeploys(k) {
    for (const d of plan.deploys || []) {
      if (+d.step !== k) continue;
      if (!craft.cards.includes(d.payload)) throw new RuleFail('Deploy payload not aboard');
      const card = cardOf(d.payload);
      const rover = cidOf(d.payload) === 'P09';
      if (rover) { if (!['moon','mars'].includes(craft.node)) throw new RuleFail('The Rover deploys on the Moon or Mars surface'); }
      else if (!inSpace(craft.node) || SUBORBITAL[craft.node]) throw new RuleFail(`${card.name} deploys at a stable orbital node — a sub-orbital arc decays by the end of the round`);
      const assetCards = [d.payload, ...(d.supports || [])];
      craft.cards = craft.cards.filter(u => !assetCards.includes(u));
      dwRegain(assetCards);
      res.assets.push({ node: craft.node, cards: assetCards, deployed: true, owner: seat,
                        history: [craft.node], isStation: false });
      res.steps.push({ note: `Deploy ${card.name} at ${NODES[craft.node].name}` });
    }
  }

  res.finalCraft = craft;
  if (res.ok) res.missions = missionPreview(g, craft, res.assets);
  return res;
}

// Which display missions would this simulated flight/asset state complete?
export function missionPreview(g, craft, assets = []) {
  const out = [];
  for (const muid of g.missions) {
    if (!muid) continue;
    const mid = cidOf(muid);
    let ok = checkMission(g, mid, craft);
    if (!ok) for (const a of assets) if (checkMission(g, mid, { ...emptyCraftDefaults(), ...a, owner: craft.owner })) ok = true;
    if (ok) out.push(muid);
  }
  return out;
}
function emptyCraftDefaults() {
  return { docked: false, usedReentry: false, usedReusableReentry: false, isStation: false, energy: 99, cards: [], history: [] };
}

function seqIn(hist, needle) {
  let i = 0;
  for (const n of hist) if (n === needle[i] && ++i === needle.length) return true;
  return false;
}

// Mirrors sar_payload_meets: any SINGLE payload card must satisfy the tag +
// Mass requirement (v0.5.1 — payload masses never add up across cards).
function payloadMeets(craft, tag, minMass = 0, orTag = null) {
  return craftCards(craft, 'Payload').some(u => {
    const c = cardOf(u);
    const tagOk = tag === null || c.tags.includes(tag) || (orTag !== null && c.tags.includes(orTag));
    return tagOk && (c.mass || 0) >= minMass;
  });
}

export function checkMission(g, mid, craft) {
  const crewed = payloadMeets(craft, 'Crewed') && craftCards(craft, 'Tank', 'Pressurized').length > 0;
  const node = craft.node, hist = craft.history || [], atEarth = node === 'earth';
  const engineOr = !!craftEngine(craft) || !!craft.stagedEngineFlight;
  const canPay = n => {
    let e = craft.energy;
    for (const u of craft.cards) if (cardOf(u).energyMode === 'Burst') e += cardOf(u).energy;
    return e >= n;
  };
  const deployedSat = (inOrbit) => Object.values(g.crafts).some(c =>
    c.owner === craft.owner && c.deployed && (!inOrbit || inSpace(c.node)) &&
    c.cards.some(u => hasTag(u, 'Satellite')));
  switch (mid) {
    case 'M01': return node === 'leo' &&
      craftCards(craft, 'Payload').some(u => !cardOf(u).tags.includes('Crewed'));
    case 'M02': return atEarth && hist.includes('moonOrbit');
    case 'M03': return node === 'moon' && (craftCards(craft, null, 'Lander').length > 0 || engineOr);
    case 'M04': return node === 'marsHigh' && payloadMeets(craft, null, 2);
    case 'M05': return hist.includes('marsZoi') && payloadMeets(craft, 'Scientific', 2) &&
      craft.cards.some(u => cidOf(u) === 'S11') && canPay(2);
    case 'M06': return atEarth && craft.docked && crewed && craftCards(craft, null, 'Docking').length > 0 && engineOr;
    case 'M07': return atEarth && hist.includes('leo') && payloadMeets(craft, null, 2);
    case 'M08': return atEarth && hist.includes('geo') &&
      payloadMeets(craft, 'Scientific', 0, 'Electronics') && canPay(1);
    case 'M09': return node === 'leo' && seqIn(hist, ['leo','geo','leo']) && engineOr && deployedSat(true);
    case 'M10': return atEarth && craft.usedReentry && payloadMeets(craft, null, 1);
    case 'M11': return atEarth && seqIn(hist, ['earth','subEarth','earth']) &&
      payloadMeets(craft, 'Reusable') && craft.usedReusableReentry;
    case 'M12': return atEarth && hist.includes('moon') && craft.cards.some(u => cidOf(u) === 'P07') && craft.usedReentry;
    case 'M13': return atEarth && seqIn(hist, ['earth','subEarth','earth']) && crewed && craft.usedReentry;
    case 'M14': return craft.deployed && node === 'geo' && payloadMeets(craft, 'Satellite');
    case 'M15': return atEarth && seqIn(hist, ['earth','subEarth','earth']) && payloadMeets(craft, 'Scientific') && canPay(1);
    case 'M16': return craft.deployed && node === 'moonOrbit' && payloadMeets(craft, 'Satellite');
    case 'M17': return atEarth && hist.includes('moonOrbit') && crewed && craft.usedReentry;
    case 'M18': return craft.isStation && node === 'geo';
    case 'M19': return node === 'mars' && (craftCards(craft, null, 'Lander').length > 0 || engineOr);
    case 'M20': return hist.includes('sunOrbit') && payloadMeets(craft, 'Scientific', 2) &&
      craft.cards.some(u => cidOf(u) === 'S11') && canPay(2);
  }
  return false;
}
