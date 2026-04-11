# 🚀 SPACE AGENCY RACE

**Official Design & Ruleset Draft (v0.1)**

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
| **Support Cards**            | Heat shields, parachutes, and landing gear           |
| **Mission Cards**            | Public contract opportunities tied to destinations   |
| **Technology Cards**         | Permanent upgrades and rule‑breakers                 |
| **Event Cards** *(optional)* | Global effects, limited randomness                   |

> ⚠️ *Event cards are optional and currently excluded from the core loop to maintain strategic control.*

### 3.2 Player Area

Each player maintains:

* **Agency Tableau** (played technologies, assets)
* **Rocket Assembly Area** (engine + fuel + payload)
* **In-Flight Area** (craft currently on the orbital board)
* **Hand** (limited size)
* **Credit Marker** on a personal Credit track
* **Agency Level Marker**

### 3.3 Shared Areas

* Orbital Board with **VP Track** and **Orbital Node Map**
* Mission Deck & Display
* Discard Piles

### 3.4 Orbital Board (First Draft)

The orbital board combines the **VP track** with a simple **orbital node map** for craft movement.

Prototype node map:

* `Earth` -> `Sub-Orbital Earth` -> `LEO` -> `High Orbit`
* `High Orbit` -> `Moon Transfer` -> `Moon Orbit` -> `Sub-Orbital Moon` -> `Moon`
* `High Orbit` -> `Solar Orbit` -> `Mars Transfer` -> `Mars Orbit` -> `LMO` -> `Sub-Orbital Mars` -> `Mars`

Each line between nodes costs **1 Range** to cross.

---

## 4. Core Resources

### 4.1 Resources (Abstracted)

| Resource             | Meaning                            |
| -------------------- | ---------------------------------- |
| **Credits**          | Funding, actions, card acquisition |
| **Range**            | How many orbital steps it can move |
| **Payload Size**     | How heavy the payload is           |
| **Reliability**      | Engine hardware failure risk       |

> Design choice: Resources are **mostly embedded in cards**, not tracked as loose tokens, reducing bookkeeping.

Tracking note:

* **Credits** are tracked openly on a personal player track.
* **VP** are tracked openly on the shared board and updated immediately when earned.
* **Completing missions is the main source of Credits**. Other sources should stay smaller or situational.

---

## 5. Setup

1. Each player chooses a color and receives:

   * 1 Starting Engine
   * 1 Basic Fuel Tank
   * 1 Starting Technology
   * 5 Credits
   * 1 Credit marker and 1 VP marker
   * 1 Agency Level marker set to **Level 1**
   * 6 craft markers for rockets and in-space assets

2. Shuffle each deck separately.

   * Separate Mission cards by **Tier 1**, **Tier 2**, and **Tier 3** before shuffling.
   * Shuffle only the **Tier 1 Mission** stack at setup.
   * Keep Tier 2 and Tier 3 Missions face-down beside the board until they unlock.

3. Reveal:

   * 3 Tier 1 Mission cards

4. Determine first player randomly.

---

## 6. Game Structure

The game proceeds over a series of **Rounds**.

### 6.1 Round Phases

1. **Planning Phase**
2. **Action Phase**
3. **Launch & Resolution Phase**
4. **Maintenance Phase**

The game ends after a fixed number of rounds **or** when the Mission deck is depleted (final trigger TBD).

---

## 7. Phase Details

### 7.1 Planning Phase

Players simultaneously:

* Draw cards (up to hand limit)
* May discard 1 card to gain 1 Credit as an emergency fallback

> Design intent: Encourage cycling without heavy randomness.

---

### 7.2 Action Phase

Each player has a number of **Command Turns** equal to their **Agency Level**.

Starting with the first player, players alternate taking **one Command Turn at a time** until everyone has used all of their Command Turns for the round.

Each individual craft may be activated at most **once per Action Phase**.

#### Available Actions

* **Acquire Card** – Pay cost, add to hand
* **Sell Part** – On your turn, discard a card from your hand and gain Credits equal to half its cost, rounded down
* **Develop Technology** – Play a Technology card
* **Assemble Rocket** – Attach Engine, Fuel, Payload, and optional Support cards
* **Upgrade Rocket** – Replace components
* **Accept Mission** – Claim one eligible Exclusive or Secret mission, or commit to a Public mission in the display
* **Launch New Craft** – Put one assembled rocket onto the orbital board and move it from its start node
* **Activate Craft** – Move one of your rockets, satellites, or stations on the orbital board
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
* Each tier unlock happens only once per game.
* When a new Mission Tier is added, immediately resolve a **Government Catch-Up Grant**.
* Tier intent: **Tier 1** teaches Earth return and LEO jobs, **Tier 2** adds asset-management and lunar missions, and **Tier 3** adds long-range crewed or return-heavy prestige missions.

#### Government Catch-Up Grant

* When **Tier 2** unlocks, the player with the **lowest VP** gains **3 Credits**.
* When **Tier 3** unlocks, the player with the **lowest VP** gains **4 Credits**.
* If multiple players are tied for lowest VP, the tied player with the **fewest Credits** gains the grant.
* If there is still a tie, each tied player gains **2 Credits** instead.

---

### 7.3 Launch & Resolution Phase

Launched and activated craft resolve in order:

1. Check **Lift**: match the payload to the engine using the simple lift tiers.
2. Optionally **Stage** one card with the `Stageable` tag to gain its printed bonus Range for this launch.
3. Spend **1 Range** for each node crossed on the orbital board.
4. Check whether the craft has reached the mission route's required node.
5. Check **Mission Requirements** (payload size, tags, special conditions).
6. Apply special effects.
7. Score VP and rewards.

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

> Design choice: Failures are costly but not game‑ending.

---

### 7.4 Maintenance Phase

* Reusable parts from craft that returned to `Earth` return to hand if they were not staged
* Ongoing effects trigger
* Refill the Mission display if needed

---

## 8. Missions

Mission cards define:

* **Destination** (LEO, Moon, Mars, Deep Space)
* **Mission Type** (Public, Exclusive, Secret)
* **Tier** (1, 2, or 3)
* **Route** (the orbital path or landing path to complete)
* **Requirements** (Range, Payload Size, Tags)
* **Rewards** (VP, Credits, Tech bonuses)

In this prototype, **Mission cards are the game's contract system**. Accepting a mission represents taking a public job offer from a government, commercial client, or scientific program.

Mission economy note:

* Completing missions is the primary way players earn Credits.
* Harder or longer missions should usually pay more Credits.
* Event-based Credits should be occasional bonuses, not the default economy.

### Mission Types

* **Public Missions** stay in the market after players commit to them, allowing multiple players to race to complete the same mission.
* **Exclusive Missions** are rare opportunities that leave the market when claimed and belong to a single player.
* **Secret Missions** are kept hidden in a player's area until completed, then revealed when scored.

### Mission Market Rules

* **Tier 1 Missions** are available from the start of the game.
* **Tier 2 Missions** enter the Mission deck when the first agency reaches Level 2.
* **Tier 3 Missions** enter the Mission deck when the first agency reaches Level 3.
* Public Missions leave the market only when completed.
* Exclusive Missions leave the market as soon as they are claimed.
* Secret Missions may be chosen during setup or gained later when a card effect allows it.
* When a mission is completed, reveal it if needed, score its rewards immediately, then discard it.
* Empty slots in the public Mission display are refilled during Maintenance.
* Mission cards should display their Mission Type and Tier clearly on the card face.

### Mission Design Philosophy

* Early missions are forgiving
* Late missions reward specialization
* Recovery-focused missions should reward safe returns and reusable hardware
* Tier 1 should stay readable at a glance: low Range, few prerequisites, and short routes
* Tier 2 should introduce combo requirements such as satellites in orbit or lunar hardware
* Tier 3 should be reserved for deep-space distance, crewed coordination, or full return missions
* Missions encourage **different strategies**, not linear progression
* Most missions should pay at least some Credits so mission play drives the economy

---

## 9. Rocket Design System

A rocket consists of:

* **0–1 Engine**
* **1 Fuel Tank**
* **0–1 Payload**
* **0–2 Support Cards**

Some rockets may also use **staging** effects printed on cards to discard part of the rocket mid-flight for extra Range.

A rocket launched from a planet must have an Engine.
An Engine-free craft is only legal if it is already **in flight** or **in orbit** because of a mission, card, or ongoing asset effect.

### Qualification Rules

* **Thrust** measures lift.
* If your rocket has **no Engine**, it may not launch from a planet.
* If your craft has **no Engine** but is already in flight or in orbit, it may carry only a **Light** payload.
* **Light** payloads can be launched by any Engine.
* **Medium** payloads require **Thrust 5+**.
* **Heavy** payloads require **Thrust 7+**.
* **Fuel Tanks are not treated as payload** for lift checks. Their mass is already abstracted into their Range, Reliability, and card effects.
* **Support Cards are not treated as payload** for lift checks. Their mass is abstracted into their card effects.
* **Range** measures remaining travel potential. It represents the fuel and momentum already available to the craft.
* To **maneuver**, a craft must have an Engine to turn that Range into orbital changes.
* Missions with the `Docking` or `Maneuver` tags require a rocket with an Engine.
* Missions with the `In-Flight` or `On-Orbit` tags may be attempted by legal Engine-free craft that are already in space.
* A rocket must satisfy both the Lift check and the Range check before it can attempt a mission.
* If a rocket has no payload, ignore the Lift check unless a card effect says otherwise.

### Orbital Node Travel

* The game treats **Range** as **delta-v** in a simple form.
* Travel happens on a graph of **nodes** connected by lines.
* Each line crossed costs **1 Range**.
* A mission's Range requirement is the total number of node-to-node moves in its route.
* Add more nodes wherever you want a longer or more difficult trip.
* Return trips are allowed. Count the steps for the way back too.
* If a mission is a round trip, its card should include the full out-and-back route in the Range cost.
* A craft moves **one node per activation** unless a card effect says otherwise.

### Landing Rules

* Reaching a **Sub-Orbital** node is enough to land.
* To land from a Sub-Orbital node, use one `Heat Shield` or `Parachute` effect and discard or stage it after use.
* If you do not use a `Heat Shield` or `Parachute`, you may perform a **propulsive landing** by spending **1 extra Range** and using an Engine.
* Each landing uses its own landing support. A return trip to Mars usually needs one set for arrival and another set for the trip home.

### Persistent Assets

* Payloads with the `Satellite` or `Station` tags remain on the orbital board after a successful delivery mission.
* These assets use your craft markers and may be activated on future turns like any other craft.
* Satellites and stations count against your available commands during the Action Phase.

### Staging Rules

* Some cards have the `Stageable` tag and a printed **Stage** effect.
* During Launch, after the Lift check and before the Range check, you may Stage **one** `Stageable` card in your rocket.
* If the staged card is an **Engine**, it counts for the Lift check and still counts as your rocket's Engine for the rest of that mission.
* When you Stage a card, gain the printed bonus Range for that launch.
* Staging never changes **Payload Size**. It only changes how much Range your rocket can reach on that launch.
* Fuel use is abstracted into the card values. The printed Stage bonus already represents the efficiency gained by dropping spent parts.
* A staged card is discarded after launch and cannot be recovered unless another effect returns it.
* Once a card is staged, you lose any future benefit from that card, including reusability or passive effects.

Design note:

* These thresholds are intentionally simple so launch qualification stays readable at a glance rather than becoming a math exercise.
* Stageable Engines are the game's simple version of the rocket equation: the card represents a booster plus sustainer, so it helps you lift the rocket early, then turns into extra Range once the spent booster part is dropped.
* A craft already in orbit does not need an Engine just to remain there. It needs one only if it must maneuver again.
* Range is not a separate fuel mini-game. Players only count simple node moves and a few extra costs such as propulsive landings.

### Tags

Cards use **tags** instead of keywords:

* `Reusable`
* `Experimental`
* `Crewed`
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

> Tags allow flexible design space and future expansions.

---

## 10. Victory Points & End Game

VP sources:

* Missions
* Technologies
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

* Define mission reach
* Interact with engine type
* Occasionally offer a simple staging decision

Example ideas:

* Lightweight tank: less payload
* Cryogenic tank: setup cost, higher range
* Drop tank: discard mid-flight for bonus range

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

## 13. Design Status & Known Open Questions

### Locked In

* Core turn structure
* Rocket assembly system
* Mission‑driven scoring

### Under Evaluation

* Transfer windows as timing constraints
* Optional Event cards
* Player asymmetry

### Next Iteration Goals

* Card list v0.1
* Balance pass on engines
* First blind playtest

---

## 14. Closing Notes

This draft prioritizes:

* Clarity over completeness
* Strategy over randomness
* Extensibility for future expansions

> *If the game feels tight, constrained, and slightly stressful — it is working as intended.*

---

**End of Draft v0.1**
