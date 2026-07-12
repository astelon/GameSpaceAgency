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

**Fix (adopted): Shakedown recovery.** Completing the Suborbital Test Flight returns **all unstaged parts of that craft to hand, Reusable or not** — the test articles are recovered and rebuilt. Round 1 becomes pure gain (2 CR + 1 VP + a proven rocket still in hand), it teaches the recovery rules by exception, and because the contract is once-per-agency it cannot be farmed. It also sets up the arc: *disposable era → buy Reusable hardware → true program*.

### Problem 1.2 — A failed round-1 launch is a pure feel-bad (fixed)

The Sterling Booster is Reliability 9, so 1 game in 10 a player's very first launch fails, discards their only engine, and their opening round produces *nothing*. KSP failures are fun because the explosion itself yields science; here it yielded only a lost turn.

**Fix (adopted): Flight Data.** Whenever one of your launches fails its Reliability check, gain **1 Credit** (telemetry). Small enough to never be worth failing on purpose (an intentional failure still wastes a Command Turn and risks the engine), but it converts every fireball into forward motion. It also quietly re-prices risk engines: Raptor-X's 40% failure now returns 1 CR, nudging the "experimental heavy lifter" gamble toward playable.

### Problem 1.3 — Round-1 Transfer Window ambiguity (fixed)

The Planning Phase advances the TW marker every round, including round 1 — but the printed cycle `3-2-1-0-1-2-3-4` has exactly 8 values for 8 rounds, which only lines up if round 1 *plays* the first value. As written, round 1 would immediately advance 3 → 2 and the 4 would never be played. **Fix (adopted):** skip the advance on round 1; rounds 1–8 play the printed values in order. This also makes the whole game's Mars windows plannable from turn one — very KSP.

### Observations (no change, watch in playtest)

* **Hand-limit collision in round 1:** 3 starting components + 2 drawn = 5 = the hand limit. Any round-1 Acquire forces a discard unless components were attached first. This reads as intentional "build, don't hoard" pressure — keep, but confirm it doesn't confuse new players.
* **Two Command Turns at Level 1** plus the combined Engineering+Launch action is a good floor; with Shakedown recovery the typical round 1 is now *fly the test flight + buy a part* — fast and forward-moving. No further change recommended before playtesting.

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
> * **Anything else** (Payload / Support / Tech) → *mass simulator*: a plain payload, **Mass 1**, no tags (Uncrewed). Occupies the payload slot.
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
| 3 | **M12 Lunar Sample Return was illegal as written**: it wants a Lander payload *and* a Cargo Return Capsule, but rockets allow 0–1 payload. | New rule: one additional `Lander` payload may ride along with the mission payload (both Masses count). Dedicated landers now work the way the missions assume. |
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
| **M21 Suborbital Test Flight** | Card text doesn't mention Shakedown recovery (rules-level exception added in v0.5). | Add a line to the card text at next CSV pass so the card teaches its own reward. |

### Verified clean

All 21 mission route Ranges check out against the node-distance table (M01–M21 ✓, TW-crossing missions correctly priced at TW 0). Card counts in §12 (88 unique / 189 copies; 8/8/5 missions per tier; 13 events) match the CSV exactly. The station-qualification chain (Hub + Power + LifeSupport + Scientific/Electronics) fits exactly in the 3 support slots. The starting kit can legally complete the Suborbital Test Flight via propulsive landing (Range 5 ≥ 2 + 1), so the "productive first turn with only the starting kit" promise holds.

---

## 5. Deferred Work (webGame)

Skipped per request; queue for the next pass:

1. Sync the v0.5 rules into the PHP engine (`webGame/api/engine/`): Flight Data credit, Shakedown recovery, round-1 TW skip, Lander ride-along slot, Jury-Rigging, persistent-event exception.
2. Apply the §4 card-text fixes to `cards.csv`, then `python3 webGame/tools/build_data.py` and run the full test suite.
3. Regenerate `docs/rulebook.html` from the updated ruleset.

---

## 6. Playtest Watch Items (added to `docs/playtest_notes.md`)

* Does round 1 now feel like progress (Shakedown recovery), and do players ever *skip* the test flight to race LEO Deployment instead? Both should be viable.
* Jury-Rigging: frequency of use, which mode dominates, and whether mass simulators crowd out the Basic Light Payload (they shouldn't — equal stats, but cost a card and the sideways slot).
* Flight Data: does 1 CR make failures feel fair? Does Raptor-X get bought now?
* Lander ride-along: watch Moon/Mars builds — is M12 now completable in a real game?
* If LEO probe-parking still feels solitaire, trial the §2 orbital-congestion cap in the second playtest.
