# 🚀 SPACE AGENCY RACE

**Official Design & Ruleset Draft (v0.2)**

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
| Sun Orbit | 5 | Shared with Moon branch start |
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
   * 5 Credits
   * 1 Credit marker and 1 VP marker
   * 1 Agency Level marker set to **Level 1**
   * 6 craft markers for rockets and in-space assets

   Cards with the `Basic` tag are always available to any player at their printed cost, even if none are in the market. A player may buy a Basic card as an Acquire Card action at any time.

2. Shuffle each deck separately.

   * Separate Mission cards by **Tier 1**, **Tier 2**, and **Tier 3** before shuffling.
   * Shuffle only the **Tier 1 Mission** stack at setup.
   * Keep Tier 2 and Tier 3 Missions face-down beside the board until they unlock.
   * Shuffle all component cards (Engines, Tanks, Payloads, Support, Technology) into one **Component Deck**.
   * Shuffle the **Event Deck** separately.

3. Reveal:

   * 3 Tier 1 Mission cards in the Mission display
   * 5 Component cards in the **Card Market**

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

The game ends after a fixed number of rounds **or** when the Mission deck is depleted (final trigger TBD).

---

## 7. Phase Details

### 7.1 Planning Phase

1. **Reveal Event:** Flip the top card of the Event Deck. Its effect applies for the entire round. If the Event Deck is empty, skip this step.
2. **Advance Transfer Window:** Move the TW marker one step along its track.
3. **Draw Cards:** Each player draws **2 cards** from the Component Deck into their hand (hand limit is **5**).
4. **Emergency Sell:** Each player may discard 1 card from hand to gain 1 Credit.

> Design intent: The Event reveal creates round-to-round variety and timing pressure. Drawing 2 cards keeps hands flowing without flooding.

---

### 7.2 Action Phase

Each player has a number of **Command Turns** equal to their **Agency Level**.

Starting with the first player, players alternate taking **one Command Turn at a time** until everyone has used all of their Command Turns for the round.

Each individual craft may be activated at most **once per Action Phase**.

At the **start of the Action Phase**, refill each in-flight craft's Energy to its **Power** — the total Energy output of its attached power sources (Solar Panel, RTG). Remove any Energy left from last round first, then place tokens equal to Power. Unused Energy does **not** accumulate between rounds.

#### Available Actions

* **Acquire Card** – Buy one face-up card from the **Card Market** (or any `Basic` card) by paying its Credit cost; add it to your hand. Immediately refill the empty market slot from the Component Deck.
* **Sell Part** – On your turn, discard a card from your hand and gain Credits equal to half its cost, rounded down
* **Develop Technology** – Pay the Technology card's Credit cost and place it face-up in your **Agency Tableau**. It applies to **all your craft** from now on. Technology cards remain in your tableau permanently unless another card effect removes them. A player may not have two developed Technology cards with the same **Name**.
* **Assemble Rocket** – Attach Engine, Fuel, Payload, and optional Support cards
* **Upgrade Rocket** – Replace components
* **Accept Mission** – Commit to a Public mission in the display. In this prototype, all missions stay public and can be raced by multiple players.
* **Launch New Craft** – Place an assembled rocket at `Earth`, perform the launch capability check, optionally Stage, then fly it along the orbital map spending Range. Resolve the mission immediately if the craft reaches its destination. (See §7.3 Launch Resolution.)
* **Activate Craft** – Choose one of your in-flight craft and **move** it (spend 1 Range per node crossed), **operate** it (spend Energy to trigger an ability printed on an attached card), or both. A craft with **0 remaining Range** can still be activated to operate in place — it simply cannot move. May resolve a mission if it reaches the destination. *(Routine persistent-asset income is collected for free during Maintenance instead — see §7.4 Asset Operations — so you rarely need to spend a Command Turn just to bank it.)*
* **Expand Agency** – Increase your Agency Level by paying Credits

> Action economy is intentionally tight: you get only a few command turns, and larger agencies can coordinate more craft each round.

### Agency Levels

* **Level 1** – 1 Command Turn each Action Phase
* **Level 2** – 2 Command Turns each Action Phase, costs **6 Credits**
* **Level 3** – 3 Command Turns each Action Phase, costs **14 Credits**

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

#### Exploration Milestone Bonuses

* The first player to reach the **Moon branch** (`Moon Orbit` or farther) gains **+2 VP**.
* The first player to reach the **Mars branch** (`Mars ZOI` or farther) gains **+4 VP**.
* Each exploration milestone is awarded once per game.

#### Technology Milestone Bonuses

* The first time a player develops their **second Technology**, they gain **+1 VP**.
* The first player to develop their **fourth Technology** gains **+2 VP**.
* If you develop a Technology while you control an `On-Orbit` `Satellite` or `Station`, gain **+1 VP** (max once per round).

---

### 7.3 Launch Resolution

When a craft is launched or activated, resolve the flight immediately:

1. **Launch Capability Check:** Verify Engine Thrust ≥ Total Rocket Mass (sum of all Fuel Tank Mass values + Payload Mass). In design terms, this answers whether the stack can leave the surface; `Range` handles orbital travel after liftoff.
2. **Reliability Check:** Roll a d10. If the result is **≤ the Engine's Reliability value** (after modifiers from Technology cards and Events), the launch succeeds. If the roll is **above** Reliability, the launch **fails** — the craft does not move, and any non-Reusable Engine is discarded. Reusable Engines survive a failed check but the craft still does not launch this action. *(Skip this step when activating a craft already in flight. A Rocket-as-Lander relaunching from a surface must pass a new Reliability Check.)*
3. Optionally **Stage** one card with the `Stageable` tag to gain its printed bonus Range for this launch.
4. The player chooses a path on the orbital map. The craft moves along this path, spending **1 Range per node** crossed. The player may **stop at any node**, preserving unspent Range for future activations.
5. **Mid-Flight Staging:** At any point during movement (between nodes), a player may stage a `Stageable` card (typically an empty Fuel Tank) to gain its stage bonus Range. The staged card is discarded. This also reduces the craft's Mass for future relaunch capability checks.
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
* **Asset Operations:** Each persistent `Satellite` or `Station` you control may trigger each of its "spend Energy" income abilities **once**, spending the asset's remaining Energy this round. Collect the printed Credits or VP. This is free and costs no Command Turn. (Energy simply refills at the start of the next Action Phase — there is no separate Energy cleanup.)
* Discard the current round's Event card
* Refill the Mission display to 3 cards if needed
* Refill the **Card Market** to 5 cards from the Component Deck

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

### Mission Design Philosophy

* Early missions are forgiving
* Late missions reward specialization
* Recovery-focused missions should reward safe returns and reusable hardware
* Tier 1 should stay readable at a glance: low Range, few prerequisites, and short routes
* Tier 2 should introduce combo requirements such as satellites in orbit or lunar hardware
* Tier 3 should be reserved for deep-space distance, crewed coordination, or full return missions
* Missions encourage **different strategies**, not linear progression
* Commercial missions should keep the economy moving; prestige missions should tempt players to delay cashflow for headlines

---

## 9. Rocket Design System

A rocket consists of:

* **0–1 Engine**
* **1–3 Fuel Tanks**
* **0–1 Payload**
* **0–3 Support Cards**

A rocket's **total Range** equals the sum of all its Fuel Tank Range values. Some rockets may also use **staging** effects printed on cards to discard part of the rocket mid-flight for extra Range.

A rocket launched from a planet must have an Engine.
An Engine-free craft is only legal if it is already **in flight** or **in orbit** because of a mission, card, or ongoing asset effect.

### Qualification Rules

* Every Fuel Tank and Payload card has a numeric **Mass** value (tanks 1–4, payloads 1–3).
* Some Support cards also have printed **Mass**.
* **Total Rocket Mass** = sum of all Fuel Tank Mass values + Payload Mass + any printed Support Mass.
* An Engine's **Thrust** must be **≥ Total Rocket Mass** for the rocket to launch.
* If your rocket has **no Engine**, it may not launch from a planet.
* If your craft has **no Engine** but is already in flight or in orbit, its Total Rocket Mass must be **≤ 3**.
* Engines have no Mass for lift purposes. Support cards count only if they print a Mass value.
* **Range** measures remaining travel potential. A rocket's total Range = sum of all Fuel Tank Range values.
* **Energy** powers activated systems such as docking hardware, advanced sensors, and computer assists. A craft refills Energy to its power output at the start of each Action Phase and spends it during that round; a Battery may be discarded for a one-time burst.
* To **maneuver**, a craft must have an Engine to turn that Range into orbital changes.
* Missions with the `Docking` tag require a `Docking`-tagged support card on the rocket (e.g., Docking Adapter or Orbital Tug).
* Missions with the `Docking` or `Maneuver` tags require a rocket with an Engine.
* Missions with the `In-Flight` or `On-Orbit` tags may be attempted by legal Engine-free craft that are already in space.
* A rocket must satisfy both the Thrust/Mass check and the Range check before it can attempt a mission.
* If a rocket has no payload, only Fuel Tank Mass counts toward Total Rocket Mass.
* Card text is authoritative. When a card gives a more specific instruction than the general rules, resolve that card effect as written.

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

* To land on a body, a craft must be at the adjacent **Sub-Orbital** node and spend **1 Range** to cross to the surface.
* **Earth reentry** (Sub-Orbital Earth → Earth): use a `Heat Shield` or `Parachute` card (discard after use), or perform a **propulsive landing** by spending **1 extra Range** with an Engine.
* **Moon landing** (Sub-Orbital Moon → Moon): spend 1 Range. The Moon has no atmosphere, so landing always requires an Engine (propulsive). A dedicated Landing Lander payload **or** the rocket itself may serve as the lander (see Rocket-as-Lander below).
* **Mars landing** (Sub-Orbital Mars → Mars Surface): spend 1 Range. Mars has a thin atmosphere: use a `Heat Shield` or `Parachute` to assist, or perform a fully propulsive landing (1 extra Range + Engine). A dedicated Landing Lander **or** the rocket itself may serve as the lander.
* Each landing uses its own support. A Moon return trip needs propulsive lunar landing plus Earth-reentry support for the trip home.

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
* Many assets carry an **ongoing income** ability ("spend 1 Energy to gain 1 Credit/VP"). Pair the asset with a `Power` card so it has Energy each round, then collect that income for **free every Maintenance** during Asset Operations — you do not spend a Command Turn to bank it.
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
* **Mid-flight staging:** At any point during movement (between nodes), a player may stage one `Stageable` card (typically an empty Fuel Tank) to gain its stage bonus Range. This also reduces the craft's current Mass, which matters for relaunch capability checks.
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
* Multiple tanks let players build heavier rockets for longer missions, but each tank adds Mass that the Engine must lift.

### Tags

Cards use **tags** instead of keywords:

* `Reusable`
* `Experimental`
* `Crewed`
* `LifeSupport`
* `Electronics`
* `Power`
* `Deep Space`
* `Stageable`
* `Docking`
* `Maneuver`
* `In-Flight`
* `On-Orbit`
* `Heat Shield`
* `Parachute`
* `Satellite`
* `Station`
* `Basic`

> Tags allow flexible design space and future expansions.

---

## 10. Victory Points & End Game

VP sources:

* Missions
* Technologies
* Exploration milestones (first to Moon / first to Mars)
* Agency milestones (first to Level 3)
* End‑game bonuses

### End Game Trigger (Current)

* After **N rounds** *(default: 8)*
* Or when the Mission deck runs out

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
* Persistent asset economy loops: Comm Satellite and Station Hub generate Credits; Imaging Probe and Microgravity Lab generate VP. Assets must be **deployed** in space and paired with a `Power` card; income is harvested for free during the Maintenance *Asset Operations* step (no Command Turn required)
* Card Market for component acquisition
* Event cards integrated into Planning Phase
* Technology tableau (permanent until removed)
* Reliability check on launch (d10 ≤ Reliability)
* Rocket-as-Lander and mid-flight staging
* Hand size limit of 5
* Transfer Window mechanic for Mars timing
* Launch Capability Check terminology (Thrust ≥ Mass gate; Range is delta-v budget)
* Assemble and Launch may be combined into one Command Turn if all components are in hand

### Under Evaluation

* Player asymmetry (agency specialization or starting bonuses)
* Exact TW track schedule per round
* Final round count / end-game trigger tuning
* Whether second players completing a mission should get a reduced reward

### Playtest Readiness

* Card list v0.2 complete (142 cards, 12 missions, full component set)
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

**End of Draft v0.2**
