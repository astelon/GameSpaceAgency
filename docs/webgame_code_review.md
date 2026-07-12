# webGame — Code Review & Refactor Plan

Static review of `webGame/` (frontend `js/`, PHP engine `api/engine/`, HTTP API
`api/index.php` + `api/storage.php`, data pipeline, tests, CI) performed on the
state of `main` at c676caa. Every bug marked **[confirmed]** was reproduced
with a script driving the real engine through `sar_apply()`.

## Status

The four confirmed engine bugs (§1.1–§1.4), all of §2 (§2.1–§2.6, the server/
API-layer issues), all of §3 (§3.1–§3.4, the frontend issues), the
`engine.php` split and most of the remaining §4 dead-code items, Phase 0,
Phase 2, Phase 3, and Phase 4 are **done** — see **[fixed]** / **[done]**
tags below and the "Progress" note at the top of §7. Everything else in this
document (the rest of §4, §5, §6, and Phase 5 of §7) is still open.

---

## 1. Confirmed engine bugs

### 1.1 Launch Abort System reroll result is discarded — the check is rolled *again* **[confirmed] [fixed]**

`flight.php` — when a reliability roll fails and the player owns C06, the
engine parks a `pending` decision recording the failing step. Accepting the
reroll (`sar_resolve_reroll`) charges 2 Credits, rolls a d10, and on success
resumes the flight with `sar_run_flight($g, $craftId, $plan, $data['step'], false)`.
But the craft is still on the surface at that step, so the resumed loop hits
`sar_is_surface($from)` and calls `sar_launch_checks()` again — which **rolls a
brand-new d10** (and re-charges the Crew Capsule / Flight Computer energy).

Observed in repro:

```
Launch Abort System reroll: 5 vs 6 — SUCCESS.
Reliability check for Raptor-X stack: rolled 7 vs 6 (base 6) — FAILURE.
```

The player paid 2 Credits, won the reroll, and the launch failed anyway. Worse:
the second failure can open *another* pending reroll, chaining 2-Credit charges
indefinitely.

**Fix direction:** make the resume skip the already-passed check — e.g. record
`skipCheckStep` in the pending data and have `sar_launch_checks` return `'ok'`
without rolling (and without re-charging energy) when resuming that exact step.

**Fixed:** `sar_run_flight()` now takes a `$skipCheckStep` parameter;
`sar_resolve_reroll()` passes the pending decision's `step` through it so the
resumed loop treats that surface departure as already cleared instead of
calling `sar_launch_checks()` again. Regression test: `test_scenarios.php`
Scenario 9 asserts exactly one `roll`-type log entry for the original check
plus one for the reroll (reverting the fix reproduces the extra roll).

### 1.2 Declining (or re-failing) the reroll never spends the command turn **[confirmed] [fixed]**

`sar_resolve_reroll` failure/decline paths call `sar_launch_failure()` +
`sar_skip_to_next_actor()` directly, never incrementing `turnsUsed`. A
non-LAS launch failure goes through `sar_finish_flight → sar_end_command_turn`
and *does* spend the turn. So owning C06 and always declining gives free launch
retries — the failed attempt costs nothing but the destroyed engine.

**Fix direction:** route all reroll resolutions through `sar_end_command_turn`
(or increment `turnsUsed` when the pending decision is created).

**Fixed:** the decline and reroll-failure paths in `sar_resolve_reroll()` now
call `sar_end_command_turn($g, $seat)` instead of `sar_skip_to_next_actor($g)`
directly, so `turnsUsed` increments. Regression test: Scenario 9 asserts
`turnsUsed` advances by 1 after a decline.

### 1.3 `stagedEngineFlight` is never cleared — engineless craft maneuver forever **[confirmed] [fixed]**

Staging a Kick Stage (E07) sets `$craft['stagedEngineFlight'] = true` so the
craft "still counts as the engine for the whole flight". Nothing ever resets
it: `sar_finish_flight` does `$craft['stagedEngineFlight'] ?? false`, which
preserves `true`. In any later round the craft passes the engine gate in
`sar_action_activate` and moves freely with no engine; it also keeps
satisfying engine-dependent mission checks (`sar_craft_has_engine_or_staged`).

**Fix direction:** clear the flag in `sar_finish_flight` (and on launch
failure). Mission checks evaluated during the flight still see it set.

**Fixed:** `sar_finish_flight()` and `sar_launch_failure()` both now reset
`stagedEngineFlight` to `false` once the flight/attempt ends (mission checks
mid-flight still observe it set beforehand, as intended). Regression test:
`test_scenarios.php` Scenario 10 stages a craft's only Engine mid-flight,
advances to the next round, and asserts `activate` with a multi-hop plan now
throws "without an Engine".

### 1.4 `version` increments twice for decision actions **[confirmed] [fixed]**

`sar_resolve_reroll` does `$g['version']++` and `sar_apply` increments again
after the action returns. Harmless today (version stays monotonic) but it
breaks the "one action → one version" invariant the polling protocol implies,
and it is exactly the kind of drift that hides real bugs later. Remove the
inner increments.

**Fixed:** removed both inner `$g['version']++` calls from
`sar_resolve_reroll()`; `sar_apply()`'s single increment is now the only one.
The fuzzer (`test_engine.php`) now asserts `version` advances by exactly 1 per
accepted action, and `test_scenarios.php` Scenario 9 checks it across every
reroll retry.

---

## 2. Server / API-layer issues (static)

### 2.1 The SQLite "lock" does not serialize requests **[fixed]**

`SarStorage::lock()` calls `beginTransaction()`, which in SQLite is a
**deferred** transaction: no lock is taken until the first write. Two
concurrent requests for the same room both read the same state, both apply an
action, and the last `save()` wins — a classic lost update (or an unhandled
`SQLITE_BUSY`, since no `busy_timeout` is set). The JSON-file fallback with
`flock()` is actually the *stronger* path. Fix: `BEGIN IMMEDIATE` +
`PRAGMA busy_timeout`, or `flock()` a per-room lockfile in both modes.

**Fixed:** `lock()` now issues `BEGIN IMMEDIATE` directly (bypassing PDO's
deferred `beginTransaction()`) so the write lock is taken up front, and the
constructor sets `PRAGMA busy_timeout=5000` so a second writer blocks and
retries instead of failing immediately on `SQLITE_BUSY`. `unlock()` commits
the same way (`COMMIT` via `exec()`, tracked with an explicit `$inTxn` flag
rather than `PDO::inTransaction()`). Regression test: `test_api.php` fires
two concurrent `action` requests (via `curl_multi`, against a real
multi-worker `php -S`) at the same room across 8 fresh rooms and asserts
neither update is lost — reverting the fix reproduces the lost update on
every run.

### 2.2 Room-code collision silently overwrites a running game **[fixed]**

`room_code()` never checks for an existing room, and `save()` upserts
(`ON CONFLICT ... DO UPDATE`). At 31⁵ ≈ 28.6M codes this is unlikely but
trivially avoidable: generate inside the lock and retry while `load()` is
non-null.

**Fixed:** the `create` op now generates a candidate code, locks it, and
checks `load()` is `null` before committing to it; on a collision it unlocks
and retries (up to 5 attempts, then fails with 503) instead of ever calling
`sar_new_game()`/`save()` against an occupied room code.

### 2.3 `sar_apply` can throw mid-mutation; callers must know to discard state **[fixed]**

`sar_action_launch` mutates the craft (node → `earth`, `launchRound`,
`history`) and charges the EV03 credit *before* the dry-run validation can
throw. `api/index.php` happens to not save on `SarError`, so players never
see it — but the contract "every mutation goes through `sar_apply()`" is
misleading: any caller that keeps the array after a `SarError` holds corrupted
state. **The fuzz harness (`test_engine.php` → `try_apply`) does exactly
that** — it keeps `$g` after a rejection, so long fuzz runs can wander through
states that are unreachable in production, masking real bugs and potentially
raising phantom ones. Fix: make `sar_apply` transactional (`$tmp = $g;`
mutate `$tmp`; assign back on success — cheap with PHP copy-on-write), then
the API layer and the fuzzer both get the right semantics for free.

**Fixed:** `sar_apply()` now mutates a local `$tmp = $g` copy and only
assigns it back to the caller's `$g` reference after the action completes
without throwing (`bootstrap.php`). A thrown `SarError` therefore always
leaves `$g` byte-for-byte untouched, regardless of how far the action got
before failing. Regression test: `test_scenarios.php` Scenario 11 launches a
real craft with a plan whose path steps to an unconnected node (`moon` is not
adjacent to `earth`) — `sar_action_launch` mutates the craft's node/range/
history *before* the dry-run probe rejects the bad route, and the test
asserts `$g` is unchanged afterwards (reverting the fix reproduces a leaked
half-launched craft). `test_engine.php`'s `try_apply` keeps its own
snapshot/restore as a second, independent check of the same guarantee.

### 2.4 Fuel Depot check is looser on the server than the rule **[fixed]**

`flight.php` validates a depot with `sar_craft_cards($depot, 'Payload', 'Station')`
— i.e. *any* deployed Station-tagged payload. P08 (Station Hub) also carries
the `Station` tag, so a crafted request can refuel from a Station Hub. The
client only offers P11. Check the card id (or add a dedicated `Depot` tag in
the CSV so data, not code, decides).

**Fixed:** the depot check in `sar_run_flight()` now requires the specific
`P11` card id on the depot craft instead of the `Station` tag.

### 2.5 Acquire-by-slot races in online games **[fixed]**

`{type:'acquire', slot:i}` buys whatever is in slot *i* at execution time.
Between rendering and the click another player may have bought that slot and
it refilled — the buyer silently gets (and pays for) a different card. Include
the expected `uid` in the action and reject on mismatch.

**Fixed:** the client (`main.js`) now sends the market slot's `uid` alongside
`slot`; `sar_action_acquire()` rejects the action with "That market slot
changed — someone else bought it first" if the slot no longer holds that uid.
The `uid` field is optional server-side for backward compatibility with older
clients/requests that omit it.

### 2.6 Smaller server items — partially done

- ~~`seats_for()` compares the hot-seat host token with `===` while player
  tokens use `hash_equals` — use `hash_equals` for both.~~ **[fixed]** both
  comparisons now go through `hash_equals`.
- ~~500 responses echo `$e->getMessage()` to the client — internal
  paths/details can leak. Log the detail, return a generic message.~~
  **[fixed]** the catch-all `Throwable` handler in `api/index.php` and the
  `SarStorage` construction failure both `error_log()` the real message and
  return a generic one to the client (the storage failure still points
  admins at `?op=health` for a full diagnosis, which leaks nothing sensitive).
- ~~JSON-file mode: `cleanup()` is a no-op (stale `ROOM.json` + `.lock` files
  accumulate forever) and `save()` is not atomic (no tmp-file + `rename`), so
  a crash mid-write can corrupt a room. `load()` never checks
  `json_decode` failure.~~ **[fixed]** `save()` writes to a per-save tmp file
  and `rename()`s it into place; `load()` (both storage modes) throws if
  `json_decode` fails on non-empty input instead of silently returning a
  corrupt/empty state; `cleanup()`'s random 1-in-50 sample now also sweeps
  JSON-mode `*.json` + matching `.lock` files idle for 3+ days, not just the
  SQLite table.
- **Still open:** `filter_state()` leaks minor hidden info: exact `missionT2`/`missionT3` deck
  counts, and maintenance "Returned to hand: …" log lines reveal opponents'
  recovered cards. Decide deliberately what is public.
- `players[].connected` is written once and never read — dead field.
- Basic-supply uids (`$cid . '#b' . $g['version']`) piggyback on the version
  counter; use a dedicated instance counter.

---

## 3. Frontend issues

### 3.1 `el()`'s `html:` attribute is an XSS foothold **[fixed]**

`ui.js` `el()` supports `html:` → `innerHTML`. It is used for card text
(trusted CSV) but also in `showDice()`, whose `label` is derived from log text
that can embed player-controlled strings (names, ≤20 chars — `<svg/onload=…>`
fits). Craft names are card-derived today, and station names
(`"{player}'s Station"`) don't currently flow through `showDice`, so
exploitability is low — but the pattern is one refactor away from stored XSS
across all clients in a room. Remove `html:` support; the only legitimate use
is `<br>` in card text, which can be handled by splitting on `<br>` and
appending text nodes + `el('br')`.

**Fixed:** `el()` no longer has an `html:` attribute at all. Added
`textWithBreaks()`, which splits a string on literal `<br>` and returns text
nodes interleaved with real `el('br')` elements — no HTML parsing, so no
substring of the input can ever become markup. `cards.js`'s card-text
rendering and `ui.js`'s `showDice()` both use it (or plain `el()` children)
instead of `innerHTML`; the Playwright card-sizing suite (which renders every
card's text) stays green.

### 3.2 A stale poll response can roll the UI back **[fixed]**

`applyState()` accepts any response unconditionally. `poll()` runs on a 2.2s
interval with no in-flight guard, so a slow poll that resolves *after* an
action response overwrites newer state with older state (UI flicker/rollback
until the next poll). Guard with `if (r.version <= st.version) return;` and/or
skip polling while a request is in flight; pause polling when
`document.hidden`.

**Fixed:** `applyState()` now ignores any response whose `version` is older
than the state already applied; `poll()` tracks an `st.pollBusy` flag so an
overlapping tick is skipped instead of firing a second concurrent request;
and the `setInterval` tick is a no-op while `document.hidden` (the existing
`visibilitychange` handler already force-polls on becoming visible again).

### 3.3 The JS rules mirror is a hand-maintained fork of the PHP engine **[fixed]**

`data.js` (`simulatePlan`, `checkMission`, `passiveLanding`, `craftReliability`,
…) re-implements ~400 lines of the PHP rules for previews. Nothing checks that
the two stay in sync, and drift already exists (§2.4 depot; `Lander`-tag
semantics differ between `sar_passive_landing` — Payload-only — and
`sar_validate_landing` — any type, which S16 Airbag Shell exploits). This is
the single biggest maintainability risk in the codebase. See refactor plan
phase 4.

**Fixed (parity harness; the `Lander`-tag/S16 drift itself is still open):**
`tools/gen_parity_fixtures.php` drives the real PHP engine (`sar_craft_reliability`,
`sar_passive_landing`, `sar_mission_check` for all 21 missions, and
`sar_run_flight` dry-runs for representative flight plans) over a battery of
craft/plan inputs and writes the PHP-authoritative results as JSON fixtures;
`tools/test_parity.mjs` (plain Node, no test framework — consistent with the
zero-dependency style of the PHP test scripts) replays the same inputs
through `craftReliability`/`passiveLanding`/`checkMission`/`simulatePlan` in
`data.js` and asserts they agree. Wired into `npm run test:parity` (part of
`npm test`) and its own CI job. Writing this harness immediately found two
real drifts, now fixed: `checkMission` was missing the `M21` case entirely
(the standing-contract mission was never previewable client-side), and
`passiveLanding`'s uncrewed-airbag branch returned the generic string
`'airbags'` instead of the actual card's name (inconsistent with every other
branch of the same function). `sar_validate_landing`'s any-type `Lander` tag
vs. `sar_passive_landing`'s Payload-only check (the S16 Airbag Shell
discrepancy noted above) was not added as a fixture and remains open.

### 3.4 Smaller frontend items — mostly fixed

- ~~`renderLog()` rebuilds 120 DOM nodes and forces `scrollTop` to bottom on
  every state change — the user gets yanked down while reading history.
  Render only new entries; only autoscroll when already at the bottom.~~
  **[fixed]** `renderLog()` now tracks the last rendered log `seq` and only
  appends entries newer than that (trimming the oldest DOM nodes past 120
  instead of rebuilding), and only forces `scrollTop` when the reader was
  already within 40px of the bottom.
- **Still open:** Planner/builder modals capture `g` by reference at open
  time; a poll can swap `st.g` underneath, so the modal submits plans against
  stale state (server re-validates, but errors are confusing). Consider
  freezing input or refreshing the modal on state change.
- ~~`enterGame()` clears the whole session on *any* `ApiError` — a transient
  500 logs the player out of the room.~~ **[fixed]** `ApiError` now carries
  the HTTP status; `enterGame()` only clears the session on a genuine 403/404
  ("you're not in this game" / "room not found"), not on a transient 500 or
  network failure.
- ~~Dead code: `cards.js:117` `c.text.replaceAll('<br>', '<br>')` (no-op);
  `planner.js` `stepControls()` stub (called with 3 args, takes none, returns
  `[]`); unused import `hintFor` in `main.js`; unused exports
  `EARTH_ONLY_REENTRY` (data.js) and `SAR_EARTH_ONLY_REENTRY` (flight.php);
  unused `$asAction` parameter in `sar_apply_engineering`;
  `sar_new_craft`'s `[$node === 'assembly' ? null : $node]` history branch is
  immediately overwritten for the assembly case.~~ **[fixed]** all removed
  (the no-op `replaceAll` went away as part of the §3.1 fix, which replaced
  that whole line).
- ~~`api.js` `fetch` has no timeout/`AbortController`; a hung request wedges
  `st.busy` actions until it settles.~~ **[fixed]** `api()` now races the
  fetch against a 15s `AbortController` timeout and surfaces it as an
  `ApiError` like any other failure.

---

## 4. Code smells & structure

- ~~**`engine.php` is a 964-line grab bag** of constants, lobby, setup, phase
  flow, six action handlers, maintenance, and scoring, all free functions over
  an untyped array. There is no written schema for the game state or the craft
  record; per-card fields (`p03Round`, `s11Round`, `relayUsedRound`,
  `ceramicAeroUsed`, `dockedHab`, `stagedEngineFlight`) get bolted on at first
  use, so "does this key exist?" is answered with `?? / !empty()` everywhere.~~
  **[fixed]** `engine.php` is gone, split into `constants.php` (error types,
  card/game constants, logging), `state.php` (derived values, craft helpers,
  and the explicit player/craft schema — see below), `lobby.php`
  (new/add/start), `phases.php` (planning/action/maintenance/scoring/pending
  decisions), `actions.php` (the simple command-turn actions), and
  `bootstrap.php` (requires + the public `sar_apply()` entry point). Pure
  mechanical move, no behavior change — same test suites, all green.
  Every craft now gets its full field set (`relayUsedRound`, `p03Round`,
  `s11Round`, `ceramicAeroUsed`, `dockedHab`, `stagedEngineFlight`, etc.)
  initialized in `sar_new_craft()`, so the `?? / !empty()` guards that only
  existed because a field might be missing were removed in favor of direct
  access; `sar_validate_state()` (`state.php`) is the single place that
  documents and enforces the player/craft schema, called by the test suites
  after every accepted action (not by `sar_apply()` itself — that per-action
  cost isn't worth paying in production).
- **`require_once` inside functions** (`sar_maintenance`,
  `sar_action_decision` in `phases.php`) — a load-order hack to avoid a
  top-level circular require with `flight.php` (which itself requires
  `phases.php`). `bootstrap.php` now requires every module unconditionally at
  the top before `sar_apply()` is ever called, so these two lazy requires are
  redundant in practice and kept only as defensive belt-and-suspenders;
  `sar_apply()`'s own former `require_once` of `flight.php`/`missions.php`
  (truly dead now that `bootstrap.php` requires them) was removed.
- **Magic card ids everywhere.** `'C01'…'C10'`, `'EV01'…'EV13'`, `'S07'`,
  `'P04'`… are hard-coded in PHP, duplicated in JS, and described a third time
  in `cards.js` HINTS. The CSV already has a Tags column; moving behavior
  triggers into data (e.g. tag `IncomeNearEarth`, `Depot`, `BurnsInAtmo`)
  would collapse three copies into one and let new cards work without code.
- Duplicated literals: hand-limit table `{1:2, 2:3, 3:4}` appears twice in
  `main.js` while the server has `SAR_LEVEL_TURNS`; level costs 6/14 are
  re-hardcoded in `expandConfirm`; the Earth/Mars descent chains are spelled
  out in `flight.php`, `data.js`, *and* `planner.js`.
- `test_engine.php` invariants use `assert()` (a no-op under
  `zend.assertions=-1`) immediately followed by manual throws — keep only the
  throws.

## 5. Inefficiencies (minor, listed for completeness)

- Every `state` response ships the full state including up to 400 log entries;
  the client renders only the last 120. Send log entries `> since` or cap the
  filtered copy.
- `sar_edge()` linear-scans the edge list per hop and `sar_card()` re-explodes
  uids constantly — irrelevant at this scale; not worth changing except as a
  by-product of other refactors.
- Polling continues at full rate in hidden tabs (battery/server load).

---

## 6. Test & CI gaps

Current coverage is genuinely good for a hobby project (card-data integrity,
full-game fuzz, scripted scenarios, sub-orbital rules, card-sizing UI), but:

1. **No coverage of the HTTP/API layer** — auth (`seats_for`), state filtering
   (hand hiding), lobby lifecycle, storage locking. Bugs §2.1–§2.6 live in
   exactly the files with zero tests.
2. **No parity tests between the PHP engine and the JS mirror** (§3.3).
3. ~~**The fuzzer's `try_apply` keeps state after `SarError`** (§2.3), which
   undermines the invariant checking that is its whole point.~~ **[fixed]**
   `try_apply()` now snapshots `$g` before each action and restores it on
   `SarError`; it also asserts `version` advances by exactly 1 per accepted
   action.
4. ~~**The confirmed bugs of §1 show the gap:** no scenario ever accepts a
   reroll, checks `turnsUsed` after a failure, or activates a kick-staged
   craft in a later round.~~ **[fixed]** `test_scenarios.php` Scenarios 9–10
   cover exactly these cases (both were verified to fail against the
   pre-fix code).
5. **CI never verifies the generated data files match the CSV** — a hand edit
   of `cards.json`/`cards_data.php` (which CLAUDE.md forbids) would pass.
6. No linters: `php -l`, PHPStan, ESLint. No PHP version matrix even though
   `index.php` advertises PHP 7.4+ support while development targets 8.x.
7. Playwright covers card sizing only — no smoke test that a hot-seat game can
   actually boot and take a turn.

---

## 7. Refactor plan

Ordered so each phase lands green on the existing suites before the next
starts. Phases 1–3 are engine/server work; 4 is frontend; 5 is CI. Effort
markers: (S)mall < ~1h, (M)edium, (L)arge.

### Phase 0 — Safety net first ✅ done
- [x] (S) Fix `try_apply` in the fuzzer to snapshot/restore state around rejected
  actions, and drop the no-op `assert()` calls.
- [x] (M) Add regression scenarios for bugs §1.1–§1.3 to `test_scenarios.php`
  (they will fail — that's the point; they gate Phase 1). Confirmed: both new
  scenarios fail when run against the pre-fix engine code, and pass after.
- [x] (S) Add `version` monotonicity (+1 per action) to the fuzzer invariants.

### Phase 1 — Correctness fixes (small, independent commits) — partially done
- [x] (M) §1.1 reroll resume: record the resumed step in pending data; skip the
  reliability roll and its energy charges on resume.
- [x] (S) §1.2 spend the command turn on reroll decline/failure.
- [x] (S) §1.3 clear `stagedEngineFlight` in `sar_finish_flight` /
  `sar_launch_failure`.
- [x] (S) §1.4 remove the inner `version++`.
- [x] (S) §2.4 depot check by card id (or new `Depot` tag).
- [x] (S) §2.5 optional `uid` echo on acquire; reject mismatches.

All Phase 0 + Phase 1 items above are done, covered by 51 assertions in
`test_scenarios.php` (2 new scenarios), the strengthened `test_engine.php`
fuzzer (40-game run green), `test_suborbital.php`, `test_cards_data.php`, and
the Playwright card-sizing suite (8/8) — all pass with the fixes applied.
Phase 2 and Phase 3 (below) are now also done. Remaining Phase 4–5 items
(frontend robustness/parity, CI) are unstarted.

### Phase 2 — Transactional engine core ✅ done
- [x] (M) Make `sar_apply` copy-on-write: mutate a copy, commit on success. Then
  document (in one place) that a thrown `SarError` leaves `$g` untouched.
  **Fixed:** see §2.3 above; regression test in `test_scenarios.php` Scenario 11.
- [x] (M) Define the state and craft schemas explicitly: initialize *all* craft
  fields in `sar_new_craft`, add `sar_validate_state()` (used by tests and
  optionally after each apply in debug), delete the `?? 0`/`!empty()` guards
  that only exist because fields may be missing.
  **Fixed:** every craft field is initialized in `sar_new_craft()`
  (`state.php`); `sar_validate_state()` checks the full player/craft field
  set (`SAR_PLAYER_FIELDS`/`SAR_CRAFT_FIELDS`) plus non-negative
  credits/vp/range/energy, valid `level`/`owner`/`node`, and no card uid
  duplicated across zones. `test_engine.php`'s fuzzer now calls it after
  every accepted action across all 40 games in the default run; it throws a
  distinct `SarInvariantError` (not `SarError`) so the fuzzer's
  legal-rejection handling can never accidentally swallow a real schema bug.
- [x] (L) Split `engine.php`: `bootstrap.php` (requires), `constants.php`,
  `lobby.php` (new/add/start), `phases.php` (planning/action/maintenance/
  scoring), `actions.php` (simple actions), keeping `flight.php`/`missions.php`.
  Pure mechanical moves, no behavior change, fuzz + scenarios green after.
  **Fixed:** see §4 above; also added `state.php` for the derived-value/craft
  helpers and the schema validator, which the original sketch didn't call out
  a home for. All four require sites (`api/index.php`,
  `tools/test_engine.php`, `tools/test_scenarios.php`,
  `tools/test_suborbital.php`) now require `bootstrap.php` instead of the old
  `engine.php`; verified end-to-end over real HTTP (`php -S` + create/start/
  state/action) in addition to the CLI suites.

### Phase 3 — Storage & API hardening ✅ done
- [x] (S) `BEGIN IMMEDIATE` + `busy_timeout` in SQLite mode (§2.1).
- [x] (S) Retry room-code generation on collision inside the lock (§2.2).
- [x] (S) Atomic JSON writes (tmp + rename), `json_decode` failure handling,
  cleanup of stale `.json`/`.lock` files (§2.6).
- [x] (S) `hash_equals` for the host token; generic 500 messages with
  server-side logging (§2.6).
- [x] (M) An API integration test: run `php -S` against a temp data dir, drive
  create/join/start/state/action over HTTP, assert hand hiding, seat
  authorization, and that two racing actions to one room serialize correctly.
  **Fixed:** all five items above are implemented in `storage.php`/
  `index.php`; `tools/test_api.php` copies `api/` into a temp dir, boots
  `php -S` with `PHP_CLI_SERVER_WORKERS=4` so requests genuinely run in
  parallel, and drives create/join/start/state/action over real HTTP with
  `curl`/`curl_multi`. It asserts hand hiding (a player's own hand is
  visible, opponents' are `null` with just a count), seat authorization
  (403 on acting for a seat you don't control, non-host `start` rejected),
  and — across 8 fresh rooms — that two concurrent `planning_done` actions to
  one room never lose an update; reverting the §2.1 fix reproduces the lost
  update on every run of the repeated race. Wired into `npm run test:backend`
  and the `Tests` CI workflow.

### Phase 4 — Frontend robustness & parity ✅ done
- [x] (S) Remove `el()`'s `html:` path; handle `<br>` by splitting into text
  nodes (§3.1). Keep the Playwright sizing suite green.
- [x] (S) Version guard + hidden-tab pause + in-flight guard in polling (§3.2).
- [x] (M) **Parity fixtures:** a PHP script generates N scenario fixtures
  (craft + plan + expected outcome/mission set) as JSON; a Node test runs
  `simulatePlan`/`checkMission` against the same fixtures. Wire into CI. This
  turns the silent PHP↔JS drift (§3.3) into a failing test forever after.
- [x] (S) Incremental log rendering + scroll preservation; session kept on
  transient errors; dead-code sweep (§3.4).
- [x] (M) One Playwright smoke test: boot a hot-seat game against a PHP dev server
  (or a canned state fixture), take an acquire action, assert the log line.
  **Fixed:** see §3.1–§3.4 above for the first four items.
  `tests/smoke.spec.mjs` copies `webGame/` to a temp dir, boots a real
  `php -S`, and drives an actual browser through the hot-seat lobby → both
  players ready up in Planning Phase (each dropping one card first, since
  Round 1 always starts at the 5-card hand limit: 3 starting + 2 drawn on the
  Planning reveal) → Action Phase → a Basic Shop `acquire`, then asserts the
  `"... from the Basic supply"` log line appears. Runs alongside the card-sizing
  suite in the `frontend` CI job (now with PHP installed); stable across
  repeated local runs on both the desktop and mobile Playwright projects.

### Phase 5 — CI
- (S) Add `php -l` over `api/`, and a PHP version matrix (8.1–8.4; or drop the
  7.4 claim in `index.php`'s health check).
- (S) Add a "generated files are fresh" job: run `build_data.py`, then
  `git diff --exit-code webGame/data/cards.json webGame/api/engine/cards_data.php`.
- (M) Add PHPStan (level ~5 to start) and ESLint (flat config, `no-unused-vars`
  alone pays for itself here); fix findings.
- (S) Run the new API integration + parity suites in the backend job; hook
  everything into `npm test` so local == CI.

### Explicit non-goals
- No framework adoption (the no-build vanilla ES-modules + plain-PHP setup is
  a feature for shared-hosting deployment; the plan keeps it).
- No state-shape rewrite (plain JSON-serializable arrays stay; they make
  saves, polling and the dry-run probe trivially cheap).
