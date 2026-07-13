---
name: verify
description: Boot and drive the Space Agency Race web game end-to-end to verify webGame/ changes at the real surface (browser + PHP API).
---

# Verify webGame changes end-to-end

## Boot

Serve an isolated copy so `api/data/` never touches the repo:

```bash
SCRATCH=$(mktemp -d)
cp -r webGame/. "$SCRATCH/app/"
mkdir -p "$SCRATCH/app/api/data"
PHP_CLI_SERVER_WORKERS=4 php -d display_errors=0 -S 127.0.0.1:8123 -t "$SCRATCH/app" &
curl -s http://127.0.0.1:8123/api/index.php?op=health   # sanity: "ok":true
```

## Drive (Playwright)

Import through the repo's node_modules (ESM resolves from the script path,
not cwd): `createRequire('/path/to/repo/package.json')('@playwright/test')`.
Use `devices['Pixel 7']` for the mobile layout (`max-width: 860px` breakpoint,
`pointer: coarse`).

Fastest path into a running game (hot-seat, one page):
1. `goto /index.html`, clear localStorage, reload.
2. Tab "Hot-seat (1 device)" → fill `Player 1`/`Player 2` → "Start hot-seat setup".
3. Click `Start game (2 players)`.
4. Planning: click the `Ready` button once per player.
5. Action phase: bar shows `<name> — command turn`. **Which seat acts first is
   random** — don't assert a specific player name.

## Gotchas

- State polling is every 2.2s; give UI assertions generous waits.
- Toasts live 4s in `#toast-root`; the game log is `#log-body`
  ("X passes for the rest of the round.", etc.).
- To simulate a flaky host, `page.route('**/api/index.php')`, `route.fetch()`
  (the server really applies the action), then `route.fulfill` a truncated
  body. The client must retry with the same `aid` and the server must replay
  idempotently.
- Hand limit is 5: acquiring can fail with "Hand is full", and round-2 `Ready`
  is disabled until cards are discarded (click hand cards to select).
- "Plan Launch" in the builder needs an Engine mounted; an engine alone is
  enough (no tank required) to open the flight planner.
