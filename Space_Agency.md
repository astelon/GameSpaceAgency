# 🚀 SPACE AGENCY RACE

**Official Design & Ruleset Draft (v0.5)**

> *This document defines the current state of the game design. It is intended as the **first official draft** to be reviewed by designers, developers, and playtesters. The goal is to present a **complete, playable ruleset** while clearly documenting design intent, assumptions, and future iteration points.*

---

## 1. High‑Level Vision

**Space Agency Race** is a **competitive, medium‑weight strategy board/card game** where players lead rival space agencies. Players design rockets, plan launches, manage limited windows of opportunity, and complete missions to earn **Victory Points (VP)**.

### Core Fantasy

> *"We are running a space agency under pressure: engineering constraints, launch timing, and strategic trade‑offs matter."*

The game emphasizes:

* Engineering trade‑offs (engines, fuel, payload)
* Timing and planning (launch windows, transfer phases)
* Risk vs reward (experimental tech, rushed launches)
* Strategic engine building (cards synergize over time)

---

## 2. Game Overview

* **Players:** 2–4 (designed primarily for 3–4)
* **Playtime:** 30–45 minutes
* **Complexity:** Medium (Euro‑inspired, low randomness)
* **Primary Mechanisms:**

  * Card drafting & tableau building
  * Action selection
  * Resource management
  * Set collection / synergy

### Objective

Accumulate the **most Victory Points (VP)** by the end of the game through:

* Successful missions
* Efficient use of Credits
* Satellites and long‑term assets
* Technological achievements

---

## 3. Components (Current Scope)

### 3.1 Card Types

| Type                         | Purpose                                              |
| ---------------------------- | ---------------------------------------------------- |
| **Engine Cards**             | Define rocket thrust, reusability, special abilities |
| **Fuel Tank Cards**          | Provide range, payload support, and stability        |
| **Payload Cards**            | Satellites, probes, crew, experiments                |
| **Support Cards**            | Heat shields, parachutes, docking adapters, and other hardware |
| **Mission Cards**            | Public contract opportunities tied to destinations   |
| **Technology Cards**         | Permanent upgrades and rule‑breakers                 |
| **Event Cards**              | Global effects, timing pressure, limited randomness  |

### 3.2 Player Area

Each player maintains:

* **Agency Tableau** (played technologies, assets)
* **Rocket Assembly Area** (engine + fuel + payload)
* **In-Flight Area** (craft currently on the orbital board)
* **Hand** (limited size)
* **Credit Marker** on a personal Credit track
* **Agency Level Marker**

### 3.3 Shared Areas

* Orbital Board with **VP Track**, **Orbital Node Map**, and **Transfer Window Track**
* **Card Market** (face-up row of component cards available for purchase)
* Mission Deck & Display
* Event Deck
* Discard Piles

### 3.4 Orbital Board (First Draft)

The orbital board combines the **VP track** with a simple **orbital node map** for craft movement.

Prototype node map:

**Earth branch:**
`Earth` → `Sub-Orbital Earth` → `LEO` → `High Orbit (GEO)` → `Earth ZOI`

**Moon branch (from Earth ZOI):**
`Earth ZOI` → `Moon Orbit` → `Sub-Orbital Moon` → `Moon`

**Mars branch (from Earth ZOI):**
`Earth ZOI` → `Sun Orbit` →*Transfer Window*→ `Mars ZOI` → `Mars High Orbit` → `Mars Low Orbit` → `Sub-Orbital Mars` → `Mars Surface`

Each line between nodes costs **1 Range** to cross, except the **Transfer Window** crossing (Sun Orbit → Mars ZOI) which costs **0–5 Range** depending on planetary alignment.

#### Transfer Window

The Transfer Window represents planetary alignment for interplanetary transfers. Its cost (**TW**, range 0–5) changes each round according to a track printed on the board. Event cards may also modify TW for a round.

* **TW 0** – Perfect window; the crossing is free.
* **TW 5** – Worst alignment; costs 5 extra Range.

> Design intent: Mars missions reward timing. A launch during TW 0 saves up to 5 Range compared to TW 5, creating strategic windows that players plan around.

#### Node Distances from Earth

| Node | Distance | Notes |
| ---- | -------- | ----- |
| Sub-Orbital Earth | 1 | |
| LEO | 2 | |
| High Orbit (GEO) | 3 | Earth-orbit GEO effects use this node |
| Earth ZOI | 4 | Boundary of Earth's influence |
| Moon Orbit | 5 | |
| Sub-Orbital Moon | 6 | |
| Moon (surface) | 7 | |
| Sun Orbit | 5 | On the Mars branch; same distance as Moon Orbit |
| Transfer Window | 5 + TW | TW = 0–5 per round |
| Mars ZOI | 6 + TW | |
| Mars High Orbit | 7 + TW | |
| Mars Low Orbit | 8 + TW | |
| Sub-Orbital Mars | 9 + TW | |
| Mars Surface | 10 + TW | |

---

## 4. Core Resources

### 4.1 Resources (Abstracted)

| Resource             | Meaning                                  |
| -------------------- | ---------------------------------------- |
| **Credits**          | Operating budget from contracts and hardware effects |
| **Thrust**           | Launch capability; must be ≥ total Mass  |
| **Range**            | Abstract delta-v budget for orbital travel |
| **Mass**             | Numeric weight of tanks (1–4) and payloads (1–3). Combined with Thrust, it determines whether a craft can launch from a surface. |
| **Energy**           | Electricity used by active systems. Each craft refills its Energy to the total of its power sources (Solar, RTG) each round; unused Energy does not carry over. A single-use Battery can be discarded for a one-time burst. |
| **Reliability**      | Engine hardware failure risk             |

> Design choice: Resources are **mostly embedded in cards**, not tracked as loose tokens, reducing bookkeeping.

> Physics note: the prototype does **not** simulate the full rocket equation. `Thrust` is a simplified launch-capability gate for leaving a surface, while `Range` is the game's abstract delta-v budget once a craft is in flight.

Tracking note:

* **Credits** are tracked openly on a personal player track.
* **VP** are tracked openly on the shared board and updated immediately when earned.
* **Energy** is tracked with tokens on each in-flight craft. It refills to the craft's power output each round and is not stored between rounds.
* **Completing missions is the main source of Credits**. Other sources should stay smaller or situational.

---

## 5. Setup

1. Each player chooses a color and receives:

   * **Sterling Booster** (E02) – Starting Engine (`Basic`)
   * **Standard Tank** (T01) – Starting Fuel Tank (`Basic`)
   * **Heat Shield** (S01) – Starting Support (`Basic`)
   * Credits by turn order: first player 5, second 6, third 7, fourth 8 (offsets first-player advantage)
   * 1 Credit marker and 1 VP marker
   * 1 Agency Level marker set to **Level 1**
   * 6 craft markers for rockets and in-space assets

   Cards with the `Basic` tag are always available to any player at their printed cost, even if none are in the market. A player may buy a Basic card as an Acquire Card action at any time. Besides the three starting components, the following are also `Basic` and always purchasable:

   * **Basic Battery** (S15) — a single-use power cell that bursts **1 Energy** anywhere (even in atmosphere or at launch) but carries **Mass 1**, the budget alternative to the lighter Battery Pack.
   * **Light / Standard / Heavy Payload** (P12 / P13 / P14) — plain payloads with no special ability, **Mass 1 / 2 / 3** and cost **1 / 1 / 2 Credits**. They exist so a payload is *always* available: even with a bad market and no payload drawn, any agency can buy one and fly a real payload mission. The light/heavy split is a small Mass-vs-cost choice (a heavier payload satisfies "Mass 2+"/"Mass 3" missions but eats more Thrust budget). They carry no `Satellite` tag, so they cannot be deployed as assets — they are cargo, not instruments.

2. Shuffle each deck separately.

   * Separate Mission cards by **Tier 1**, **Tier 2**, and **Tier 3** before shuffling.
   * Shuffle only the **Tier 1 Mission** stack at setup.
   * Keep Tier 2 and Tier 3 Missions face-down beside the board until they unlock.
   * **Scale the Component Deck to the player count:** 4 players use all cards (~155); 3 players remove 1 copy of every card with 3+ copies (~123); 2 players remove 1 copy of every card plus a 2nd copy of every card with 5+ copies (~99). Removed copies go back in the box unseen. (Players cycle ~16 drawn cards each plus market churn; this keeps ~60% of the deck flowing per game at any count, so combo pieces like the Station Hub stay findable.)
   * Shuffle the remaining component cards (Engines, Tanks, Payloads, Support, Technology) into one **Component Deck**.
   * Shuffle the **Event Deck** separately. Missions and Events are always used in full.

3. Reveal:

   * 1 **Starter Event** — draw one of the three Starter Event cards and reveal it face-up.
     It is round 1's Event, known to every player before the game begins; the regular Event
     deck is used from round 2 on. (See §7.1 *Starter Events*.)
   * 3 Tier 1 Mission cards in the Mission display. Ensure at least one **easy** Tier 1 mission
     (a payload-only or deploy mission such as *LEO Deployment*) is among the three, so round 1
     is never a dead hand — if none appears, set one of the three aside and draw replacements
     until an easy one appears, then shuffle the set-aside cards back into the Mission deck.
   * 7 Component cards in the **Card Market** (a wider offer means a useful part is reliably on
     display).

4. **Hand size limit:** Each player may hold at most **5 cards** in hand. If you ever exceed 5, immediately discard down to 5.

5. Determine first player randomly.

---

## 6. Game Structure

The game proceeds over a series of **Rounds**.

### 6.1 Round Phases

1. **Planning Phase**
2. **Action Phase**
3. **Maintenance Phase**

Launches and in-flight activations resolve **immediately** during the Action Phase. There is no separate resolution phase.

The game ends after a fixed number of rounds (**8**). If the Mission deck is depleted, the display simply is not refilled — the game still plays its full round count.

---

## 7. Phase Details

### 7.1 Planning Phase

1. **Reveal Event:** Flip the top card of the Event Deck. Its effect applies for the entire round. If the Event Deck is empty, skip this step. *(Round 1 skips the flip: it uses the Starter Event already revealed during setup.)*
2. **Advance Transfer Window:** Move the TW marker one step along its printed cycle: **3 → 2 → 1 → 0 → 1 → 2 → 3 → 4**, repeating. The full cycle is visible on the board so windows can be planned in advance. The marker starts on the first space at setup and **the advance is skipped on round 1**, so rounds 1–8 play the eight printed values in order.
3. **Draw Cards:** Each player draws **2 cards** from the Component Deck into their hand (hand limit is **5**).
4. **Emergency Sell:** Each player may discard up to 2 cards from hand to gain 1 Credit each. (This is the only way to sell cards — there is no Sell action.)

> Design intent: The Event reveal creates round-to-round variety and timing pressure. Drawing 2 cards keeps hands flowing without flooding.

#### Starter Events (Round 1)

Round 1's Event is never drawn from the Event deck. Instead, the game includes three dedicated **Starter Event** cards; one is drawn and revealed **during setup**, so every player plans their opening with full information. All three open the program with a boost — round 1 is never wrecked by bad space weather:

| Starter Event | Effect (round 1 only) |
| --- | --- |
| **Recovery Trials** | Any craft that lands safely on Earth this round returns **all its unstaged parts** to hand, Reusable or not (single-use landing devices actually expended are still discarded). |
| **Founding Grant** | Each player immediately gains **3 Credits**. |
| **Crash Program** | Each player has **1 additional Command Turn** this round. |

Discard the Starter Event during round 1's Maintenance as usual. The unused Starter Events return to the box.

> Design intent: the opening round should always feel like progress, but not the *same* progress every game — a hardware start, a funding start, or a tempo start each push round 1 in a different direction. Revealing the card at setup (instead of at the start of round 1) removes round-1 randomness from planning.

---

### 7.2 Action Phase

Each player has a number of **Command Turns** determined by their **Agency Level** (Level 1 = 2, Level 2 = 3, Level 3 = 4).

Starting with the first player, players alternate taking **one Command Turn at a time** until everyone has used all of their Command Turns for the round.

Each individual craft may be activated at most **once per Action Phase**.

At the **start of the Action Phase**, refill each in-flight craft's Energy to its **Power** — the total Energy output of its attached power sources (Solar Panel, RTG). Remove any Energy left from last round first, then place tokens equal to Power. Unused Energy does **not** accumulate between rounds.

#### Available Actions

* **Acquire Card** – Buy one face-up card from the **Card Market** (or any `Basic` card) by paying its Credit cost; add it to your hand. Immediately refill the empty market slot from the Component Deck.
* **Flush the Market** *(free action)* – Once per Command Turn, before or instead of browsing a stale market, pay **2 Credits** to discard all seven face-up Card Market cards and reveal seven new ones from the Component Deck. This does **not** consume the Command Turn, so you may flush and then Acquire one of the new cards in the same turn.
* **Develop Technology** – Pay the Technology card's Credit cost and place it face-up in your **Agency Tableau**. It applies to **all your craft** from now on. Technology cards remain in your tableau permanently unless another card effect removes them. A player may not have two developed Technology cards with the same **Name**.
* **Engineering** – Attach Engine, Fuel, Payload, and optional Support cards to a rocket in your Rocket Assembly Area, and/or replace components on it. You may also attach **one card sideways** as jury-rigged hardware (see §9 *Jury-Rigging*).
* **Launch New Craft** – Place an assembled rocket at `Earth`, perform the launch capability check, optionally Stage, then fly it along the orbital map spending Range. Resolve the mission immediately if the craft reaches its destination. May be combined with Engineering into a single Command Turn if all components are in hand. (See §7.3 Launch Resolution.)
* **Activate Craft** – Choose one of your in-flight craft and **move** it (spend 1 Range per node crossed), **operate** it (spend Energy to trigger an ability printed on an attached card), or both. A craft with **0 remaining Range** can still be activated to operate in place — it simply cannot move. May resolve a mission if it reaches the destination. *(Routine persistent-asset income is collected for free during Maintenance instead — see §7.4 Asset Operations — so you rarely need to spend a Command Turn just to bank it.)*
* **Expand Agency** – Increase your Agency Level by paying Credits

> Action economy is intentionally tight: you get only a few command turns, and larger agencies can coordinate more craft each round.

### Agency Levels

* **Level 1** – 2 Command Turns each Action Phase
* **Level 2** – 3 Command Turns each Action Phase, costs **6 Credits**
* **Level 3** – 4 Command Turns each Action Phase, costs **14 Credits**

New Agency Levels take effect at the start of your next round.

#### Mission Tier Unlocks

* The first time any player reaches **Agency Level 2**, shuffle all **Tier 2 Missions** into the Mission deck.
* The first time any player reaches **Agency Level 3**, shuffle all **Tier 3 Missions** into the Mission deck.
* The first player to reach **Agency Level 3** gains **+2 VP**.
* Each tier unlock happens only once per game.
* When a new Mission Tier is added, immediately resolve a **Government Catch-Up Grant**.
* Tier intent: **Tier 1** teaches Earth return and LEO jobs, **Tier 2** adds asset-management and lunar missions, and **Tier 3** adds long-range crewed or return-heavy prestige missions.

#### Government Catch-Up Grant

* When **Tier 2** unlocks, the player with the **lowest VP** gains **3 Credits**.
* When **Tier 3** unlocks, the player with the **lowest VP** gains **4 Credits**.
* If multiple players are tied for lowest VP, the tied player with the **fewest Credits** gains the grant.
* If there is still a tie, each tied player gains **2 Credits** instead.

#### Exploration Bonuses

Exploring is rewarding on its own, so early flights feel like progress even before a
mission pays out. Two layers stack:

**Personal orbit floor (always, once per agency):**

* The **first time your agency reaches `LEO`**, gain **+1 Credit**. Getting to orbit at all
  always pays a little — a reliable early funding source independent of missions.

**Exploration Race ladder (global, diminishing by arrival order):** the **1st / 2nd / 3rd / 4th**
agency to *first* reach each frontier earns a scaled reward. Near-Earth frontiers pay Credits
(funding); deep-space frontiers pay Victory Points (glory).

| Frontier reached           | 1st           | 2nd    | 3rd    | 4th |
| -------------------------- | ------------- | ------ | ------ | --- |
| `High Orbit (GEO)`         | +2 Credits    | +1 Cr  | +1 Cr  | —   |
| `Earth ZOI`                | +1 VP +1 Cr   | +1 Cr  | —      | —   |
| Moon branch (`Moon Orbit`+)| +2 VP         | +1 VP  | —      | —   |
| Mars branch (`Mars ZOI`+)  | +4 VP         | +2 VP  | +1 VP  | —   |

* Each rung is awarded once. An agency scores a given frontier only the first time it arrives.
* The **first-to-Moon (+2 VP)** and **first-to-Mars (+4 VP)** milestones are simply the top rung
  of this ladder; trailing agencies now get a consolation instead of nothing, which keeps the
  space race tense for everyone.

#### Technology Milestone Bonuses

* Each player gains **+1 VP** the first time they develop their **second Technology**.
* The first player to develop their **fourth Technology** gains **+2 VP**.
* If you develop a Technology while you control an `On-Orbit` `Satellite` or `Station`, gain **+1 VP** (max once per round).

---

### 7.3 Launch Resolution

When a craft is launched or activated, resolve the flight immediately:

1. **Launch Capability Check:** Verify total Engine Thrust (all Engines combined) ≥ Total Rocket Mass (sum of all Fuel Tank Mass values + the Mass of every Payload). In design terms, this answers whether the stack can leave the surface; `Range` handles orbital travel after liftoff.
2. **Reliability Check:** Roll a d10. If the result is **≤ the craft's Reliability value** (the Engine's printed Reliability after modifiers from Technology cards and Events; for a two-engine cluster, the lowest engine's modified value **−1**), the launch succeeds. If the roll is **above** Reliability, the launch **fails** — the craft does not move, and any non-Reusable Engine is discarded. Reusable Engines survive a failed check but the craft still does not launch this action. **Flight Data:** whenever one of your launches fails its Reliability check, gain **1 Credit** — even a fireball returns telemetry, so a failed test still moves the program forward. *(Skip this step when activating a craft already in flight. A Rocket-as-Lander relaunching from a surface must pass a new Reliability Check.)*
3. Optionally **Stage** one card with the `Stageable` tag to gain its printed bonus Range for this launch (at most one card pre-flight).
4. The player chooses a path on the orbital map. The craft moves along this path, spending **1 Range per node** crossed. The player may **stop at any node**, preserving unspent Range for future activations.
5. **Mid-Flight Staging:** During movement, a player may stage `Stageable` cards (typically empty Fuel Tanks) to gain their stage bonus Range — each card may be staged only once, and at most one card per node crossing. The staged card is discarded. This also reduces the craft's Mass for future relaunch capability checks.
6. Track the craft's **remaining Range** with a token or small die beside its marker on the board.
7. If a card on the craft says to **Spend Energy**, remove that many Energy tokens from the craft's current Energy when the effect is used. If the craft is short, you may **discard a Battery** attached to it to add its printed burst of Energy (a Battery works anywhere, even in atmosphere or at launch).
8. If the craft reaches the mission route's required destination, check **Mission Requirements** (Mass, tags, special conditions).
9. Apply special effects.
10. Score VP and rewards.

#### Deploying Persistent Assets

At any point while a craft is **in space** (at any orbital node — not on a surface and not at the `Earth` node), the controlling player may **deploy** one `Satellite` or `Station` payload carried by that craft:

* The payload separates and becomes its **own craft** at the current node, taking one of your craft markers.
* Any Support cards you assign to it (typically a `Power` card such as a Solar Panel or RTG to run it, plus its own equipment) stay attached and remain with the deployed asset.
* The **rest of the rocket keeps its remaining Range** and may continue its flight in the same action — this is how a craft can drop a satellite and still fly home.
* A deployed asset normally has **0 Range** of its own (no Fuel Tank), so it stays parked where it was deployed. It still counts as one of your craft and may be activated at most once per Action Phase.
* Deploying is **free** and is part of the launch or activation already underway; it does not cost an extra Command Turn.

A payload that is never deployed simply returns or is discarded with its craft as normal. A Comm Satellite, Imaging Probe, Station Hub, or Microgravity Lab must be **deployed** before it can produce its ongoing income (see §7.4 *Asset Operations*).

#### Energy Timing

* **Energy refills, it does not accumulate.** At the start of each Action Phase a craft's Energy resets to its **Power** (the total output of its Solar Panels and RTGs). Whatever you don't spend this round is gone next round — there is no stored-vs-generated distinction and no end-of-round Energy cleanup.
* When a card says to **Spend X Energy**, remove that many tokens from the craft's current Energy.
* **Batteries are single-use bursts, not storage.** At any time you may discard a Battery attached to a craft to immediately add its printed Energy. A Battery works **anywhere**, including in atmosphere and during launch or relaunch — handy when Solar Panels are unavailable or the craft carries no generator.
* **Persistent-asset income** abilities (a Comm Satellite, Imaging Probe, Station Hub, or Microgravity Lab) spend the asset's Energy during the **Maintenance Phase** *Asset Operations* step — no Command Turn and no movement, only that the asset still has Energy this round. Because Energy refills every round, just keep a `Power` card on the asset and the income pays out each round.
* **Conditional activation income:** some `Scientific` and `Electronics` cards (Sensor Array, Science Module, Space Telescope, Comm Satellite relay) pay VP or Credits when activated under the right conditions — a storm Event in play, or a deep-space position. Card text is authoritative; this is how bad space weather becomes an opportunity for a prepared agency.

#### Failure

If requirements are not met:

* Rocket fails
* Components may be discarded or damaged

#### Recovery

* If a craft returns to the `Earth` node, its **unstaged Reusable parts** return to your hand during Maintenance.
* If a part was **staged**, it is lost and does not return.
* Non-Reusable parts do not return to your hand unless another card effect says they do.
* Reusable recovery parts should usually cost about **1 Credit more** than their disposable versions because they trade higher setup cost for better long-term efficiency.
* If a craft remains in space, its parts stay with that craft and do not return to your hand.
* A craft with **0 remaining Range** cannot move but remains on the board as a persistent asset (if applicable).

> Design choice: Failures are costly but not game‑ending.

---

### 7.4 Maintenance Phase

* Reusable parts from craft that returned to `Earth` return to hand if they were not staged
* Ongoing technology effects trigger
* **Asset Operations:** Each persistent `Satellite` or `Station` you control may trigger each of its "spend Energy" income abilities **once**, spending the asset's remaining Energy this round. Collect the Credits or VP. This is free and costs no Command Turn. **Income scales with distance:** Satellite assets pay 1 Credit at `Earth ZOI` or closer, 1 VP beyond `Earth ZOI`; Station cards pay as printed. (Energy simply refills at the start of the next Action Phase — there is no separate Energy cleanup.)
* Discard the current round's Event card, unless its text says it persists (e.g., *Stranded Crew* stays in play until claimed)
* **Mission Sweep:** if no mission was completed this round, discard the mission that has been in the display longest
* Refill the Mission display to 3 cards if needed (place new missions to the right, so the oldest is always leftmost)
* Refill the **Card Market** to 7 cards from the Component Deck

---

## 8. Missions

Mission cards define:

* **Destination** (LEO, Moon, Mars, Deep Space)
* **Mission Type** (`Public` in the current prototype)
* **Tier** (1, 2, or 3)
* **Route** (the orbital path or landing path to complete)
* **Requirements** (Range, Mass, Tags)
* **Rewards** (VP, Credits)

In this prototype, **Mission cards are the game's contract system**. Accepting a mission represents taking a public job offer from a government, commercial client, or scientific program.

Mission economy note:

* Completing missions is the main source of both Credits and VP, but the reward split should force tradeoffs.
* **Commercial** missions should be **Credits-heavy** (Credits > VP) to fund acquisition and agency expansion.
* **Prestige** missions should be **VP-heavy** (VP > Credits) to reward long-range exploration and scientific risk.
* **Infrastructure** missions should sit between the two, often enabling later station, docking, or satellite rewards.
* Harder or longer missions should usually pay more total rewards, but not always more of both currencies.
* Event-based Credits should remain occasional bonuses, not the default economy.

### Mission Market

* In the current prototype, **all missions are Public Missions**.
* Public Missions stay in the market after players commit to them, allowing multiple players to race to complete the same mission.
* Contract class should be communicated through tags such as `Commercial`, `Prestige`, or `Infrastructure`, not through a second ownership layer.

### Mission Market Rules

* **Tier 1 Missions** are available from the start of the game.
* **Tier 2 Missions** enter the Mission deck when the first agency reaches Level 2.
* **Tier 3 Missions** enter the Mission deck when the first agency reaches Level 3.
* Public Missions leave the market only when completed.
* When a mission is completed, score its rewards immediately, then discard it.
* A completed mission is resolved as soon as the craft reaches the required destination and satisfies all printed requirements.
* Empty slots in the public Mission display are refilled during Maintenance.
* Mission cards should display their Mission Type, Tier, and contract-class tags clearly on the card face.
* Mission cards should show a short **Conditions list** (route + requirements) in rules text.
* Mission reward badges should show **VP** and **Credits** only; route Range remains a requirement, not a reward stat.

#### Standing Contracts

* Some missions are `Basic` **standing contracts**: like Basic components, they are **always
  available** and never sit in the Mission display or deck. Each agency may complete each
  standing contract **once per game**.
* **Suborbital Test Flight** (M21) is the first standing contract: fly `Earth → Sub-Orbital
  Earth → Earth` and land safely (parachute or propulsive) — **no payload required** — for
  **2 Credits + 1 VP**. It guarantees every agency a productive first turn using only the
  starting kit, and teaches the launch-and-land loop.
* **Reward hierarchy:** simply lifting off (a no-payload standing contract) is the *floor* and
  pays the least. Carrying a payload is the real job and always pays more — e.g. *LEO
  Deployment* (payload → LEO) pays 5 Credits + 2 VP versus the test flight's 2 + 1. Longer and
  harder missions pay more still.

### Mission Design Philosophy

* Early missions are forgiving
* Late missions reward specialization
* Recovery-focused missions should reward safe returns and reusable hardware
* Tier 1 should stay readable at a glance: low Range, few prerequisites, and short routes
* Tier 2 should introduce combo requirements such as satellites in orbit or lunar hardware
* Tier 3 should be reserved for deep-space distance, crewed coordination, or full return missions
* Tier 3 science missions require a **Mass 2+ Scientific payload** — this gives the heavy science hardware (Science Module, Rover, Space Telescope) an exclusive job the light Imaging Probe cannot poach
* Missions encourage **different strategies**, not linear progression
* Commercial missions should keep the economy moving; prestige missions should tempt players to delay cashflow for headlines

---

## 9. Rocket Design System

A rocket consists of:

* **0–2 Engines** (two engines form a **cluster** — see *Engine Clusters* below)
* **1+ Fuel Tanks** (no fixed cap — Thrust is the real limit, since every tank's Mass must be lifted)
* **0–2 Payloads** — rideshare launches are allowed: a satellite plus the mission payload, or a lander plus a return capsule, can share one rocket; every payload's Mass counts toward Total Rocket Mass
* **0–3 Support Cards**

A rocket's **total Range** equals the sum of all its Fuel Tank Range values, **minus any Deadweight penalties** printed on attached cards (see *Deadweight* below). Some rockets may also use **staging** effects printed on cards to discard part of the rocket mid-flight for extra Range.

A rocket launched from a planet must have an Engine.
An Engine-free craft is only legal if it is already **in flight** or **in orbit** because of a mission, card, or ongoing asset effect.

### Engine Clusters

* A rocket may mount **up to 2 Engines**; their **Thrust adds**.
* A two-engine cluster's **Reliability** is the **lowest** value among its engines (after per-engine modifiers) **minus 1** — more engines means more plumbing and more ways to fail. Roll one Reliability check per launch as usual.
* Any single remaining Engine is enough to maneuver, land propulsively, and relaunch. Staging away a `Stageable` engine (Kick Stage) leaves the other engine flying the craft — the classic booster + sustainer stack.

> Design intent: clustering cheap engines is the budget route to heavy lift (two Sterling Boosters: Thrust 10, Reliability 8) — Kerbal "moar boosters" — while premium engines buy similar lift in one reliable, often Reusable card.

### Qualification Rules

* Every Fuel Tank and Payload card has a numeric **Mass** value (tanks 1–4, payloads 1–3).
* Some Support cards also have printed **Mass**.
* **Total Rocket Mass** = sum of all Fuel Tank Mass values + the Mass of every Payload + any printed Support Mass.
* The rocket's **total Thrust** (all Engines combined) must be **≥ Total Rocket Mass** for the rocket to launch.
* Mission requirements that name a payload Mass (e.g., "payload Mass 2+") refer to a **single payload card** of that Mass — two Mass-1 payloads do not add up to satisfy them.
* If your rocket has **no Engine**, it may not launch from a planet.
* If your craft has **no Engine** but is already in flight or in orbit, its Total Rocket Mass must be **≤ 3**.
* Engines have no Mass for lift purposes. Support cards count only if they print a Mass value.
* **Range** measures remaining travel potential. A rocket's total Range = sum of all Fuel Tank Range values, minus attached Deadweight penalties.
* **Energy** powers activated systems such as docking hardware, advanced sensors, and computer assists. A craft refills Energy to its power output at the start of each Action Phase and spends it during that round; a Battery may be discarded for a one-time burst.
* To **maneuver**, a craft must have an Engine to turn that Range into orbital changes.
* Missions with the `Docking` tag require a `Docking`-tagged support card on the rocket (e.g., Docking Adapter or Orbital Tug).
* Missions with the `Docking` or `Maneuver` tags require a rocket with an Engine.
* Missions with the `In-Space` tag may be attempted by legal Engine-free craft that are already in space.
* Missions requiring a `Crewed` payload: the craft must also include a `Pressurized` tank.
* A rocket must satisfy both the Thrust/Mass check and the Range check before it can attempt a mission.
* If a rocket has no payload, only Fuel Tank Mass counts toward Total Rocket Mass.
* **Uncrewed** means any payload *without* the `Crewed` tag.
* Card text is authoritative. When a card gives a more specific instruction than the general rules, resolve that card effect as written.

### Deadweight (Range Penalties)

Some heavy non-tank cards print a **Deadweight** value (shown as **Range −1**): hauling them costs delta-v. This is the game's light-touch rocket equation — mass that isn't fuel reduces how far the fuel takes you.

* When a craft launches, subtract the **sum of all attached Deadweight values** from its total Range.
* If a Deadweight card **permanently leaves a craft in flight** — deployed as an asset, staged, or discarded — the craft immediately **regains** that much Range. Dropping the heavy cargo before the trip home is exactly the right move.
* **Fuel Tanks never carry Deadweight:** a tank's printed Range is already net of its own weight.
* Most cards have **no** Deadweight — light parts fly free. Current guideline: **Mass 3+ non-tank cards print Range −1**; a future Mass 5+ monster part would print −2.
* Cards with Deadweight in the current set: **Science Module (P03)**, **Heavy Payload (P14)**, and **Fuel Depot (P11)** — each **Range −1**.

> Design intent: the penalty is deliberately **not 1:1 with Mass**. Thrust already taxes total weight at the launch gate; Deadweight only bites the few genuinely heavy items, so starter rockets and light probes keep their full printed Range and route math stays a simple sum of printed numbers.

### Jury-Rigging (Sideways Cards)

Any card in hand can be strapped onto a rocket as improvised hardware instead of being played normally. During an **Engineering** (or combined Engineering + Launch) action, you may attach **at most one card sideways** to a rocket. A sideways card ignores its printed text, tags, Cost, and Mass — what it does depends only on its card type:

| Sideways card | Acts as | Effect |
| --- | --- | --- |
| **Engine** | Strap-on booster | **+1 Thrust** on this craft's launch and relaunch capability checks |
| **Fuel Tank** | Drop tank | **+1 Range** added to the craft's Range when it launches |
| **Any other card** | Mass simulator | A plain payload: **Mass 1**, no tags (counts as Uncrewed) |

* A jury-rigged booster or drop tank does **not** occupy an engine/tank/support slot; the mass simulator **does** occupy one of the rocket's two payload slots.
* A jury-rigged card is never a real component: it can never be **staged**, **recovered**, or targeted by card effects. Discard it when its craft is discarded or returns to Earth.
* Limit: **one sideways card per rocket**.

> Design intent: every dead card in hand is one strut away from being useful — the KSP "add more boosters" fantasy, and a reason turns feel different even with an awkward hand. The exchange rate is fair by construction: a hand card is worth ~1 Credit via Emergency Sell, and each sideways effect is deliberately weaker than the 1-Credit purchase it imitates (Fuel Pod, Basic Battery, Light Payload).

### Orbital Node Travel

* The game treats **Range** as **delta-v** in a simple form.
* Travel happens on a graph of **nodes** connected by lines.
* Each line crossed costs **1 Range**, except the **Transfer Window** crossing which costs the current **TW value** (0–5).
* When launched or activated, a craft may move **any number of nodes** up to its remaining Range in a single action.
* A player may voluntarily **stop at any node**, preserving unspent Range for future activations.
* Track each craft’s **remaining Range** with a token or small die beside its marker on the board.
* A craft with 0 remaining Range cannot move but stays on the board as a persistent asset (if applicable).
* Return trips are allowed. Count the steps for the way back too. The Transfer Window cost applies each time the craft crosses it.
* Mission cards that cross the Transfer Window show route Range at TW 0. Players must add the current TW cost when planning.

### Landing Rules

* To land on a body, a craft must be at the adjacent **Sub-Orbital** node and spend **1 Range** to cross to the surface, **using a landing method**.
* **A heat shield does not land you.** A `Reentry` heat shield (Heat Shield, Ceramic Tile Shield) protects the craft through reentry *heat* and can aerobrake, but it does **not** slow the craft for touchdown. To land you need one of the **landing methods** below.
* **Landing methods:**
  * **Parachute** (`Parachute` cards — Recovery Chutes, Guided Parafoil, Splashdown Kit): **Earth only** (needs thick atmosphere). Discard after use unless Reusable; some pay a +1 Credit recovery bonus.
  * **Airbags** (`Airbag` card — Airbag Shell): a cushioned bounce landing on **Earth or Mars**, **Uncrewed craft only** (a crew cannot survive the impact). Single-use.
  * **Lander** (a `Lander` payload such as the Landing Lander): sets the craft down on any surface it can reach.
  * **Propulsive** (Engine + **1 extra Range**; Landing Legs waive the extra Range): available on any body.
* **Earth landing** (Sub-Orbital Earth → Earth): parachute, airbags (uncrewed), a Lander, or propulsive. Crews must use a parachute or a propulsive landing.
* **Moon landing** (Sub-Orbital Moon → Moon): the Moon has no atmosphere, so landing is always **propulsive** — an Engine is required. A dedicated Landing Lander **or** the rocket itself may serve as the lander (see Rocket-as-Lander below).
* **Mars landing** (Sub-Orbital Mars → Mars Surface): Mars air is too thin for parachutes. Use **airbags** (uncrewed), a **Lander**, or a **propulsive** landing. A heat shield may still be used to aerobrake on the way down.
* Each landing uses its own method. A Moon return trip needs a propulsive lunar landing plus an Earth landing method (parachute/propulsive) for the trip home.
* **Sub-orbital arcs decay — touch down by the end of the round.** A Sub-Orbital node is a ballistic arc, not a stable orbit. Any craft still at `Sub-Orbital Earth`, `Sub-Orbital Moon`, or `Sub-Orbital Mars` when the Action Phase ends comes down on the body below during Maintenance:
  * With a **passive lander** aboard — a parachute (Earth only), airbags (uncrewed, Earth/Mars), a Lander payload, or **Landing Legs + Engine** — the craft touches down **safely and automatically**: no command turn and no Range spent. Normal landing effects apply (single-use devices are expended, recovery credits pay out).
  * On the airless Moon the automatic touchdown is always propulsive: it needs an **Engine** plus Landing Legs or a Lander to set down on.
  * Without a passive lander, the player must spend one of their **command turns** during the round to land propulsively (or climb to a stable orbit) — otherwise the craft **crashes and is destroyed**.
  * Because sub-orbital arcs decay, persistent assets (Satellites/Stations) may **not** be deployed at Sub-Orbital nodes.
* **Aerobraking:** while moving *toward* a body with atmosphere (descending on the Earth or Mars branch), a craft may discard a `Reentry` heat-shield card to immediately gain **+2 Range**. The card is spent and, being a heat shield, was never a landing method anyway.
* **Atmosphere nodes:** `Earth`, `Sub-Orbital Earth`, `Sub-Orbital Mars`, and `Mars Surface`. The Moon branch has no atmosphere. Cards that react to "entering atmosphere" (e.g., Solar Panel) trigger when a craft moves onto any atmosphere node.

### Rocket-as-Lander

A rocket does **not** need a separate Landing Lander payload to land. If the rocket has:

1. An **Engine** (for propulsive landing capability), and
2. Enough **remaining Range** to land and later relaunch,

then it may land on the surface directly. To **relaunch from a surface**, the craft must pass a new Launch Capability Check (Thrust ≥ current Total Mass, accounting for any cards staged away) and a new **Reliability Check**. It then continues flight using its remaining Range.

> Design intent: This makes surface missions possible without a Landing Lander, but more expensive in fuel. Dedicated landers are still valuable because they free up Range for the rest of the trip. Players can stage away empty tanks before relaunching to reduce Mass.

### Persistent Assets

* Payloads with the `Satellite` or `Station` tags become persistent assets when **deployed** in space (see §7.3 *Deploying Persistent Assets*). A payload may also be left behind automatically if its whole craft strands at a node with 0 Range.
* A persistent asset keeps any Support cards assigned to it (for example a Solar Panel or RTG that powers it) unless they were staged or discarded.
* These assets use your craft markers and may be activated like any other craft. To **move** one it must first gain Range (e.g., from an Orbital Tug); to **operate** one in place, use the Activate Craft action or the Maintenance *Asset Operations* step.
* Many assets carry an **ongoing income** ability ("spend 1 Energy for income"). Pair the asset with a `Power` card so it has Energy each round, then collect that income for **free every Maintenance** during Asset Operations — you do not spend a Command Turn to bank it.
* **Income scales with distance:** a `Satellite` asset's income pays **1 Credit** at `Earth ZOI` or closer and **1 VP** beyond `Earth ZOI`. Near-Earth infrastructure funds the agency; deep-space infrastructure earns glory. `Station` cards (Station Hub, Microgravity Lab) pay as printed — their gate is the expensive station chain itself.
* Satellites and stations each count as one of your craft and may be activated at most once per Action Phase.

### On-Orbit Stations

* `High Orbit (GEO)` is the station node on the Earth branch.
* A craft parked there may be designated an `On-Orbit Station` if it includes a `Station Hub` payload, at least one attached `Power` card, at least one attached `LifeSupport` card, and at least one additional `Scientific` or `Electronics` card.
* This is the same condition shown on the `Station Hub` card and the supporting station cards, so station setup feels like a satisfying payoff rather than a bookkeeping puzzle.
* Once designated, the station remains parked in `High Orbit (GEO)` until an effect moves it.
* Docking missions and station-related Events target any such On-Orbit Station.

### Staging Rules

* Some cards have the `Stageable` tag and a printed **Stage** effect.
* **Pre-flight staging:** During Launch, after the launch capability check and before movement, you may Stage **one** `Stageable` card. If the staged card is an Engine, it still counts for the launch capability check.
* **Mid-flight staging:** During movement, a player may stage `Stageable` cards (typically empty Fuel Tanks) to gain their stage bonus Range — each card may be staged only once, and at most one card per node crossing. This also reduces the craft's current Mass, which matters for relaunch capability checks.
* When you Stage a card, gain the printed bonus Range for that flight.
* Staging reduces the craft's current **Mass** (important for relaunch capability checks) and increases how much Range your rocket can reach.
* Fuel use is abstracted into the card values. The printed Stage bonus already represents the efficiency gained by dropping spent parts.
* A staged card is discarded after launch and cannot be recovered unless another effect returns it.
* Once a card is staged, you lose any future benefit from that card, including reusability or passive effects.

Design note:

* These thresholds are intentionally simple so launch qualification stays readable at a glance rather than becoming a math exercise.
* Stageable Engines are the game's simple version of the rocket equation: the card represents a booster plus sustainer, so it helps you lift the rocket early, then turns into extra Range once the spent booster part is dropped.
* A craft already in orbit does not need an Engine just to remain there. It needs one only if it must maneuver again.
* Range is not a separate fuel mini-game. Players count node moves plus Transfer Window costs and propulsive landings.
* Flights resolve immediately when launched or activated. Players choose how far to fly, and remaining Range is tracked on the board.
* Multiple tanks let players build heavier rockets for longer missions, but each tank adds Mass that the Engines must lift.

### Tags

Cards use **tags** instead of keywords. Every tag has a rules meaning; flavor lives in card names and flavor text.

Component tags:

* `Basic` — always purchasable at printed cost
* `Reusable` — returns to hand on Earth return (no tag = discarded after use)
* `Stageable` — jettison for printed bonus Range
* `Cryogenic` — Hydrogen Core pairing; Cryo Handling tech
* `Pressurized` — crew-rated tank; required for Crewed payloads
* `Crewed` — carries crew (Uncrewed = any payload without this tag)
* `LifeSupport` — station qualification
* `Electronics` — electronics requirements + station qualification
* `Scientific` — science requirements + station qualification
* `Power` — generates Energy
* `Reentry` — heat shield: survives reentry heat and can aerobrake, but is **not** a landing method
* `Parachute` — Earth-only landing device (parachutes, splashdown)
* `Airbag` — cushioned landing for Uncrewed craft on Earth or Mars
* `Lander` — enables surface landing
* `Docking` — docking hardware
* `Satellite` — persistent asset when deployed
* `Station` — station module

Mission tags: `Commercial`, `Prestige`, `Infrastructure` (contract class), `Surface`, `Docking`, `Maneuver`, `In-Space`, plus requirement tags (`Crewed`, `Scientific`).

Event tags: `Starter` — a Starter Event; one is revealed at setup as round 1's Event, and Starter cards are never shuffled into the Event deck.

> Tags allow flexible design space and future expansions.

---

## 10. Victory Points & End Game

VP sources:

* Missions (including the always-available Suborbital Test Flight standing contract)
* Technologies
* **Exploration Race ladder** — the 1st–4th agency to reach `Earth ZOI`, the Moon branch, or the Mars branch earns scaled VP (the old first-to-Moon/Mars milestones are its top rung)
* Agency milestones (first to Level 3)
* End‑game infrastructure bonus: **+1 VP per deployed persistent asset** (Satellite or Station) still on the board

### End Game Trigger (Current)

* After **8 rounds** (fixed). An empty Mission deck stops display refills but does not end the game.

Highest VP wins. Ties broken by:

1. Completed Missions
2. Remaining Credits

---

## 11. Card Design Guidelines (For Developers & Designers)

### 11.1 General Principles

* Every card must answer: **"What decision does this enable?"**
* Avoid pure numeric upgrades without trade‑offs
* Prefer synergy over raw power

### 11.2 Engine Cards

Design axes:

* Thrust vs Reliability
* Reusable vs Disposable
* Stable vs Experimental

Bad design:

> +2 Range, no drawback

Good design:

> +3 Range, discard after launch OR gain VP if reused

Stageable engine example:

> Thrust 7. Stage: +2 Range for this launch. Discard after launch.

---

### 11.3 Fuel Tanks

Fuel tanks should:

* Define mission reach via Range (summed across all tanks)
* Create meaningful Mass vs Range trade-offs (heavier tanks eat Thrust budget)
* Interact with engine type (e.g., Cryogenic requirements)
* Occasionally offer a simple staging decision

Design axes:

* **Range/Mass ratio** – Efficient tanks give more Range per Mass but may cost more Credits
* **Staging potential** – Stageable tanks trade reuse for bonus Range
* **Compatibility** – Specialized tanks (Cryo, Pressurized) unlock better engines or crewed missions
* **Stacking strategy** – Multiple small tanks vs one large tank shapes the Mass budget differently

---

### 11.4 Payloads

Payloads are **scoring levers**:

* Satellites → long‑term VP
* Probes → burst scoring
* Crew → high risk, high reward

---

### 11.5 Technologies

Technologies should:

* Break rules slightly
* Enable new strategies
* Never be mandatory

---

## 12. Design Status & Known Open Questions

### Locked In

* Core turn structure (Planning → Action → Maintenance)
* Rocket assembly system (multi-tank, Mass-based Thrust check)
* Mission-driven scoring and economy
* Commercial / Prestige / Infrastructure mission taxonomy (tag-based)
* Mission reward split: Commercial skews Credits, Prestige skews VP, Infrastructure balanced
* Persistent asset economy loops with **distance-scaled income**: Satellite assets pay Credits near Earth and VP beyond Earth ZOI; Station Hub and Microgravity Lab pay as printed. Assets must be **deployed** in space and paired with a `Power` card; income is harvested for free during the Maintenance *Asset Operations* step (no Command Turn required)
* Card Market for component acquisition
* Event cards integrated into Planning Phase
* Technology tableau (permanent until removed)
* Reliability check on launch (d10 ≤ Reliability)
* Rocket-as-Lander and mid-flight staging
* Hand size limit of 5
* Transfer Window mechanic for Mars timing
* Launch Capability Check terminology (Thrust ≥ Mass gate; Range is delta-v budget)
* Assemble and Launch may be combined into one Command Turn if all components are in hand
* **Early-game dynamics (v0.4):** always-buyable Basic payloads (Light/Standard/Heavy) so a payload is never unavailable; a 7-card market; the Suborbital Test Flight standing contract; and the two-layer Exploration Bonuses (personal LEO Credit floor + the diminishing Exploration Race ladder)
* **Landing model:** a heat shield is heat/aerobrake only — landing requires a parachute (Earth), airbags (uncrewed, Earth/Mars), a Lander, or a propulsive burn
* **Sub-orbital decay:** sub-orbital arcs are not stable orbits — a craft still on one at round end auto-lands with a passive lander (parachute / airbags / Lander / Landing Legs + Engine) or crashes; a propulsive landing without legs costs a command turn during the round
* **v0.5 pacing pass** (see `docs/design_review_pacing.md`): **Starter Events** (round 1's event comes from a benign 3-card pool, revealed at setup); **Flight Data** (1 Credit whenever a launch fails its Reliability check); round-1 TW advance skipped so the printed 8-value cycle maps to rounds 1–8; relaxed stack limits (**Engine Clusters** 0–2 with Thrust adding and Reliability = lowest −1, **uncapped Fuel Tanks**, **0–2 rideshare Payloads** — which also makes Lander + Cargo Return Capsule builds like *Lunar Sample Return* legal); **Deadweight** Range penalties (−1) on Mass 3+ non-tank cards, regained when the card leaves the craft; and the **Jury-Rigging** sideways-card rule

### Under Evaluation

* Player asymmetry (agency specialization or starting bonuses)
* Whether second players completing a mission should get a reduced reward
* **Orbital congestion:** cap the number of income-paying `Satellite` assets per node (e.g., 3 slots each at LEO and GEO; first deployed keep them) so near-Earth real estate becomes a race that one player can lock other players out of — see `docs/design_review_pacing.md` §2
* **Jury-Rigging free-choice variant:** let the player pick any of the three sideways effects regardless of card type (simpler to abuse, but even easier to teach)
* Additional standing contracts at higher tiers (e.g., a once-per-agency "GEO comm license") so every agency always has a fallback job

### Playtest Readiness

* Card list v0.5 (91 unique / 192 with copies): 21 missions — 8 Tier 1 (incl. the Suborbital Test Flight standing contract) / 8 Tier 2 / 5 Tier 3 — 13 events plus the 3 Starter Events (EV14–EV16), the Basic payload set (P12–P14), the landing devices (Airbag Shell, Splashdown Kit), and Deadweight (Range −1) printed on the Science Module, Heavy Payload, and Fuel Depot
* Economy rebalanced — see `docs/playtest_notes.md` for balance targets
* First blind playtest scheduled

---

## 13. Closing Notes

This draft prioritizes:

* Clarity over completeness
* Strategy over randomness
* Extensibility for future expansions

> *If the game feels tight, constrained, and slightly stressful — it is working as intended.*

---

**End of Draft v0.5**
