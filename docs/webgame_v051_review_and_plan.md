# webGame — Reliability Review ("server returned an invalid response") & v0.5.1 Update Plan

Review of `webGame/` at `9a8d65f` (post board-game v0.5.1, post code-review
Phases 0–3). All five backend suites (`test_cards_data`, `test_engine 30`,
`test_scenarios`, `test_suborbital`, `test_api`) pass at this commit.

This document has two independent parts:

* **Part 1** diagnoses the "Server returned an invalid response" reports and
  lists prioritized fixes.
* **Part 2** is the implementation plan for bringing the web edition up to
  ruleset **v0.5.1** (the engine currently plays v0.4 rules with the v0.5.1
  card *data* already loaded).

---

## Part 1 — "Server returned an invalid response"

### 1.1 What the message actually means

The string comes from exactly one place, `js/api.js:32`: it is thrown whenever
the `POST api/index.php` response body fails `res.json()`. The API itself
*never* deliberately emits non-JSON — every code path in `api/index.php` ends
in `out()`/`fail()`, which emit JSON with `Content-Type: application/json`.
So every sighting of this message is one of two things:

1. **An empty body** — PHP died mid-request (fatal, OOM, killed by the host)
   or `json_encode()` silently returned `false`, or
2. **An HTML error page** — the hosting stack (LiteSpeed/Apache on the
   Hostinger-class shared host the README targets) answered *instead of* PHP:
   503/508 resource-limit pages, mod_security 403s, gateway timeouts, or an
   `.htaccess` misfire.

Given the deployment target and the fact that commit `b9f3122` already had to
add poll backoff for "server overload / outages", **hosting-level HTML error
pages under load are the most likely production cause** — but the code has
three real defects in this area too, listed first because we can actually fix
them.

### 1.2 Defects in our code

#### 1.2.1 `out()` never checks `json_encode()` failure → empty 200 body

`api/index.php:15` does `echo json_encode($data, JSON_UNESCAPED_UNICODE)`.
If the state contains a byte sequence that is not valid UTF-8,
`json_encode()` returns `false`, `echo false` prints nothing, and the client
receives an **empty body with HTTP 200** — the exact reported error, on every
`state` poll, for every player in the room, forever.

Invalid UTF-8 *can* get in: the JSON request path rejects it
(`json_decode` fails → `$req` falls back to `$_POST`), but that very fallback
accepts **form-encoded** POSTs whose `name` field can carry raw invalid
bytes. `clip_name()` does not stop them: with mbstring, `mb_substr()` passes
invalid bytes through unvalidated; without mbstring, the `/us` regex *fails*
on invalid UTF-8 and the code falls back to a raw `substr()`. The bad name
then lands in the player record and in log lines, poisoning every future
response of that room.

`SarStorage::save()` (`api/storage.php:98`) has the same unchecked
`json_encode()`: on failure it stores `''`/false, and every later `load()`
throws "Corrupted game state" — a *bricked room* (surfaces as a JSON 500, but
the room is equally dead).

**Fix (small):**
- Validate names at the door: reject `name`/`names` values that fail
  `preg_match('//u', $s)` (or `mb_check_encoding`) with a 400.
- Encode defensively: add `JSON_INVALID_UTF8_SUBSTITUTE` to the flags in
  `out()` and `save()`, and treat `json_encode() === false` as a logged 500
  with a proper JSON error body instead of `echo false`.

#### 1.2.2 JSON-file locks can block for unbounded wall time → host kills the request

In JSON-file storage mode, `lock()` does a blocking `flock(LOCK_EX)`
(`api/storage.php:56`). On Linux `max_execution_time` counts **CPU time, not
wall time**, so a request stuck waiting on the lock is *not* stopped by PHP —
it sits there until the web server or gateway kills it (LiteSpeed
`request_terminate_timeout`, proxy 504), which produces an **HTML error page**
→ this message. One wedged request holding a room's lock turns every
subsequent action in that room into this failure, and the piled-up workers
push the whole site toward the shared-host process limit (§1.3).

The SQLite path is already bounded (`busy_timeout=5000` → clean JSON 500), so
this only bites hosts where `pdo_sqlite` is missing or `SAR_FORCE_JSON` is
set — but those are exactly the low-end shared hosts where it hurts most.

**Fix (small):** acquire with `LOCK_EX | LOCK_NB` in a retry loop with a ~5 s
deadline; on timeout return a JSON 503 ("room is busy, try again") like the
SQLite path effectively does.

#### 1.2.3 The frontend turns a transient failure into a permanent one

`enterGame()` (`js/main.js:180-183`) clears the whole saved session on **any**
`ApiError` — and "Server returned an invalid response" *is* an `ApiError`. So
one transient 508 while the page is (re)loading:

- **online mode:** deletes the player's room *and seat token* — they can only
  re-join as a brand-new player;
- **hot-seat mode:** deletes the **host token**, and since hot-seat rooms have
  no join flow, the running game is *unrecoverable*.

This single line is probably why users perceive the error as fatal rather
than as a blip.

**Fix (small):** only clear the session on *definitive* rejections ("Room not
found" 404 / auth 403). On transient errors (non-JSON body, HTTP 5xx, network
failure) keep the session, show a "server is busy — retrying" note, and retry
with the same backoff `pollTick()` already uses.

Related quality-of-life gaps in `js/api.js` (all small):

- The message hides the HTTP status. Report it — `Server error (HTTP 508) —
  the host looks overloaded` tells the user (and us) far more than "invalid
  response". Attach `status` to `ApiError` so callers can distinguish.
- No `AbortController` timeout: a hung request wedges `st.busy` until the
  browser gives up (already noted as code-review §3.4; do it here).

### 1.3 Hosting-level causes (can't be fixed in code, but can be diagnosed and mitigated)

- **Shared-host process limits.** Every poll is one PHP process for its full
  duration. At 2.2 s polling × players × concurrent rooms, a Hostinger-class
  "entry processes" cap (typically 15–30) is reachable; past it, LiteSpeed
  serves **"508 Resource Limit Is Reached"** HTML — this exact symptom, in
  bursts, worst at busy times. The `b9f3122` backoff only slows *failing*
  pollers; healthy tabs still poll at full rate (even hidden ones — review
  §3.2/Phase 4 is still open).
- **`api/.htaccess` compatibility.** `Require all denied` is Apache-2.4 authz
  syntax. On a host whose `AllowOverride` doesn't permit authz directives,
  the mere presence of that `.htaccess` makes **every request under `api/` a
  500 HTML page** — i.e. the game never works at all on that host. (Note the
  file protects `api/data` saves, so it must not simply be deleted.)
- **WAF / mod_security** rules that dislike JSON POST bodies → HTML 403.
- **PHP-FPM OOM / kill** on oversized states (the state ships up to 400 log
  entries per poll — tens of KB each way, ×4 players every 2.2 s).

**Mitigations that reduce exposure (medium):**

- Cut poll payloads: send only log entries `> since` (the client already
  tracks `lastLogSeq`), and let `state` responses omit the static parts.
  (Also code-review §5.)
- Pause polling when `document.hidden`; stretch the interval when several
  consecutive polls return `unchanged`.
- These directly reduce PHP process-seconds per game, which is the currency
  the 508 limit is charged in.

### 1.4 Five-minute diagnostic for the next report

The causes above have distinguishable signatures. To identify which one a
user actually hit:

1. **Browser devtools → Network** on the failing action: look at the status
   and raw body of the `api/index.php` POST.
   - `200` + empty body → §1.2.1 (encode failure).
   - `508`/`503` + HTML → host resource limit (§1.3).
   - `500` + HTML mentioning `.htaccess`/"misconfiguration" → authz directive
     problem (§1.3).
   - `504`/empty after ~30 s → killed request, likely lock wait (§1.2.2).
2. Open `api/index.php?op=health` in a browser — it self-checks PHP version,
   storage, writability, and reports which storage mode is active.
3. Check the hosting panel's error log for `508`, `mod_security`, or PHP
   fatal entries at the reported time.

### 1.5 Prioritized fix list

| Prio | Fix | Where | Size |
| --- | --- | --- | --- |
| P0 | Keep session on transient errors; retry with backoff in `enterGame` | `js/main.js` | S |
| P0 | `JSON_INVALID_UTF8_SUBSTITUTE` + `json_encode` failure check | `api/index.php`, `api/storage.php` | S |
| P0 | Reject non-UTF-8 names | `api/index.php` (`clip_name`) | S |
| P0 | Surface HTTP status in `ApiError`; friendlier overload message | `js/api.js` | S |
| P1 | `flock` timeout → JSON 503 | `api/storage.php` | S |
| P1 | Fetch timeout via `AbortController` | `js/api.js` | S |
| P1 | Delta log in `state` responses; hidden-tab pause; adaptive poll interval | `api/index.php`, `js/main.js` | M |
| P2 | README ops note: 508s, entry-process limits, the `?op=health` + devtools diagnostic | `webGame/README.md` | S |

A regression test for the P0 server items fits naturally into
`tools/test_api.php`: POST a form-encoded `create` with an invalid-UTF-8 name
and assert the response is valid JSON (either a clean 400 or a substituted
name — never an empty body), then poll `state` and assert it still parses.

---

## Part 2 — Update plan: web edition → ruleset v0.5.1

### 2.1 Where the webGame stands today

- **Card data is already v0.5.1** — `cards.json`/`cards_data.php` were
  regenerated in `f4a20ec`: EV14–EV16 (Starter Events) exist, and Deadweight
  is encoded as `range: -1` on **P03 Science Module, P11 Fuel Depot, P14
  Heavy Payload**. A guard in `lobby.php:113` keeps `Starter`-tagged events
  out of the round event deck, so behavior is unchanged — the *mechanics* are
  not implemented yet.
- **Some v0.5 items are already in the engine:** the round-1 Transfer-Window
  skip (`phases.php:53`), the 7-card market and flush (`SAR_MARKET_SIZE`),
  mission-display ordering (refill right, sweep oldest), Stranded-Crew
  persistence across rounds.
- **Everything else in the engine is still v0.4**, most visibly the stack
  limits (`actions.php:214-216`: at most 1 Engine, 3 Tanks, 1 Payload) and
  the single-engine thrust/reliability model (`state.php:62-110`).
- `webGame/README.md` still advertises "ruleset v0.2 / card list v0.3".

### 2.2 Rule deltas to implement (v0.4 engine → v0.5.1)

Each item lists the engine change, the JS rules-mirror change (`js/data.js`
must stay in lockstep — code-review §3.3), and UI work.

#### A. Starter Events (EV14–EV16)

Round 1's event comes from the 3-card Starter pool instead of the event deck.

- `lobby.php` `sar_start_game()`: pick one of EV14/EV15/EV16 at random and
  stash it (e.g. `$g['starterEvent']`); log the reveal at setup. (In the web
  flow "revealed during setup" and "revealed at round 1 planning" coincide,
  since starting the game immediately begins round 1.)
- `phases.php` `sar_begin_planning()`: on round 1, use the starter card as
  `$g['event']` instead of drawing; from round 2 draw as today. Discard it in
  maintenance as usual (the two unused starters simply never enter play).
- Effects:
  - **EV15 Founding Grant**: +3 Credits to everyone on reveal — same shape as
    the existing EV02 branch.
  - **EV16 Crash Program**: +1 command turn this round →
    `sar_command_turns()` returns `+1` while `sar_event_id($g) === 'EV16'`.
    Frontend: `main.js:370` and `:508` hardcode `{1:2,2:3,3:4}` — replace
    both with a server-derived `commandTurns` per player in `filter_state()`
    (also kills a code-review §4 duplication).
  - **EV14 Recovery Trials**: in maintenance step 1 (Earth recovery), while
    EV14 is the active event return **all** parts to hand, not just
    `Reusable` ones (expended single-use landing devices were already
    discarded at use, so no special case needed). Keep the C01
    Reusable-Refurb credit restricted to genuinely `Reusable`-tagged parts.
- JS mirror: `handLimit`-style helper for turns; no sim changes otherwise.

#### B. Flight Data (1 Credit on a failed launch)

- `flight.php` `sar_launch_failure()`: `+1` Credit to the owner, logged.
  **Ruling adopted:** the credit pays when the launch *ultimately* fails —
  i.e. once per failed attempt, in `sar_launch_failure()`. A first-roll
  failure that a Launch-Abort-System reroll then saves pays nothing (the
  launch did not fail); a failed reroll pays once, not twice.
- JS: planner already previews odds; add a hint line ("a failure still pays
  1 Credit of flight data").

#### C. Engine Clusters (0–2 Engines, Thrust adds, Reliability = lowest −1)

- `actions.php:214`: limit becomes `> 2`.
- `state.php` `sar_craft_thrust()`: sum over **all** engines; the E05
  Hybrid-Cycle +1 (cryo tank aboard) applies per E05 engine.
- `state.php` `sar_craft_reliability()`: compute each engine's modified value
  (base + per-engine mods — Reusable Refurb only if *that* engine is
  Reusable — + craft-wide mods: Cryo Handling, Precision Guidance, Flight
  Computer, events), take the **minimum**, and subtract **1 if two engines**
  are mounted. Return the mods list so the dice log stays explanatory.
- `flight.php`:
  - E03 Hydrogen-Core cryo-tank requirement (`:229`): check **any** mounted
    E03, not just the first engine.
  - `sar_launch_failure()`: discard **every** non-Reusable engine (rules:
    "any non-Reusable Engine is discarded").
  - Kick-Stage staging: `stagedEngineFlight` should only matter when *no*
    engine remains; with a second engine still mounted the craft is simply
    still powered (current unconditional flag-set stays correct, since
    `sar_craft_engine()` finds the surviving engine first).
- JS mirror: `craftThrust`, `craftReliability` (same algorithm); builder
  (`planner.js:45`) slot limit `Engine: 2`; builder help text.

#### D. Fuel Tanks uncapped

- `actions.php:215`: drop the 3-tank cap entirely (Thrust is the real gate).
  Keep allowing 0 tanks at assembly time — the builder already warns "No
  fuel tank: Range 0", and a 1+-tank minimum would forbid harmless
  intermediate configurations.
- JS: builder slot limit `Tank: Infinity`; help text.

#### E. Rideshare Payloads (0–2) + single-card payload-Mass requirements

- `actions.php:216`: limit becomes `> 2`.
- `state.php` `sar_craft_mass()` already sums every payload — no change.
- `missions.php` `sar_mission_check()` currently reads only the **first**
  payload (`sar_payload_info()`). Rework to evaluate **each payload card**
  and pass if *any single card* satisfies the requirement (v0.5.1 explicitly
  rules that two Mass-1 payloads do **not** add up to "payload Mass 2+"):
  - Mass thresholds (M04, M05, M07, M10, M20): max single-card mass.
  - Tag requirements (Scientific/Electronics/Reusable/Satellite: M05, M08,
    M11, M14, M16, M20): any payload with the tag; where a mass threshold
    accompanies the tag (M05, M20) the **same card** must satisfy both.
  - `Crewed` (M06, M13, M17 + Stranded Crew in `flight.php`): any Crewed
    payload (+ Pressurized tank as today).
  - M01 "uncrewed payload": satisfied by any non-Crewed payload aboard.
- This also legalizes the Lander + P07 Cargo-Return-Capsule build for M12
  (v0.5's stated motivation) with no further code: M12 checks P07 by id.
- JS mirror: `checkMission` gets the same per-card treatment; builder slot
  limit `Payload: 2`; craft naming (first payload) can stay.

#### F. Deadweight (Range −1 printed on Mass-3+ non-tank cards)

Data already carries it (`range: -1` on P03/P11/P14).

- Launch range (`flight.php:52`): today `range = Σ tank range`. Add the
  (negative) printed `range` of every mounted **non-Tank** card, clamped at
  `max(0, …)` — i.e. `range = max(0, Σ tank range + Σ non-tank range)`.
- Regain on leaving: when a card with negative printed range **permanently
  leaves a craft that is in flight**, the craft regains that much Range.
  Central helper (e.g. `sar_card_leaves_craft()`) called from:
  - `sar_deploy()` — the practical case (P11 Fuel Depot deployed as an
    asset; deploy-supports too, for future-proofing),
  - `sar_stage_card()` / discard paths — none of the three current cards are
    Stageable or burst-discardable, but the helper makes the rule
    data-driven so a future Deadweight card just works.
- Note: `sar_validate_state()` already rejects negative craft range, which
  conveniently guards the clamp.
- JS mirror: `tankRange()` (`data.js:144`) is used by the builder, planner
  and craft list — apply the same formula; builder shows "Deadweight −N" in
  its stats line when nonzero.
- Card rendering: `cards.js` prints `range` when non-null, so P03/P11/P14
  will now show a `-1` Range stat — verify it reads as a penalty (tooltip
  text already explains it) and that the Playwright sizing suite stays green.

#### G. Jury-Rigging (one sideways card per rocket)

The largest item — new action surface, schema field, and UI.

- **Schema:** add `sideways: null|uid` to the craft record
  (`sar_new_craft()`, `SAR_CRAFT_FIELDS`, `sar_validate_state()`; include the
  uid in the no-duplication zone scan).
- **Engineering** (`sar_apply_engineering()`): accept `sideways` in the
  action (attach from hand / detach back to hand, max 1 per rocket, any card
  type). A sideways non-Engine/non-Tank card occupies one of the two payload
  slots for the composition count.
- **Effects** (all ignore the card's printed text/tags/Mass):
  - sideways **Engine** → +1 Thrust in `sar_craft_thrust()` (launch and
    relaunch checks); it is *not* an engine for any other rule (does not
    satisfy "has an Engine", cannot be the cluster's second engine for the
    reliability −1).
  - sideways **Tank** → +1 Range added once at launch (in the launch-range
    computation); not a tank for staging/cryo/pressurized checks.
  - sideways **anything else** → a payload card of Mass 1, no tags, counts
    as Uncrewed: contributes 1 to `sar_craft_mass()`, satisfies "payload"
    presence and "payload Mass 1+" requirements (M01, M10), never satisfies
    tagged or Mass-2+ requirements.
- **Lifecycle:** never stageable, recoverable, or targetable — on Earth
  recovery (maintenance) and on craft destruction it goes to
  `componentDiscard`, *including* under EV14 Recovery Trials and regardless
  of a `Reusable` print.
- **UI:** builder gets a "jury-rig" slot (render the card rotated 90° with a
  strap icon); hand-card menu gains "Strap on sideways"; planner sim
  (`simulatePlan`) mirrors the three effects; `hintFor` text.
- **Filter:** `filter_state()` needs no change (craft cards are public), but
  confirm the sideways uid ships inside the craft record for rendering.

#### H. Second-Technology milestone is per-player

v0.5 text: *each player* gains +1 VP the first time they develop their second
Technology (the +2 for the first **fourth** tech stays global-first).

- `actions.php:103-107`: drop the `milestones['secondTech']` global gate —
  `count($tableau) === 2` fires exactly once per player naturally. Keep the
  `milestones` key for save compatibility or remove it and bump nothing else
  (states are short-lived; rooms idle out in 3 days).

#### I. Optional alignment (low priority)

- Setup easy-mission guarantee: the engine swaps the display's slot 0 with
  the first easy mission found in the deck and buries the displaced card at
  the bottom; v0.5's procedure draws replacements and shuffles set-asides
  back. Functionally close; align only if the distribution difference ever
  matters (it doesn't for play balance).
- `README.md`: update the version line to "ruleset v0.5.1 / card list v0.5
  (91 unique / 192 copies)", mention Starter Events and the new build rules
  in the feature list.

### 2.3 Suggested phasing

Ordered so every phase lands green on the existing suites, engine-first (the
server is authoritative; the JS mirror and UI follow within the same phase to
avoid shipping drift):

| Phase | Contents | Risk | Size |
| --- | --- | --- | --- |
| **V1 — engine-only rules** | B Flight Data · H per-player 2nd tech · A Starter Events (incl. `commandTurns` in `filter_state` + main.js pips) | low | M |
| **V2 — stack limits** | C clusters · D tanks · E rideshare + single-card mission checks (engine + `data.js` mirror + builder limits/text) | medium | L |
| **V3 — Deadweight** | F launch formula + regain helper + builder/planner display | low | M |
| **V4 — Jury-Rigging** | G schema + engineering + effects + builder/planner UI | highest (new action surface) | L |
| **V5 — docs & polish** | I README/version bumps, hint texts, rulebook link check | low | S |

Part 1's P0 reliability fixes are independent of all of this and should ship
first (or in parallel) — they are what users are actually hurting from.

### 2.4 Test plan

New `test_scenarios.php` scenarios (the existing harness style covers all of
these):

1. **Starter Events:** force each of EV14/EV15/EV16 as the starter; assert
   round 1 uses it and round 2 draws from the deck; EV15 pays +3 each; EV16
   grants the extra turn (and only in round 1); EV14 returns non-Reusable
   parts on Earth recovery while an expended parachute stays discarded.
2. **Flight Data:** forced launch failure pays exactly +1 Credit; a
   successful LAS reroll pays nothing; a failed reroll pays once.
3. **Clusters:** two engines' Thrust adds; reliability = min(modified) − 1
   (with a Reusable engine + C01 asymmetry case); launch failure discards
   both non-Reusable engines; E03 in slot 2 still demands a cryo tank.
4. **Rideshare:** two Mass-1 payloads do **not** satisfy "payload Mass 2+"
   (M07); a Mass-2 + Mass-1 pair does; M05 requires Scientific and Mass 2 on
   the *same* card; deploying one payload leaves the other flying.
5. **Deadweight:** P14 aboard → launch range = tanks − 1; deploying a P11
   mid-flight regains +1; clamp at 0.
6. **Jury-Rigging:** each of the three sideways types produces its effect and
   nothing else (sideways engine doesn't count as "has an Engine"; sideways
   card never returns to hand on recovery, even under EV14).
7. **Tech milestone:** two different players each get +1 VP at their second
   tech.

Plus:

- `test_engine.php` fuzzer: teach the action generator to occasionally build
  clusters, rideshares, and jury-rigged rockets so the invariants sweep the
  new composition space; add "craft range never negative after launch"
  (already implied by `sar_validate_state`).
- `test_api.php`: the §1.5 invalid-UTF-8/JSON-integrity regression.
- Playwright: EV14–EV16 and the `-1` Range stat render at uniform card size
  (the harness renders every card automatically — just verify it stays
  green); if V4 adds the sideways builder slot, extend the sizing assertions
  to the rotated card.

### 2.5 Design rulings assumed (flag to the designer if wrong)

1. Flight Data pays on *ultimate* launch failure only (not on a first roll
   that a paid reroll rescues) — §2.2 B.
2. A jury-rigged mass simulator satisfies plain "payload" / "payload Mass 1+"
   mission requirements (it is "a plain payload: Mass 1 … counts as
   Uncrewed") — §2.2 G.
3. Deadweight can never push a craft's launch Range below 0.
4. Cluster reliability: all modifiers are applied per engine, then min, then
   −1; craft-wide modifiers (events, C02/C03, Flight Computer) affect both
   engines equally so ordering only matters for C01 Reusable Refurb, which is
   per-engine.
5. EV14 Recovery Trials does not extend the C01 refurb *credit* to
   non-Reusable parts (only the return-to-hand).
