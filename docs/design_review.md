# Space Agency Race — Design Review & Improvement Plan

*Review of Rulebook (docs/rulebook.html), Card Set v0.2 (cards/cards.csv, 142 cards), and Design Draft v0.2 (Space_Agency.md).*

---

## 1. Executive Summary

Space Agency Race has a genuinely strong core. The **Thrust = launch gate / Range = delta-v budget** abstraction is the best kind of simplification: it captures the KSP feeling of "can I lift it?" and "can I get there and back?" in two numbers, without a rocket-equation mini-game. The **Transfer Window** track is an elegant Hohmann-window stand-in, **staging** correctly models mass-ratio gains, and the **deploy-and-fly-home** satellite rule is a great moment of play. The node map (Earth branch → Earth ZOI → Moon/Mars branches) is readable and thematic. Keep all of this.

The prototype's problems cluster in four areas:

1. **Rules-vs-cards drift** — ~16 concrete inconsistencies where card text, tags, the rulebook, and the design doc disagree (Section 2).
2. **Economy dominance** — persistent-asset VP income (Imaging Probe) outperforms the game's marquee missions, and two of the nine actions are strictly wasteful (Section 3).
3. **Content gaps** — too few missions (the game can end by round 3 on the mission-deck trigger), no Mars Landing, one lonely crewed mission, and several cards with abilities that do nothing (Section 4).
4. **Tag sprawl** — ~40 tags in the CSV, ~24 defined in the rulebook, only ~16 with any rules meaning (Section 5).

None of these require redesign — the fixes are consolidations, clarifications, and additional cards. A prioritized roadmap is in Section 8.

---

## 2. Inconsistencies (rules vs cards vs design doc)

Each item names the affected cards/sections and a recommended fix.

### 2.1 "Accept Mission" is a no-op action
All missions are Public and can be raced; nothing in the completion rules requires having accepted the mission. The action burns one of a Level-1 player's ~8 total actions per game for zero effect.
**Fix (pick one):** delete the action; or make acceptance meaningful ("when you complete a mission you Accepted, gain +1 CR"). Deleting is cleaner. *(rulebook.html §5, Space_Agency.md §7.2)*

### 2.2 Mission deck can end the game by round 2–3
End trigger: "game ends when the Mission deck runs out." There are only 4 Tier-1 missions; 3 are dealt face-up at setup, leaving a **1-card deck**. Two early completions can empty it before anyone reaches Level 2. It's also undefined whether "runs out" means the deck is empty or the display can't refill.
**Fix:** end trigger = fixed 8 rounds only; an empty mission deck simply stops refills. Also add missions (Section 4). *(rulebook.html §3/§11, Space_Agency.md §10)*

### 2.3 Mission-display deadlock
Missions leave the 3-slot display only when completed. M06 *Crewed Station Visit* requires an On-Orbit Station — a chain needing P08 Station Hub (2 copies in a 122-card component deck) + Power + LifeSupport + Scientific/Electronics, all deployed at GEO. If nobody builds a station, M06 clogs a display slot forever; two such missions lock 2/3 of the contract economy.
**Fix:** Maintenance sweep — if no mission was completed this round, discard the oldest displayed mission and refill. *(rulebook.html §4 Maintenance)*

### 2.4 Engines missing lifecycle tags
The rulebook says engines "may be Reusable, Disposable, or Stageable." **E03 Hydrogen Core, E04 Ion Sustainer, and E06 Raptor-X carry none of the three.** They are de-facto disposable via the default recovery rule, but then the explicit `Disposable` tag on E02/E07 is redundant.
**Fix:** declare "no Reusable tag = discarded" as the default (it already is, in Recovery) and delete `Disposable` entirely — or tag every engine. The first option supports the tag cleanup in Section 5. *(cards.csv E03/E04/E06)*

### 2.5 Orbital Tug text/tag mismatch
S06 Orbital Tug says "If this craft returns to Earth, return this card to hand" — Reusable behavior — but its tags are `Docking;Maneuver`, no `Reusable`. The recovery rule keys off the tag.
**Fix:** add `Reusable` (its cost already fits the "+1 CR over disposable" guideline). *(cards.csv S06)*

### 2.6 Flight Computer references a nonexistent check
S10 grants +1 Reliability "when this craft launches, **docks**, or relaunches." There is no docking check anywhere in the rules — docking only requires a Docking card + 1 Energy.
**Fix:** remove "docks," or introduce a docking check (not recommended; more dice ≠ more fun). *(cards.csv S10)*

### 2.7 Dead rules text
- **P03 Science Module:** "Spend 2 Energy to activate this module's instruments" — no effect defined.
- **S11 Sensor Array:** "Spend 1 Energy to activate this card" — activate to what end? Its real function (Tier-3 science gate) is passive.
- **T05 Pressurized Tank:** "Safer for crewed payloads" — no rule exists; identical stats and cost to T01 Standard Tank, which is also `Basic` (always purchasable). T05 is strictly worse.
- **E06 Raptor-X:** its failure text restates the default failure rule verbatim.
**Fix:** every activated ability must name its payoff — see the conditional-activation system in Section 4.2, which turns P03 and S11 into an economy. Give T05 a real rule (Section 3.3). *(cards.csv P03/S11/T05/E06)*

### 2.8 Tag/name mismatches on mission requirements
- **M08 Science Relay** requires "Scientific or **Comm** payload" — no `Comm` tag exists (P01 is `Uncrewed;Electronics;Satellite`). Use `Scientific or Electronics payload`.
- **M06** requires "a **Crewed Capsule**" — the card is named "Crew Capsule"; require "a `Crewed` payload" instead so future crewed payloads qualify.
- **M09** requires "an **Engine with remaining Range**" — engines don't have Range, craft do. Say "a craft with an Engine and remaining Range."
- **M10** requires "payload Mass 1 + Heat Shield or Parachute" — ambiguous between "Mass exactly 1" and "Mass 1+". Every other mission uses "Mass 2+" phrasing; write "Mass 1+" if that's the intent.
*(cards.csv M06/M08/M09/M10)*

### 2.9 `Uncrewed` tagging is incomplete
M01 requires an Uncrewed payload. P01/P02/P05/P07 carry the tag, but P03 Science Module, P06 Landing Lander, and P08 Station Hub — all uncrewed — don't, so a Landing Lander can't legally fulfill *LEO Deployment*.
**Fix:** define **Uncrewed = any payload without the `Crewed` tag** and delete the tag from all cards. One line of rules replaces eight tag instances. *(rulebook.html §13, cards.csv)*

### 2.10 Transfer Window progression is undefined
"Advance the TW marker one step along its track (range 0–5)" — what happens at 5? Wrap to 0, bounce back, stay? Never stated; the design doc lists the schedule as an open question, but the rulebook must be playable today.
**Fix:** print a deterministic cycle on the board (recommended: `3-2-1-0-1-2-3-4` repeating). This is *more* thematic, not less — real transfer windows are predictable, and planning around a visible window is exactly the KSP skill the game wants to reward. It also removes randomness from the highest-stakes decision in the game. *(rulebook.html §4/§7)*

### 2.11 "Atmosphere" is never defined
S07 Solar Panel: "If this craft enters atmosphere, discard this card." Which nodes have atmosphere is unstated.
**Fix:** one glossary line — *Atmosphere nodes: Earth, Sub-Orbital Earth, Sub-Orbital Mars, Mars Surface. The Moon branch has no atmosphere.* (This also cleanly explains why Moon landings are propulsive-only.) *(rulebook.html §14)*

### 2.12 Milestone mismatches
- `Space_Agency.md` grants **+1 VP for a player's second Technology**; the rulebook omits it.
- "First to develop **4** Technologies: +2 VP" is nearly unreachable: only 4 distinct tech names exist, duplicates are banned, so it requires collecting *every* tech name from a 122-card deck within 8 rounds.
**Fix:** sync the docs; rescale to "first to 3 Technologies" until the tech pool grows (Section 4.3). *(rulebook.html §5, Space_Agency.md §7.2)*

### 2.13 Staging count ambiguity
Pre-flight staging allows "one" card and mid-flight staging says "stage one Stageable card" — per flight? per node? unlimited? A triple-Fuel-Pod build cares a lot.
**Fix:** "Each Stageable card may be staged once; you may stage any number of cards per flight, but at most one per node crossing." (Keeps the KSP multi-stage fantasy, prevents a single mid-node dump from being a pure math exercise.) *(rulebook.html §6/§8)*

### 2.14 `Maneuver` tag semantics conflict
Defined as a *mission* tag ("Mission requires orbital maneuvering; Engine mandatory") but appears on support card S06 Orbital Tug.
**Fix:** keep it mission-only; S06's docking/`Reusable` tags already describe it. *(cards.csv S06, rulebook.html §13)*

### 2.15 End-game bonuses promised, none exist
Design doc §10 lists "End-game bonuses" as a VP source; no card or rule grants any.
**Fix:** either delete the line or add a small set (recommended: **1 VP per deployed persistent asset still on the board at game end** — rewards infrastructure without bookkeeping, and gives P05 CubeSat a purpose, Section 3.5). *(Space_Agency.md §10)*

### 2.16 Engine-free craft mass rule missing from rulebook
Design doc: "If your craft has no Engine but is already in flight, its Total Mass must be ≤ 3." The rulebook never mentions it.
**Fix:** add to Rocket Design or delete from the doc — as written it mainly affects deployed assets, which are already parked. *(rulebook.html §6)*

---

## 3. Balance Analysis

### 3.1 Imaging Probe income is the dominant strategy ⚠ (highest priority)
P02 Imaging Probe (2 CR, Mass 1) + S07 Solar Panel (1 CR): deploy in LEO by round 2 with the starting rocket, then Asset Operations pays **1 VP per round, free, forever** — ~6 VP per probe, zero risk, and there are 3 copies plus 2 Microgravity Labs doing the same at a station.

Compare the marquee mission: M04 *Mars Orbit Insertion* (13 VP) needs ~12 CR of hardware (long-range tank + high-thrust engine + Mass-2 payload), Level 3 (14 CR, itself gated behind Level 2's 6 CR), a reliability roll, and TW timing. Two quiet probes match it with none of the above.

**Fix — thematic, not a flat nerf: scale asset income by distance.**
- Persistent asset on the **Earth branch** (LEO/GEO): income pays **1 CR**.
- Persistent asset at **Moon Orbit or beyond, or Sun Orbit or beyond**: income pays **1 VP**.

LEO probes still fund you (CR is useful), but VP farming now requires flying to the Moon or deep space — harder launch, more Range, real cost. Infrastructure migrates outward over the game, which is exactly the space-program arc you want. Applies to P01, P02, P08, S13.

### 3.2 Raptor-X is overcosted
E06: 6 CR, Thrust 9, Reliability 6 → 60% success, engine lost on failure. Expected cost per successful launch ≈ 6 / 0.6 = **10 CR**, vs E03 Hydrogen Core (5 CR, Thrust 8, Reliability 7) with a softer failure mode. Nobody should buy it.
**Fix:** make Raptor-X **Reusable** (thematically on the nose) and keep 6 CR / R6. Risk stays, but success builds a lasting asset — the experimental-heavy-lift gamble the card wants to be.

### 3.3 Pressurized Tank is a dead card
Same cost/stats as the always-available Basic Standard Tank; its only differentiator is a non-functional flavor tag.
**Fix:** give the `Crewed` tag real teeth: **"A craft carrying a Crewed payload must include a Pressurized tank."** T05 becomes mandatory crewed hardware, the `Crewed` tag's vague "life-support consideration" becomes a concrete rule, and crewed missions get the extra hardware constraint they thematically deserve. (Price T05 at 3 CR when this lands, per the "specialization costs +1" guideline.)

### 3.4 Science Module is dominated
P03 (3 CR, Mass 3) loses to P02 Imaging Probe (2 CR, Mass 1) for every `Scientific` requirement, and its activation has no effect.
**Fix:** see Section 4.2 — P03 becomes the deep-space activation specialist. Optionally make Tier-3 science missions require a "Mass 2+ Scientific payload" so the heavy module has an exclusive niche.

### 3.5 CubeSat Cluster is pure filler
1 CR, satisfies "Uncrewed payload"/"On-Orbit Satellite" requirements, then does nothing.
**Fix:** with the end-game bonus from 2.15 ("1 VP per deployed asset at game end"), cheap cubesats become a real low-risk strategy without any new text on the card.

### 3.6 Sell Part action is dominated
It costs a Command Turn and pays ⌊cost/2⌋ — 0 CR for every 1-cost card — while the Planning-phase Emergency Sell pays 1 CR for any card and costs nothing.
**Fix:** delete the action; extend Emergency Sell to "discard up to 2 cards for 1 CR each" if players need more liquidity.

### 3.7 First-player advantage is structural
Identical starting kits + public raced missions + alternating single actions = the first player reliably wins the round-1/2 race to the best Tier-1 contract.
**Fix:** staggered starting credits — P1: 5, P2: 6, P3: 7, P4: 8. Cheap, standard, invisible.

### 3.8 Action starvation at Level 1
A Level-1 player takes ~8 actions in the entire game, and Level 2 (6 CR) costs more than starting credits. The KSP fantasy is tinkering; one move per round is austerity.
**Playtest candidates (pick one):** Level 1 = 2 Command Turns (Levels 2/3 = 3/4); or Level 2 costs 4 CR; or Level 3 drops 14 → 12 CR. Recommendation: try "Level 1 = 2 turns" first — it doubles the fun floor without touching the economy.

### 3.9 Mission economy reference table
For future tuning — VP per Range point (CR in parens), current set:

| Mission | Tier | VP/Range | Notes |
|---|---|---|---|
| M10 Capsule Recovery | 1 | 3.0 (+2) | best T1 ratio |
| M11 Reusable Flight Test | 1 | 2.0 (+3) | needs ~5 CR reusable kit |
| M01 LEO Deployment | 1 | 1.0 (+5) | the CR engine |
| M07 Emergency Resupply | 1 | 0.75 (+6) | the CR engine |
| M03 Lunar Landing | 2 | 1.3 (+3) | one-way: strands ~7 CR hardware |
| M02 Lunar Flyby | 2 | 0.7 (+2) | hardware returns |
| M08 Science Relay | 2 | 1.0 (+3) | |
| M06 Crewed Station Visit | 2 | 0.8 (+4) | gated on station existing |
| M09 Orbital Service Check | 2 | 1.0 (+6) | gated on owning a satellite |
| M04 Mars Orbit Insertion | 3 | ~1.6 (+3) | at TW 1 |
| M05 Deep Space Probe | 3 | ~1.5 (+2) | heaviest requirements |
| M12 Lunar Sample Return | 3 | 0.9 (+3) | hardest logistics, same VP as M04 — consider 14–15 VP |

The tier curve is broadly sane; M12 is underpaid for its logistics (lander + return capsule + reentry support + 14 Range).

---

## 4. Missing Cards

### 4.1 Missions — expand Tier 1 and Tier 2 first
Target counts: **T1: 4 → 7, T2: 5 → 8, T3: 3 → 5** (20 total). This fixes the deck-exhaustion bug (2.2), gives the display real variety, and supports 4-player races. Suggested specs:

| New Mission | Tier | Route / Requirements | Reward | Class |
|---|---|---|---|---|
| **Tourist Hop** | 1 | Earth → Sub-Orbital → Earth; `Crewed` payload + reentry support | 2 VP + 4 CR | Commercial |
| **Weather Satellite** | 1 | Deploy a `Satellite` at High Orbit (GEO) | 3 VP + 3 CR | Infrastructure |
| **Sounding Flight** | 1 | Earth → Sub-Orbital → Earth; `Scientific` payload, spend 1 Energy | 3 VP + 2 CR | Prestige |
| **Lunar Orbiter** | 2 | Deploy a `Satellite` at Moon Orbit | 6 VP + 2 CR | Infrastructure |
| **Crewed Lunar Flyby** | 2 | Earth → Moon Orbit → Earth (Range 10); `Crewed` payload + reentry support | 8 VP + 2 CR | Prestige |
| **Station Assembly** | 2 | Designate an On-Orbit Station at GEO | 6 VP + 4 CR | Infrastructure |
| **Mars Landing** | 3 | Earth → Mars Surface (10+TW); Lander or Rocket-as-Lander | 16 VP + 2 CR | Prestige |
| **Asteroid Rendezvous** | 3 | Reach Sun Orbit; Sensor Array + `Scientific` payload, spend 2 Energy | 11 VP + 4 CR | Prestige |

Notes:
- **Mars Landing is the map's missing capstone** — Mars Surface currently has *no* mission (M05 "Deep Space Probe" targets Sub-Orbital Mars, which is also thematically odd for a probe; consider retargeting M05 to Sun Orbit/Mars ZOI and letting Asteroid Rendezvous take the sensor niche).
- **Tourist Hop and Crewed Lunar Flyby give the Crew Capsule a career** — it currently supports exactly one mission (M06). This creates the Apollo arc: Tourist Hop (T1) → Crewed Lunar Flyby (T2) → future Crewed Mars mission.
- **Station Assembly pays off the station chain directly** and un-deadlocks M06: someone now has a reward for building the thing M06 needs.
- **Weather Satellite / Lunar Orbiter** make "deploy an asset" a scored contract, feeding the asset-income path.

### 4.2 Conditional activation income — sensors and comms earn under the right conditions
Today, activation text on P03/S11 does nothing. Instead of deleting it, make activations a situational economy keyed to **position** and **Events** — bad weather becomes an opportunity for whoever built the right hardware:

| Card | New activation text |
|---|---|
| **S11 Sensor Array** | "Spend 1 Energy: gain **1 VP** if a storm Event (Solar Storm, Transfer Window Storm) is active, **or** if this craft is at Sun Orbit or beyond." |
| **P01 Comm Satellite** | Keep 1 Energy → 1 CR relay income; add: "While deployed, when another player's craft moves beyond Earth ZOI, you may spend 1 Energy: gain **1 CR** from the bank (relay services). Once per round." |
| **P03 Science Module** | "Spend 2 Energy at Moon Orbit or beyond: gain **1 VP** (**2 VP** while a storm Event is active). Once per round." |

New Events to feed the system (also diversifies the all-global-symmetric event deck):

| Event | Effect |
|---|---|
| **Solar Flare Watch** | Solar-storm flavor twist: launches suffer −1 Reliability this round, but Sensor Array activations pay double. |
| **Broadcast Rights** | Comm Satellite / Station income abilities pay +1 CR this round. |
| **Media Frenzy** | Prestige missions completed this round grant +1 VP. |
| **Comms Blackout** | New missions can't be completed this round unless a player controls a deployed Electronics asset (theirs or paid 1 CR to another player's). |

**Design rule to adopt:** *every* activated ability must name its payoff, and each Event should create an activation opportunity for somebody. (Also replace EV04 Tech Breakthrough's "search the 122-card deck and reshuffle" with "reveal cards until a Technology appears; take it, discard the rest" — same effect, no shuffle.)

### 4.3 Technologies — pool is too small (4 names)
Add 4–6, one per strategic path:

| Tech | Effect | Path served |
|---|---|---|
| **Trajectory Planning** | Your craft treat the Transfer Window cost as 1 lower (min 0). | Explorer / Mars |
| **Launch Abort System** | When you fail a Reliability check, you may pay 2 CR to reroll once. | Risk management |
| **Contracting Office** | When you complete a Commercial mission, gain +1 CR. | Economy |
| **Mass Production** | Basic cards cost you 1 less (min 1). | Economy / reuse |
| **Staging Simulations** | Your Stage bonuses give +1 additional Range. | Staging builds |
| **Deep Space Network** | Your deployed assets beyond Earth ZOI produce +1 Energy. | Asset network |

With ~10 names in the pool, restore the tech milestones as: 2nd tech +1 VP (already in design doc — sync to rulebook), 4th tech +2 VP (now actually reachable).

### 4.4 Payloads & Support
| Card | Sketch | Why |
|---|---|---|
| **Rover** (Payload, 3 CR, Mass 2, `Scientific`) | Deploy on a Moon/Mars surface: 1 Energy → 1 VP in Asset Operations. | Makes landings pay ongoing value; surface counterpart of the probe. |
| **Space Telescope** (Payload, 4 CR, Mass 2, `Scientific;Satellite`) | Deploy at High Orbit or beyond: 1 Energy → 1 VP; 2 VP instead during storm Events. | Premium VP asset with event synergy. |
| **Fuel Depot** (Payload, 4 CR, Mass 3, `Station`) | Deploy in orbit: an adjacent activating craft may spend 1 of the depot's Energy to gain +2 Range (once per round). | Extremely KSP; enables refueled Mars runs; infrastructure that serves other missions. |
| **Landing Legs** (Support, 1 CR, `Lander`) | Propulsive landings with this craft cost no extra Range. | Cheap surface-program enabler between "nothing" and the Lander payload. |

### 4.5 Events
8 events for 8 rounds is exactly enough, but all current events are global and symmetric. The four in 4.2 add asymmetric texture. Consider also a **TW Forecast** event ("look at next round's TW; you may move the marker one step either way") to reward planners.

---

## 5. Tag Simplification

Current state: the CSV uses ~40 distinct tags, the rulebook legend defines 24, and only ~16 have any rules meaning. Every non-functional tag is noise a player must rule out at the table.

### Keep (functional — 17 tags)
`Basic`, `Reusable`, `Stageable`, `Cryogenic`, `Pressurized` (with the new crewed rule), `Crewed`, `LifeSupport`, `Power`, `Electronics`, `Scientific`, `Docking`, `Satellite`, `Station`, `Lander` (rename of payload-side `Surface`), and mission classes `Commercial` / `Prestige` / `Infrastructure`.

### Merge
| Old | New | Rationale |
|---|---|---|
| `Heat Shield`, `Parachute` | **`Reentry`** | The rules treat them identically everywhere ("land safely from Sub-Orbital, discard unless Reusable"). The distinction is flavor — keep it in the card name and art. |
| `In-Flight`, `On-Orbit` | **`In-Space`** | Both mean "completable by a craft already in space." |

### Delete (flavor-only → move to name/flavor text)
`Disposable` (absence of `Reusable` already means discarded — one default rule replaces a tag), `Reliable`, `Stable`, `Balanced`, `Efficient`, `HighThrust`, `LowThrust`, `Cheap`, `Extended`, `Expandable`, `Heavy`, `Small`, `Fragile` (S07 already spells out the discard rule in text), `Solar`, `Guidance`, `Recovery`, `Relay`, `Upgrade`, `Permanent`, `Compatible`, `Support`, `Flexible`, `Experimental` (currently no mechanic — either delete or define once: "Experimental cards suffer an extra −1 Reliability during storm Events"), `Uncrewed` (define as "any payload without `Crewed`", per 2.9), `Public` (all missions are public — say it once in the rulebook), and destination tags `LEO`/`Lunar`/`Mars`/`DeepSpace` (the route text already names the destination).

**Result:** every tag on every card means something, the rulebook legend shrinks to ~17 one-line entries, and mission requirements can always be phrased as "have a card with tag X" — which is the whole point of a tag system.

---

## 6. Multiple Paths to VP and Credits (design pillar)

Audit of scoring routes after the Section 3–4 changes. The goal: several viable engines, each with a VP source *and* a CR source, none dominant.

| Path | VP source | CR source | Status after fixes |
|---|---|---|---|
| **Contract runner** | Prestige missions | Commercial missions | Healthy; more T1/T2 missions give it room (4.1) |
| **Asset network** | Probe/Lab/Telescope income *beyond Earth orbit* | Comm Sat / Station Hub income in Earth orbit | Distance-scaled income (3.1) keeps it strong but no longer free |
| **Explorer** | Moon/Mars milestones + long-range Prestige (Mars Landing) | thin — consider +2 CR on exploration milestones | Mars Landing added (4.1) |
| **Science ops** | Conditional activations: sensors during flares, deep-space labs | Asteroid Rendezvous pays CR-heavy for a Prestige | New system (4.2) |
| **Infrastructure / station** | Station Assembly, docking Events, Microgravity Lab | Station operations, M09 servicing, Fuel Depot leverage | Un-deadlocked by Station Assembly + display sweep |
| **Tech / industry** | Tech milestones (2nd/4th tech) | Contracting Office, Reusable Refurb recovery income | Viable once tech pool grows (4.3) |

**Guidelines to adopt:**
- Every card type should feed at least one VP route and one CR route.
- Events should rotate the spotlight between routes round to round (storm → science ops; Broadcast Rights → asset network; Media Frenzy → contract runners).
- When adding a card, name which path it serves; if the answer is "none," it's filler.

---

## 7. Fun & KSP-Feel Improvements

1. **Deterministic Transfer Window cycle** printed on the board (`3-2-1-0-1-2-3-4`, repeating). Real windows are predictable; planning three rounds ahead for a TW-0 burn *is* the Hohmann fantasy, and it fixes the undefined-advance bug (2.10). Events still perturb it, which now feels like weather instead of noise.
2. **Aerobraking:** while descending toward a body with atmosphere, a craft may discard a `Reentry` card to gain +2 Range. Classic KSP maneuver, gives heat shields a mid-flight decision ("burn it now for the capture, or save it for the landing?").
3. **Distance-scaled asset income** (3.1): the economic map mirrors the physical map — near-Earth pays cash, deep space pays glory.
4. **Rescue mission (event-spawned):** "Stranded Crew — a capsule is adrift in LEO. First player to reach LEO with a `Crewed`-capable craft and return to Earth gains 5 VP." Beloved KSP trope, zero new components.
5. **Action list slims from 9 to 6:** delete Accept Mission (2.1) and Sell Part (3.6); fold Upgrade Rocket into Assemble Rocket (one "Engineering" action: build and/or modify, optionally launch if all parts are in hand). Fewer, chunkier decisions per turn.
6. **Mission display sweep** (2.3) keeps contracts fresh and prevents deadlock.
7. **Fixed 8-round game** (2.2) makes pacing predictable for the 30–45 minute promise.

---

## 8. Improvement Roadmap

### Phase 1 — Fix contradictions (no new content; makes v0.2 cleanly playable)
| # | Change | Files |
|---|---|---|
| 1 | Delete Accept Mission + Sell Part actions; fold Upgrade into Assemble | rulebook.html §5, Space_Agency.md §7.2 |
| 2 | End trigger = 8 rounds only; add mission-display sweep | rulebook.html §3/§4/§11 |
| 3 | Define TW cycle on the board; define atmosphere nodes | rulebook.html §4/§7/§14 |
| 4 | Fix card text: S06 +`Reusable`, S10 remove "docks", E06 remove redundant text, M06/M08/M09/M10 requirement wording | cards.csv |
| 5 | Uncrewed = "no Crewed tag"; staging = once per card, one per node crossing | rulebook.html §8/§13 |
| 6 | Sync tech milestones (2nd tech +1 VP) between docs; rescale 4th→3rd tech until pool grows | rulebook.html §5, Space_Agency.md |

### Phase 2 — Rebalance & streamline (playtest after each)
| # | Change | Files |
|---|---|---|
| 7 | Distance-scaled asset income (1 CR near Earth / 1 VP beyond) | cards.csv P01/P02/P08/S13, rulebook.html §7 |
| 8 | Raptor-X → Reusable; T05 Pressurized rule + Crewed requirement; M12 → 14–15 VP | cards.csv, rulebook.html §13 |
| 9 | Tag consolidation per Section 5 (~40 → ~17 tags) | cards.csv Tags column, rulebook.html §13 |
| 10 | Staggered starting credits (5/6/7/8); trial Level 1 = 2 Command Turns | rulebook.html §3/§5 |
| 11 | End-game bonus: 1 VP per deployed asset on the board | rulebook.html §11 |

### Phase 3 — New content
| # | Change | Files |
|---|---|---|
| 12 | +8 missions per Section 4.1 (T1 priority), retarget M05 | cards.csv |
| 13 | Conditional activation system + 4 new Events (Section 4.2) | cards.csv |
| 14 | +4–6 Technologies (Section 4.3) | cards.csv |
| 15 | Rover, Space Telescope, Fuel Depot, Landing Legs (Section 4.4) | cards.csv |
| 16 | Aerobraking rule + Rescue event | rulebook.html §7, cards.csv |

### Playtest instrumentation (extends docs/playtest_notes.md)
- VP earned per path (missions vs asset income vs milestones vs activations) — watch for probe dominance post-fix.
- Actions "wasted" per player (acquires that never launched, etc.).
- Round in which each Tier unlocked; whether Tier 3 ever appeared.
- Whether any mission sat uncompleted in the display for 3+ rounds.

---

*Review based on: rulebook.html (1,655 lines), cards.csv (58 unique cards / 142 total incl. copies: 24 engines, 25 tanks, 24 payloads, 39 support, 10 tech, 12 missions, 8 events), Space_Agency.md v0.2. All mission Range values were verified against the node-distance table and are internally consistent (M01–M12 ✓).*

---

**Implementation status:** Phases 1–3 of the roadmap have been applied to `cards.csv`, `docs/rulebook.html`, and `Space_Agency.md` (see the commit history on this branch). The card set is now v0.3: 81 unique cards / 176 total, 20 missions (7/8/5 per tier), 13 events. New cards (M13–M20, EV09–EV13, C05–C10, P09–P11, S14) still need art generated via the AI art pipeline and the nanDECK templates re-exported.
