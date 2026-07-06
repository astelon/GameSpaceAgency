// The orbital map: an interactive SVG styled after tts/board_v2.
import { NODES, EDGES, TW_CYCLE, twCost, craftPayload, cardOf } from './data.js';
import { el } from './ui.js';

export const POS = {
  earth:     [72, 330],  subEarth: [152, 330], leo: [232, 330], geo: [316, 330], earthZoi: [408, 330],
  moonOrbit: [480, 205], subMoon:  [556, 138], moon: [630, 84],
  sunOrbit:  [492, 452], marsZoi:  [648, 452],
  marsHigh:  [726, 402], marsLow:  [800, 356], subMars: [872, 310], mars: [944, 262],
};
const NS = 'http://www.w3.org/2000/svg';

function svgEl(tag, attrs = {}, ...children) {
  const n = document.createElementNS(NS, tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
    else n.setAttribute(k, v);
  }
  for (const c of children) n.append(c);
  return n;
}

// Renders the static map into `container`. Returns handles for dynamic bits.
export function renderBoard(container, { onNodeClick = null, onCraftClick = null, compact = false } = {}) {
  container.innerHTML = '';
  const svg = svgEl('svg', { viewBox: '0 0 1010 520', preserveAspectRatio: 'xMidYMid meet' });

  const defs = svgEl('defs');
  defs.innerHTML = `
    <radialGradient id="gEarth" cx="35%" cy="35%"><stop offset="0%" stop-color="#7fd0ff"/><stop offset="55%" stop-color="#2b7fd4"/><stop offset="100%" stop-color="#0c3a72"/></radialGradient>
    <radialGradient id="gMoon" cx="40%" cy="35%"><stop offset="0%" stop-color="#e8e8ee"/><stop offset="100%" stop-color="#7c7f8c"/></radialGradient>
    <radialGradient id="gMars" cx="40%" cy="35%"><stop offset="0%" stop-color="#ff9d6e"/><stop offset="100%" stop-color="#a03a1c"/></radialGradient>
    <radialGradient id="gSun" cx="50%" cy="50%"><stop offset="0%" stop-color="#ffe9a8"/><stop offset="100%" stop-color="#f5a52400"/></radialGradient>`;
  svg.append(defs);

  // decorative system rings
  const rings = svgEl('g');
  for (const r of [70, 130, 190, 255]) rings.append(svgEl('circle', { class: 'sysring', cx: 72, cy: 330, r }));
  for (const r of [55, 105, 155, 205]) rings.append(svgEl('circle', { class: 'sysring', cx: 944, cy: 262, r }));
  rings.append(svgEl('circle', { class: 'sysring', cx: 630, cy: 84, r: 44 }));
  svg.append(rings);
  if (!compact) {
    svg.append(svgEl('text', { class: 'syslabel', x: 180, y: 500 }, 'EARTH SYSTEM'));
    svg.append(svgEl('text', { class: 'syslabel', x: 630, y: 30 }, 'LUNAR SYSTEM'));
    svg.append(svgEl('text', { class: 'syslabel', x: 850, y: 500 }, 'MARS SYSTEM'));
  }

  // planets
  svg.append(
    svgEl('circle', { cx: 72, cy: 330, r: 26, fill: 'url(#gEarth)' }),
    svgEl('circle', { cx: 630, cy: 84, r: 15, fill: 'url(#gMoon)' }),
    svgEl('circle', { cx: 944, cy: 262, r: 22, fill: 'url(#gMars)' }),
    svgEl('circle', { cx: 560, cy: 452, r: 34, fill: 'url(#gSun)', opacity: .5 }),
  );

  // transfer window corridor
  const twBand = svgEl('g');
  twBand.append(svgEl('rect', { x: 540, y: 400, width: 66, height: 104, rx: 10,
    fill: '#b0498c14', stroke: '#b0498c55', 'stroke-dasharray': '4 4' }));
  const twText = svgEl('text', { class: 'tw-board-label', x: 573, y: 424 }, 'TW');
  const twVal = svgEl('text', { class: 'tw-board-label', x: 573, y: 448, 'font-size': 20 }, '?');
  const twCycle = svgEl('text', { x: 573, y: 492, fill: '#8a5a7a', 'font-size': 8.5, 'text-anchor': 'middle' }, '');
  twBand.append(twText, twVal, twCycle);
  svg.append(twBand);

  // edges
  const edgeEls = {};
  for (const e of EDGES) {
    const [a, b] = e;
    const line = svgEl('line', {
      class: 'edge' + (e[2] === 'tw' ? ' tw' : ''),
      x1: POS[a][0], y1: POS[a][1], x2: POS[b][0], y2: POS[b][1],
    });
    edgeEls[`${a}|${b}`] = line;
    svg.append(line);
  }

  // nodes
  const nodeEls = {};
  for (const [id, [x, y]] of Object.entries(POS)) {
    const g = svgEl('g');
    const r = NODES[id].surface ? 15 : 11;
    // invisible oversized hit area for touch screens
    g.append(svgEl('circle', {
      cx: x, cy: y, r: r + 13, fill: 'transparent',
      onclick: () => onNodeClick && onNodeClick(id),
    }));
    const circ = svgEl('circle', {
      class: 'node-circle' + (NODES[id].surface ? ' surface' : ''),
      cx: x, cy: y, r,
      onclick: () => onNodeClick && onNodeClick(id),
    });
    const title = svgEl('title', {}, NODES[id].name + (NODES[id].atmo ? ' (atmosphere)' : ''));
    circ.append(title);
    g.append(circ);
    const labelDy = (id === 'moon' || id === 'subMoon' || id === 'moonOrbit') ? -r - 7 : r + 13;
    g.append(svgEl('text', { class: 'node-label', x, y: y + labelDy }, compact ? NODES[id].short : NODES[id].name));
    svg.append(g);
    nodeEls[id] = circ;
  }

  const craftLayer = svgEl('g');
  svg.append(craftLayer);
  container.append(svg);

  return {
    svg, nodeEls, edgeEls, craftLayer,
    setTw(g, seat) {
      const base = TW_CYCLE[g.twIdx];
      const eff = twCost(g, seat);
      twVal.textContent = String(eff);
      twVal.setAttribute('fill', eff === 0 ? '#57d98a' : eff >= 4 ? '#ff6b6b' : '#ff8ac2');
      const cyc = TW_CYCLE.map((v, i) => i === g.twIdx ? `[${v}]` : v).join(' ');
      twCycle.textContent = cyc;
    },
    drawCrafts(g, { selected = null, mySeats = [] } = {}) {
      craftLayer.innerHTML = '';
      const byNode = {};
      for (const c of Object.values(g.crafts)) {
        if (c.node === 'assembly' || !POS[c.node]) continue;
        (byNode[c.node] ||= []).push(c);
      }
      for (const [node, list] of Object.entries(byNode)) {
        const [nx, ny] = POS[node];
        list.forEach((c, i) => {
          const ang = (i / Math.max(4, list.length)) * Math.PI * 2 - Math.PI / 2;
          const rad = 24 + 7 * Math.floor(i / 8);
          const x = nx + Math.cos(ang) * rad, y = ny + Math.sin(ang) * rad;
          const color = g.players[c.owner].color;
          const mine = mySeats.includes(c.owner);
          const grp = svgEl('g', { class: 'craft-marker', onclick: () => onCraftClick && onCraftClick(c.id) });
          const body = svgEl('g', { class: 'body', transform: `translate(${x},${y})` });
          const isAsset = c.deployed;
          if (isAsset) {
            body.append(svgEl('rect', { x: -6.5, y: -6.5, width: 13, height: 13, rx: 3,
              fill: color, stroke: c.id === selected ? '#f5c542' : '#0a0e1a', 'stroke-width': c.id === selected ? 2.5 : 1.2 }));
            body.append(svgEl('text', { x: 0, y: 3.4, 'text-anchor': 'middle', 'font-size': 9 }, c.isStation ? '🛰' : '📡'));
          } else {
            body.append(svgEl('path', { d: 'M0,-9 L6,7 L0,3.5 L-6,7 Z',
              fill: color, stroke: c.id === selected ? '#f5c542' : '#0a0e1a', 'stroke-width': c.id === selected ? 2.2 : 1.2 }));
          }
          const badges = [];
          if (c.range > 0) badges.push(`R${c.range}`);
          if (c.energy > 0) badges.push(`E${c.energy}`);
          if (badges.length) {
            body.append(svgEl('text', { class: 'craft-badge', x: 0, y: 18, 'text-anchor': 'middle',
              fill: mine ? '#f5c542' : '#8494b8' }, badges.join(' ')));
          }
          const pl = craftPayload(c);
          const tip = svgEl('title', {}, `${g.players[c.owner].name} — ${c.name}` +
            `\nRange ${c.range} · Energy ${c.energy}` + (pl ? `\nPayload: ${cardOf(pl).name}` : ''));
          body.append(tip);
          grp.append(body);
          craftLayer.append(grp);
        });
      }
    },
    highlightPath(path, candidates = []) {
      for (const elx of Object.values(nodeEls)) elx.classList.remove('sel-ok', 'sel-path', 'sel-bad');
      for (const elx of Object.values(edgeEls)) elx.classList.remove('path-hl');
      if (!path) return;
      for (const n of path) nodeEls[n]?.classList.add('sel-path');
      for (let i = 1; i < path.length; i++) {
        (edgeEls[`${path[i-1]}|${path[i]}`] || edgeEls[`${path[i]}|${path[i-1]}`])?.classList.add('path-hl');
      }
      for (const n of candidates) nodeEls[n]?.classList.add('sel-ok');
    },
  };
}
