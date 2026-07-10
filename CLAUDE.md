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
- Frontend (Playwright): `npm ci && npx playwright test` — renders every card
  into the hand, market and mission-offer zones (desktop + mobile layouts) and
  asserts they all have identical dimensions. Harness: `webGame/tests/harness.html`.
- `npm test` runs both suites.

Cards must always render at one uniform size in the hand, the market, and the
mission offer (`--card-w` + fixed aspect-ratio in `webGame/css/game.css`);
keep the Playwright suite passing when touching card CSS or `renderCard()`.

## Monitor CI results on GitHub

CI runs the `Tests` workflow (`.github/workflows/tests.yml`) on every push.
**After pushing, keep monitoring the GitHub Actions result of the `Tests`
workflow for your branch until it is green** — check the run, and if any job
fails, diagnose and push a fix. Do not consider a change finished while its
workflow run is red.
