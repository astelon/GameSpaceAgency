# Space Agency Race — Web Edition

A complete, playable web version of the board game defined in
[`Space_Agency.md`](../Space_Agency.md) (ruleset v0.2 / card list v0.3).

* **2–4 players**, online (room codes) or hot-seat on one device
* Full rules enforcement: rocket assembly, Thrust/Mass launch checks, d10
  reliability rolls, Range/orbital travel, Transfer Window cycle, staging,
  aerobraking, landings, deploys, docking, stations, Energy, all **20
  missions**, **13 events** and **10 technologies** scripted
* Teaching hints everywhere: every card explains what the engine will do with
  it, and the flight planner previews costs, success odds and which missions
  a flight will complete before you commit
* Animations: dice rolls, craft movement, round/mission banners, log feed
* Uses the existing card art, icons and stats from `cards/cards.csv`

## Architecture

| Layer | Tech | Notes |
| ----- | ---- | ----- |
| Backend | Plain PHP 7.4+/8.x, no framework | Server-authoritative rules engine (`api/engine/`), all randomness server-side |
| Storage | SQLite (`pdo_sqlite`) | Falls back to JSON files with `flock()` if SQLite is missing |
| Transport | AJAX polling every ~2 s | Works on any shared host — no WebSockets required |
| Frontend | Vanilla ES modules + CSS | No build step; edit files directly on the server |

Hot-seat mode uses the same server engine — one browser token controls all
seats — so the rules behave identically in both modes.

## Deploying to Hostinger (or any PHP shared host)

1. Upload the **contents of this `webGame/` folder** to the target directory
   (e.g. `public_html/spacerace/`). The `tools/` folder is optional on the
   server.
2. Make sure PHP ≥ 7.4 is selected (Hostinger defaults to 8.x — fine).
3. Ensure `api/data/` exists and is **writable** by PHP (755 usually works on
   Hostinger; the folder ships with a deny-all `.htaccess` so saves are never
   downloadable).
4. Open `https://your-domain/spacerace/` — that's it. No database setup: the
   SQLite file is created automatically at `api/data/games.db`.

Idle rooms are cleaned up automatically after 3 days.

## Local development

```bash
php -S 127.0.0.1:8080 -t webGame     # from the repo root
# open http://127.0.0.1:8080/
```

## Regenerating card data

Card stats, text and art references are generated from the master
`cards/cards.csv`:

```bash
python3 webGame/tools/build_data.py
```

This rewrites `data/cards.json`, `api/engine/cards_data.php` and re-copies
art/icons into `assets/`. Run it whenever the CSV changes. (26 newer cards
have no generated art yet; the UI shows a styled type-icon placeholder for
them and picks the JPG up automatically once it exists in
`cards/art/generated_backup/`.)

## Tests

```bash
php webGame/tools/test_engine.php 100   # bot plays 100 full games, checks invariants
php webGame/tools/test_scenarios.php    # deterministic rules scenarios (missions, incomes, TW, staging…)
```

## Rule interpretations & adaptations

The paper ruleset leaves some things to the table; the digital version makes
these calls (all in one place so they're easy to revisit):

* **Missions auto-complete.** The instant a craft/asset meets every printed
  condition, the mission is claimed and paid — including its Energy cost
  (batteries are discharged automatically when needed). This is always
  beneficial, keeps the game flowing, and the log explains each claim.
* **Asset income is automatic** during Maintenance (Asset Operations), as the
  rules intend ("no Command Turn required"). An asset without Energy logs a
  hint about attaching a Power card.
* **Battery Packs discharge automatically** whenever a craft is short on
  Energy for a mandatory or requested spend.
* **Hand-limit overflow** from Maintenance recovery is enforced at the next
  Planning "Ready" step instead of mid-maintenance (avoids blocking a phase
  nobody is watching). Acquiring is blocked while at the limit.
* **Deployed assets come online immediately** (Energy = their Power output at
  deploy time) so a satellite deployed in round N earns income in round N's
  Maintenance.
* **Crewed missions** require a Pressurized tank on the craft (per §9).
* **Solar Panels** are lost when *entering* an atmosphere node from space, not
  during ascent from Earth (otherwise they could never be launched).
* **Transfer Window modifiers** apply in the order: event (EV06/EV07), then
  Trajectory Planning, clamped to 0–5.
* **"Second Technology" milestone** is awarded once globally (first player to
  reach 2 techs), matching the other milestone bonuses.
* **Broadcast Rights (EV10)** adds +1 Credit to each income ability that
  triggers that round.
* **Comms Blackout (EV12)**: relay access is paid automatically (1 Credit to
  the lowest-seat rival with a deployed Electronics asset); with no provider
  or no Credit, the mission simply can't be claimed that round.
* **Fuel Depot** serves the owner's craft only.
* **First player is fixed** for the whole game (the 5/6/7/8 starting-Credit
  ladder already compensates seat order).
* **Comm Satellite relay** income triggers automatically when a rival craft
  moves beyond Earth ZOI (once per round per satellite).

## File map

```
webGame/
├── index.html            single-page app shell
├── css/game.css          all styling (dark board-inspired theme)
├── js/
│   ├── main.js           app orchestration: lobby, polling, rendering, actions
│   ├── data.js           card DB + client-side rules mirror (previews/hints)
│   ├── planner.js        rocket builder + flight planner modals
│   ├── board.js          interactive SVG orbital map
│   ├── cards.js          card renderer + per-card teaching hints
│   ├── api.js / ui.js    HTTP client / DOM helpers
├── data/cards.json       generated card database (frontend)
├── assets/               card art, icons, card back (generated/copied)
├── api/
│   ├── index.php         JSON API router (create/join/start/state/action)
│   ├── storage.php       SQLite or JSON-file persistence with locking
│   ├── data/             runtime saves (blocked from the web)
│   └── engine/
│       ├── engine.php    state machine: setup, phases, actions, maintenance
│       ├── flight.php    launches, movement, staging, landings, deploys, docking
│       ├── missions.php  mission predicates + auto-claiming
│       ├── map.php       orbital node graph
│       └── cards_data.php generated card database (backend)
└── tools/
    ├── build_data.py     cards.csv → JSON/PHP + asset copy
    ├── test_engine.php   bot-driven full-game fuzz tests
    └── test_scenarios.php deterministic rules tests
```

## Deploying to your own server (SSH/rsync)

For a LAMP box (e.g. Docker on a Raspberry Pi) or any host with SSH:

```bash
webGame/tools/deploy.sh user@192.168.100.100:/var/www/html/spacerace [ssh_port]
```

It rsyncs the game (excluding runtime saves and tools), then creates
`api/data/` with the right permissions on the server. Authentication uses
your own SSH keys — no credentials are stored in the repo. If your Docker
container maps the webroot to a host folder, target that folder; the docroot
must end up serving `index.html` and allow PHP execution in `api/`.
