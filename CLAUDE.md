# GameSpaceAgency

Space Agency Race — a board game about running a space agency. The repo holds
the printable card sources (`cards/`), rules (`Space_Agency.md`), and a
playable web edition (`webGame/`: static HTML/JS frontend + PHP rules engine
in `webGame/api/engine/`).

## Card data pipeline

`cards/cards.csv` is the master card database. `python3 webGame/tools/build_data.py`
regenerates `webGame/data/cards.json` (frontend) and
`webGame/api/engine/cards_data.php` (engine) — never edit those two files by
hand, and always regenerate both together.

## Tests

- Backend (PHP 8):
  - `php webGame/tools/test_cards_data.php` — card database schema/consistency/assets
  - `php webGame/tools/test_engine.php 30` — full-game fuzz/smoke via `sar_apply()`
  - `php webGame/tools/test_scenarios.php` — scripted rule scenarios
  - `php webGame/tools/test_suborbital.php` — sub-orbital decay rules
  - `php webGame/tools/test_api.php` — HTTP API integration: boots `php -S`
    against an isolated temp copy of `api/`, drives create/join/start/state/
    action over real HTTP, and asserts auth, hand hiding, and that concurrent
    actions to one room serialize correctly (storage locking)
- PHP<->JS rules parity: `npm run test:parity` — `webGame/tools/gen_parity_fixtures.php`
  runs the real PHP engine (`craftReliability`/`passiveLanding`/`checkMission`/
  flight-plan dry-runs) over a battery of inputs and writes the results as
  fixtures; `webGame/tools/test_parity.mjs` (plain Node, no test framework)
  replays the same inputs through the JS mirror in `webGame/js/data.js` and
  asserts they agree. Regenerate fixtures whenever `data.js` or the PHP rules
  change — this is the test that catches PHP<->JS drift.
- Frontend (Playwright): `npm ci && npx playwright test` — card-sizing.spec.mjs
  renders every card into the hand, market and mission-offer zones (desktop +
  mobile layouts) and asserts they all have identical dimensions (harness:
  `webGame/tests/harness.html`); smoke.spec.mjs boots `php -S` + a real
  browser and plays a hot-seat game through the lobby, Planning Phase, and an
  Action-Phase acquire, asserting the resulting log line — the one check that
  exercises frontend JS + HTTP API + PHP engine together end-to-end.
- `npm test` runs all of the above (backend, parity, then frontend).

Cards must always render at one uniform size in the hand, the market, and the
mission offer (`--card-w` + fixed aspect-ratio in `webGame/css/game.css`);
keep the Playwright suite passing when touching card CSS or `renderCard()`.

## Monitor CI results on GitHub

CI runs the `Tests` workflow (`.github/workflows/tests.yml`) on every push.
**After pushing, keep monitoring the GitHub Actions result of the `Tests`
workflow for your branch until it is green** — check the run, and if any job
fails, diagnose and push a fix. Do not consider a change finished while its
workflow run is red.
