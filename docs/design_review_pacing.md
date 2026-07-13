# Space Agency Race — Pacing, Variety & Jury-Rigging Review (v0.4 → v0.5)

*Review of `Space_Agency.md` v0.4 and `cards/cards.csv` (88 unique / 189 cards). Scope: the physical board game only — the webGame and `cards.csv` sync are deliberately deferred (see §5).*

Focus areas, per request:

1. Early-round pacing — players should feel like they are tinkering with and testing rockets, with real progress every round.
2. Turn variety and multiple, partly mutually-exclusive paths to victory.
3. A basic rule for cards played **sideways** with a weaker alternate effect.
4. Inconsistency sweep.
5. Keep the fun, KSP-like experience.

---

## 1. Early-Round Pacing

### What already works (keep)

v0.4 did a lot of good work here: the **Suborbital Test Flight** standing contract guarantees a productive round 1; **Basic** cards mean a payload/engine/tank is always buyable; the 7-card market plus the 2-Credit **Flush** keeps options flowing; **Engineering + Launch in one Command Turn** means a full build-and-fly loop fits in a single turn; the personal **first-LEO Credit** and the **Exploration Race ladder** pay out for simply going somewhere new. These are exactly the right levers.

### Problem 1.1 — Round 1 eats your starting kit ⚠ (fixed)

The starting kit (Sterling Booster, Standard Tank, Heat Shield) contains **no Reusable card**. Per the Recovery rules, non-Reusable parts are discarded even after a safe Earth return. So the intended round-1 beat — fly the Suborbital Test Flight — pays 2 Credits + 1 VP but **destroys ~6 Credits of hardware**, leaving the player with an empty hangar and 7–10 Credits. Round 2 begins by re-buying the rocket you just had. That is the opposite of "real progress every round," and it punishes the player who did the thematic thing.

**Fix (adopted, revised after feedback): Starter Events.** The first proposal (Shakedown recovery — the test flight returns all parts) was rejected as too much of a mission-specific exception. Instead, round 1's Event is drawn from a dedicated pool of three benign **Starter Events** and revealed **at setup**, so the opening is planned with full information and can never be wrecked by a Solar Storm draw:

* **Recovery Trials** — craft that land safely on Earth this round return all unstaged parts (the hardware start; the original Shakedown idea survives here as one of three openings).
* **Founding Grant** — each player gains 3 Credits (the funding start).
* **Crash Program** — each player has 1 extra Command Turn this round (the tempo start).

Each gives round 1 a different texture, so games diverge from turn one. Alternatives considered and set aside: *Salvage* (recover one non-Reusable part on every safe Earth landing — game-wide, but permanently devalues the Reusable tag) and simply raising M21's payout (fixes the math, not the mood).

### Problem 1.2 — A failed round-1 launch is a pure feel-bad (fixed)

The Sterling Booster is Reliability 9, so 1 game in 10 a player's very first launch fails, discards their only engine, and their opening round produces *nothing*. KSP failures are fun because the explosion itself yields science; here it yielded only a lost turn.

**Fix (adopted): Flight Data.** Whenever one of your launches fails its Reliability check, gain **1 Credit** (telemetry). Small enough to never be worth failing on purpose (an intentional failure still wastes a Command Turn and risks the engine), but it converts every fireball into forward motion. It also quietly re-prices risk engines: Raptor-X's 40% failure now returns 1 CR, nudging the "experimental heavy lifter" gamble toward playable.

### Problem 1.3 — Round-1 Transfer Window ambiguity (fixed)

The Planning Phase advances the TW marker every round, including round 1 — but the printed cycle `3-2-1-0-1-2-3-4` has exactly 8 values for 8 rounds, which only lines up if round 1 *plays* the first value. As written, round 1 would immediately advance 3 → 2 and the 4 would never be played. **Fix (adopted):** skip the advance on round 1; rounds 1–8 play the printed values in order. This also makes the whole game's Mars windows plannable from turn one — very KSP.

### Observations (no change, watch in playtest)

* **Hand-limit collision in round 1:** 3 starting components + 2 drawn = 5 = the hand limit. Any round-1 Acquire forces a discard unless components were attached first. This reads as intentional "build, don't hoard" pressure — keep, but confirm it doesn't confuse new players.
* **Two Command Turns at Level 1** plus the combined Engineering+Launch action is a good floor; with a Starter Event boosting the opening, the typical round 1 is *fly the test flight + buy a part* — fast and forward-moving (and under Crash Program, three actions). No further change recommended before playtesting.

---

## 2. Turn Variety & Exclusive Paths

### Audit of what's already exclusive

The game has more competitive blocking than it may appear, and all of it should be kept:

| Mechanism | Exclusivity |
| --- | --- |
| Public missions | First to complete takes it; it leaves the display. The Mission Sweep keeps the race honest. |
| Exploration Race ladder | Diminishing rewards by arrival order; top rungs are once-per-game. |
| Tech milestones | First-to-4th-tech (+2 VP) and first-to-Level-3 (+2 VP) are single-claim races. |
| Card Market | Buying a card denies it; Flush is soft interaction. |
| Tier unlocks | The first Level-2/3 agency chooses *when* Tier 2/3 missions flood the display — a real tempo weapon. |
| EV13 Stranded Crew | One 5 VP bounty, first claimant only. |

Distinct viable engines (paths) after v0.4: contract runner (Commercial CR ↔ Prestige VP), asset network (distance-scaled satellite income), explorer (ladder + long-range Prestige), science ops (conditional activations under storms / deep space), station/infrastructure, tech/industry (Contracting Office, Reusable Refurb, milestones). Six paths for 2–4 players is enough breadth; the weakness is not the *number* of paths but that the **asset-network path is non-exclusive** — everyone can park probes in LEO without interacting.

### Proposal (Under Evaluation): Orbital congestion

> **LEO and High Orbit (GEO) each hold at most 3 income-paying `Satellite` assets across all players.** A satellite deployed at a full node still counts for missions and end-game VP, but produces no Maintenance income until a slot frees (asset destroyed or moved).

Rationale: near-Earth orbital slots are literally a licensed, contested resource in reality; this makes "deploy early" a race, lets an infrastructure player *deny* income to a copycat, and pushes latecomers outward (where income pays VP) — reinforcing the intended arc. Costs one small board element (3 slot boxes at two nodes). Recommended to trial in the second playtest, not the first (v0.5 already changes enough).

Also filed under evaluation: **more standing contracts** at higher tiers (e.g., a once-per-agency "GEO comm license"), so each agency always has a personal fallback job and turns diverge even when the shared display is picked over.

### Why no bigger change

Worker-placement-style hard blocking (e.g., unique action spaces) would fight the game's simultaneous-race identity. The mission display + ladder + market already make most points *contested*; congestion closes the one uncontested loophole.

---

## 3. Jury-Rigging — the Sideways-Card Rule (adopted)

The requested "cards played sideways have a weaker effect" rule, tuned for this game:

> **Jury-Rigging.** During an Engineering (or combined Engineering + Launch) action, you may attach **at most one card from hand sideways** to a rocket. A sideways card ignores its printed text, tags, Cost, and Mass; its effect depends only on its card type:
>
> * **Engine** → *strap-on booster*: **+1 Thrust** for the craft's launch/relaunch checks.
> * **Fuel Tank** → *drop tank*: **+1 Range** when the craft launches.
> * **Anything else** (Payload / Support / Tech) → *mass simulator*: a plain payload, **Mass 1**, no tags (Uncrewed). Occupies a payload slot.
>
> A jury-rigged card can never be staged, recovered, or targeted by effects; discard it when the craft is discarded or returns to Earth. Limit one per rocket.

### Design notes & balance math

* **Why type-based rather than free choice:** "an engine sideways is a booster, a tank sideways is a drop tank, anything else is ballast" is self-explanatory at the table and thematically exact. The free-choice variant (pick any of the three effects) is listed under evaluation as an even simpler teach, but it lets any hand become Thrust, which is stronger.
* **Exchange rate:** a hand card is already worth ~1 Credit via Emergency Sell. Each sideways mode is deliberately weaker than the ~1-Credit card it imitates: +1 Range vs Fuel Pod's +3 Range for 1 CR; Mass-1 no-tag payload vs the Light Payload's identical stats for 1 CR (equal, but it costs your card *and* your only sideways slot); +1 Thrust has no direct 1 CR analog but is capped at one per rocket.
* **What it buys the game:** every dead card (the Cryo Tank with no Hydrogen Core, the third Heat Shield) is now a tinkering decision instead of Emergency Sell fodder — the KSP "add more boosters and struts" fantasy, and a genuine early-pacing aid: round 1–2 rockets can stretch to LEO Deployment (mass simulator satisfies "Carry an Uncrewed payload" — a boilerplate test article, exactly like real first flights).
* **Interactions checked:** cannot be staged (no Staging Simulations combo); cannot be recovered (no Reusable loop); mass simulator occupies the payload slot (no double-payload smuggling); Modular Payloads (C04) can't reduce it below Mass 1; the one-per-rocket cap bounds stacking.

---

## 4. Inconsistencies Found

### Fixed in this pass (`Space_Agency.md`)

| # | Issue | Fix |
| --- | --- | --- |
| 1 | **Flush the Market** said "discard all **five** … reveal **five**" but the market has been 7 cards since v0.4. | Now seven/seven. |
| 2 | **Component-deck scaling counts stale** (~143/~114/~91 are v0.3 numbers). Actual v0.4 copies: 155 total; 3p removes 32 (→123); 2p removes 56 (→99). | Numbers corrected. |
| 3 | **M12 Lunar Sample Return was illegal as written**: it wants a Lander payload *and* a Cargo Return Capsule, but rockets allow 0–1 payload. | Initially fixed with a special Lander ride-along slot; superseded by the general **0–2 rideshare payloads** rule (see §7). |
| 4 | **Round-1 TW advance** made the 8-value printed cycle impossible to play in 8 rounds. | Advance skipped on round 1. |
| 5 | **Maintenance discards every Event**, contradicting *Stranded Crew* ("persists until claimed"). | Maintenance line now carves out persistent Events. |
| 6 | **Engineering referenced a "Launch Area"** that no rule defines (§3.2 defines the Rocket Assembly Area). | Renamed. |
| 7 | **Tech milestone wording** ("The first time a player develops their second Technology…") was ambiguous between per-player and first-player-only. | Now explicitly per-player, once each. |
| 8 | **Setup mission-swap** said "swap the oldest" — at setup there is no oldest. | Reworded (set one aside, draw until an easy mission appears, shuffle back). |
| 9 | **Node table note** for Sun Orbit ("Shared with Moon branch start") was wrong — Sun Orbit is on the Mars branch. | Note corrected. |
| 10 | **Footer said "End of Draft v0.2"** under a v0.4 header. | Version bumped to v0.5 both ends. |
| 11 | **Launch check text** said "Payload Mass" (singular) — now that two payloads are possible (#3), both mentions sum "the Mass of every Payload". | Updated in §7.3 and §9. |

### Deferred — card-text changes (need `cards.csv` + webGame regen, skipped per request)

These require editing `cards.csv` and regenerating `webGame/data/cards.json` + `cards_data.php` together, so they're queued for the next webGame-inclusive pass:

| Card | Issue | Recommendation |
| --- | --- | --- |
| **P04 Crew Capsule** | "Spend 1 Energy when this capsule launches or relaunches" has no stated payoff or consequence — dead text as written. | Make it a requirement: "To launch or relaunch with crew, spend 1 Energy (life-support checkout)." Pairs with the Basic Battery, gives crewed launches a real hardware cost. |
| **M03 Lunar Landing** | Requires "Landing Lander" by card name. | Phrase as "`Lander` payload or Rocket-as-Lander" (tag-based, like M19). |
| **M05 / M20** | Require "a Sensor Array" by card name; S11's own text says it's "required for any mission that requires Sensors". | Either keep the name consistently on both sides or introduce a requirement phrasing ("Sensors (Sensor Array)"). Low priority. |
| **S14 Landing Legs** | Tagged `Lander` ("enables surface landing") but its text requires an Engine — the tag over-claims. | Drop the `Lander` tag (its text is the rule) or redefine the tag as "part of a landing system". Note: no exploit today — missions ask for Lander *payloads*, and S14/S16 are Supports. |
| **EV04 Tech Breakthrough** | "First player to launch this round" — attempt or success? | "First player to attempt a launch (pass or fail)". |

### Verified clean

All 21 mission route Ranges check out against the node-distance table (M01–M21 ✓, TW-crossing missions correctly priced at TW 0). Card counts in §12 (88 unique / 189 copies; 8/8/5 missions per tier; 13 events) match the CSV exactly. The station-qualification chain (Hub + Power + LifeSupport + Scientific/Electronics) fits exactly in the 3 support slots. The starting kit can legally complete the Suborbital Test Flight via propulsive landing (Range 5 ≥ 2 + 1), so the "productive first turn with only the starting kit" promise holds.

---

## 5. Deferred Work (webGame)

Card **data** for the new mechanics has landed (Starter Events EV14–EV16 with the `Starter` tag; Deadweight encoded as Range −1 on P03 / P14 / P11; data files regenerated and the full test suite passing). A one-line guard in `lobby.php` keeps `Starter`-tagged events out of the engine's round event deck so current webGame behavior is unchanged. Still queued for the next PR:

1. Sync the v0.5 rules into the PHP engine (`webGame/api/engine/`): Starter Event reveal at setup, Flight Data credit, round-1 TW skip, Engine Clusters, uncapped tanks, 0–2 payloads, Deadweight math, Jury-Rigging, persistent-event exception.
2. Generate art for EV14–EV16 via the AI pipeline and re-export the nanDECK templates.
3. Apply the §4 card-text fixes to `cards.csv`, then `python3 webGame/tools/build_data.py` and run the full test suite.
4. Regenerate `docs/rulebook.html` from the updated ruleset.

---

## 6. Playtest Watch Items

Maintained in `docs/playtest_notes.md` (v0.5 section) — Starter Event divergence, Flight Data, Jury-Rigging usage, Engine Cluster balance, uncapped-tank degeneracy, rideshare asset spam, Deadweight feel, and the orbital-congestion trigger condition.

---

## 7. Revision v0.5.1 — Feedback Pass

Design-owner feedback on the first v0.5 draft produced three changes:

### 7.1 Shakedown recovery → Starter Events

See §1.1 (revised in place). Round 1's Event now comes from a benign three-card pool revealed at setup — a hardware, funding, or tempo opening instead of a guaranteed recovery exception.

### 7.2 Stack limits relaxed (full package)

The 0–1 engine / 1–3 tanks / 0–1 payload caps existed for readability only; the Thrust ≥ Mass gate is the real physical limit, so the caps now lean on it:

* **Engines 0–2 (clusters):** Thrust adds; a two-engine cluster's Reliability is the **lowest engine's modified value −1**. The −1 is load-bearing: without it, two Sterling Boosters (10 Thrust, Rel 9, 6 CR) strictly outclass Raptor-X (9 Thrust, Rel 6, 6 CR). With it, clustering is the cheap-but-riskier route to heavy lift, and Kick Stage + sustainer becomes a genuine booster stack.
* **Tanks: uncapped (min 1).** Thrust and card economy self-limit; monster stacks need a cluster to lift them, which is the fun part.
* **Payloads 0–2 (rideshare):** deploy a satellite *and* carry the mission payload; supersedes the Lander ride-along special case (one general rule instead of an exception). Guardrail added: mission "payload Mass X+" requirements refer to a **single payload card**, so two Mass-1 payloads can't fake a Mass-2 contract.
* **Support stays 0–3:** support cards are mostly mass-less, so Thrust doesn't gate them; unlimited Power/sensor stacking would break the Energy economy.

### 7.3 Deadweight — mass affects Range (the light-touch rocket equation)

Requested: total Range should respond to how much the rocket carries, embedded in card stats, simple numbers, **not** 1:1 with Mass.

Adopted design:

* A few heavy **non-tank** cards print **Range −1** (guideline: Mass 3+; a future Mass 5+ part would print −2). Currently exactly three cards: Science Module, Heavy Payload, Fuel Depot.
* Subtract attached Deadweight from total Range at launch; when a Deadweight card **leaves the craft in flight** (deployed / staged / discarded) the craft **regains** that Range — dropping the heavy cargo before the trip home is the Tsiolkovsky moment the mechanic exists for.
* **Tanks are exempt**: a tank's printed Range is already net of its own weight (this is why tank Range/Mass ratios differ by design).

Rejected alternatives: a global threshold table ("Mass ≥ 5 → −1, ≥ 7 → −2") — not embedded in cards, adds a lookup; and per-Mass scaling — a Mass-8 Mars stack losing 8 Range would make long missions impossible and re-tune every mission budget. The sparse per-card stat leaves all existing mission Range math intact (verified: every current mission remains completable with Mass-2 payloads, and Heavy Payload builds still close M07 with the Standard Tank).

**Balance note:** Deadweight slightly deepens the existing Science Module vs Rover/Telescope choice (P03 is now Mass 3 *and* −1 Range, but remains the only Mass-3 Scientific payload with the Moon-orbit activation). Watch it in playtest; if P03 stops getting flown, drop its Deadweight before weakening the rule.
