# Roadmap — PF2e CRPG Engine

This document describes the phased plan to build a fully-modelled, working implementation of the **Pathfinder 2e** ruleset, exposable over **MCP** so that an AI Dungeon Master agent can run a complete CRPG game session.

---

## Vision

```
AI DM Agent  ──MCP──►  PF2e Engine  ──►  CRPG Client
   (LLM)                (this repo)         (UI / CLI)
```

The engine is the authoritative rules layer. The AI DM connects to it via the Model Context Protocol (MCP), issuing commands ("roll initiative", "the fighter Strikes the goblin", "apply Frightened 2") and reading back game state. A thin CRPG client renders that state for the human player(s). The engine never generates narrative — that is the DM agent's job.

---

## Phases

### Phase 0 — Foundation ✅ (In Progress)

Set up project scaffolding and governance before any game logic is written.

- [x] `CONTRIBUTING.md` — contribution guidelines
- [x] `ROADMAP.md` — this document
- [ ] Choose primary language and toolchain (TypeScript + Node recommended)
- [ ] Initialise `engine/` package with linter, formatter, test runner
- [ ] CI pipeline: lint → type-check → test on every PR
- [ ] Define JSON Schema drafts for core entity types (Character, Item, Spell, Feat, Condition)

---

### Phase 1 — Core Data Models

Define typed representations of every PF2e entity. No logic yet — pure data shape.

**Character & Actor Models**
- [ ] `Ability` — STR / DEX / CON / INT / WIS / CHA with modifier calculation
- [ ] `Proficiency` — Untrained / Trained / Expert / Master / Legendary + level bonus
- [ ] `Character` — ability scores, ancestry, heritage, background, class, level, HP, AC, speeds
- [ ] `Conditions` — all conditions from the CRB (Frightened, Stunned, Grabbed, Dying, etc.) with value ranges and stacking rules
- [ ] `HeroPoints` — tracking and spend rules

**Skills & Saves**
- [ ] All 16 skills with governing ability
- [ ] Fortitude / Reflex / Will saves
- [ ] Perception

**Ancestry & Class Skeletons**
- [ ] Ancestry model: ability boosts/flaws, HP, speed, size, traits, vision
- [ ] Class model: key ability, HP/level, proficiency progressions, class features list
- [ ] Data entries for Core Rulebook ancestries (Human, Elf, Dwarf, Gnome, Goblin, Halfling, Leshy, Orc)
- [ ] Data entries for Core Rulebook classes (Fighter, Wizard, Cleric, Rogue, Ranger, Bard, Champion, Druid, Monk, Sorcerer, Witch, Barbarian, Investigator, Oracle, Swashbuckler, Thaumaturge)

**Items**
- [ ] `Item` base model: name, level, traits, bulk, price, description
- [ ] `Weapon` — damage dice, damage type, group, range, reload, traits
- [ ] `Armor` — AC bonus, check penalty, speed penalty, dex cap, strength requirement
- [ ] `Shield` — hardness, HP, BT
- [ ] `Consumable` — charges, activation
- [ ] `Equipment` — worn/carried, invested limit
- [ ] `Treasure` — coins (CP/SP/GP/PP)
- [ ] `Container` — bulk capacity

**Spells**
- [ ] `Spell` — rank (1–10), traditions, traits, cast action cost, range, area, targets, duration, save type, heightened effects
- [ ] `SpellSlot` — rank, prepared/spontaneous variants
- [ ] `FocusPool` — points, max (capped at 3)
- [ ] `Cantrip` — auto-heightens to half level

**Feats**
- [ ] `Feat` — type (Ancestry/Class/Skill/General/Archetype), prerequisites, traits, level requirement
- [ ] `Action` / `FreeAction` / `Reaction` / `PassiveAbility` feat subtypes

---

### Phase 2 — Rules Engine (Core Mechanics)

Pure functions that consume current game state and return results / new state.

**Dice & Randomness**
- [ ] `roll(notation: string)` — parse and evaluate XdY±Z notation
- [ ] Exploding dice, reroll-and-keep variants
- [ ] Deterministic seed support for testing

**Checks**
- [ ] `skillCheck(actor, skill, dc, options)` — roll d20 + bonus vs DC
- [ ] Four-degree outcome resolution: Critical Success / Success / Failure / Critical Failure
- [ ] Automatic degree shift for natural 20 / natural 1
- [ ] Conditional modifiers: circumstance, status, item, untyped — stacking rules
- [ ] `Fortune` and `Misfortune` effects (reroll higher/lower)
- [ ] Persistent checks (e.g. flat check to remove Persistent Damage)

**Saves**
- [ ] `save(actor, saveType, dc, options)` — delegates to check engine
- [ ] Basic vs. basic save variants

**Action Economy**
- [ ] Action point tracking per turn (3 actions + 1 reaction per round)
- [ ] Action cost enforcement (◆ / ◆◆ / ◆◆◆ / ◇ / ↺)
- [ ] Free actions and reactions per-trigger tracking

**Combat**
- [ ] `Strike(attacker, target, weapon, options)` — to-hit roll, outcome, damage roll
- [ ] Multiple Attack Penalty (MAP): −5 / −10 standard; −4 / −8 for agile
- [ ] Damage types and resistance / weakness / immunity application
- [ ] Critical hit rules: double damage dice
- [ ] `DealDamage(target, amount, type)` — HP tracking, Dying state transitions
- [ ] Recovery checks (Dying → Wounded)
- [ ] Persistent Damage — end-of-turn flat check, stacking
- [ ] `Grab` / `Shove` / `Trip` — Athletics vs Fortitude/Reflex DCs
- [ ] `Aid` reaction — check before using, +1/+2/+3/−1 bonus to target
- [ ] Flanking detection — requires two actors on opposite sides
- [ ] Cover: standard / greater / lesser — AC/save bonuses
- [ ] Concealment and Invisibility — flat check requirement

**Spellcasting**
- [ ] Spell slot expenditure and recovery
- [ ] `CastSpell(caster, spell, rank, targets, options)`
- [ ] Area targeting (cone / burst / line — affected target resolution)
- [ ] Sustained spells — concentration tracking
- [ ] Focus Point expenditure and recovery (10-min refocus)
- [ ] Counteract checks

**Conditions Engine**
- [ ] Apply / remove / increment / decrement any condition
- [ ] End-of-turn automatic decrements (Frightened, Stunned, etc.)
- [ ] Immunity tracking (can't reapply same condition while immune)
- [ ] Condition interactions (Grabbed → Restrained upgrade, etc.)

**Exploration & Downtime**
- [ ] Exploration mode activities (Scout, Avoid Notice, Search, Investigate)
- [ ] Encounter distance and initiative roll from exploration
- [ ] Downtime activities (Craft, Earn Income, Train Skill, Treat Disease)

---

### Phase 3 — Content Data Population

Populate `engine/data/` with SRD-legal content entries validated against Phase 1 schemas.

- [ ] All Core Rulebook spells (rank 1–10 per tradition)
- [ ] All Core Rulebook feats (Ancestry, Class, Skill, General) for included classes
- [ ] Core equipment catalogue: weapons, armors, shields, adventuring gear
- [ ] Alchemical items: bombs, elixirs, mutagens
- [ ] All CRB conditions with full mechanical text
- [ ] Bestiary stat blocks — format matching `Actor` model
  - [ ] Phase 3a: Common low-level creatures (levels 1–5)
  - [ ] Phase 3b: Mid-tier creatures (levels 6–14)
  - [ ] Phase 3c: High-level and mythic creatures (levels 15–25)

---

### Phase 4 — Encounter & World Systems

Higher-level systems that the DM agent needs to run an adventure.

**Encounter**
- [ ] `InitiativeOrder` — sort actors by Perception check result at encounter start
- [ ] Turn state machine: Start of Turn → Actions → End of Turn → Next Actor
- [ ] `EncounterLog` — append-only log of every action and outcome (the DM agent reads this)
- [ ] XP budget system — encounter difficulty by party level
- [ ] Treasure generator by level and difficulty

**Exploration / Hex Crawl**
- [ ] `Map` — grid or hex, tile types, movement costs
- [ ] `Region` — biome, encounter tables, points of interest
- [ ] Travel time calculation with party speed
- [ ] Random encounter checks

**Party & Advancement**
- [ ] Party composition model — multiple PCs + companions
- [ ] XP tracking and automatic level-up prompt
- [ ] Level-up wizard: ability boosts, new class features, feat selection
- [ ] Resting: 8-hour HP/spell recovery; 10-min focus refocus

---

### Phase 5 — MCP Server (AI DM Interface)

Expose the engine as an MCP server so any LLM-based DM agent can drive a session.

**Tool Definitions** (MCP resources/tools the DM agent can call)
- [ ] `getGameState()` — full serialised current state
- [ ] `getCharacter(id)` — one actor's stat block + conditions
- [ ] `rollCheck(actorId, checkType, dc, modifiers?)` — resolve a check, return outcome
- [ ] `performAction(actorId, actionType, params)` — execute an action, return state delta
- [ ] `applyCondition(targetId, condition, value?)` — add/modify condition
- [ ] `removeCondition(targetId, condition)` — remove condition
- [ ] `dealDamage(targetId, amount, type)` — reduce HP, handle Dying
- [ ] `healDamage(targetId, amount)` — restore HP
- [ ] `castSpell(casterId, spellId, rank, targets, options?)` — cast + resolve
- [ ] `advanceTurn()` — move initiative tracker forward
- [ ] `startEncounter(actorIds)` — roll initiative, build order
- [ ] `endEncounter()` — clean up encounter state, award XP
- [ ] `rest(partyId, restType)` — short/long rest recovery
- [ ] `listFeats(filter?)` — browse available feats
- [ ] `listSpells(filter?)` — browse available spells

**MCP Server Implementation**
- [ ] MCP-compliant server boilerplate (stdio or HTTP transport)
- [ ] Tool schemas with full JSON Schema validation
- [ ] Stateful session model — each game session has an isolated state object
- [ ] Session persistence: save/load state to JSON file
- [ ] Error responses in MCP format (invalid action, missing resource, etc.)
- [ ] Streaming support for long-running operations (e.g. dice animation hint)

---

### Phase 6 — CRPG Client (Human Player Interface)

A thin client that receives game state from the engine and presents it to the human player. The DM agent handles narrative; the client handles display and player input.

- [ ] Terminal/CLI client (first milestone — simplest to implement)
  - [ ] Render current HP, conditions, AC for party + visible enemies
  - [ ] Accept player action input, forward to engine via MCP
  - [ ] Display `EncounterLog` entries as narrated by DM agent
- [ ] Web client (second milestone)
  - [ ] Character sheet view
  - [ ] Encounter map (grid/hex renderer)
  - [ ] Action palette with legal-action filtering
  - [ ] Dice roll animation (cosmetic only — results come from engine)
- [ ] Save / load game UI

---

### Phase 7 — AI DM Agent Integration

Wire a real LLM-based DM agent to the MCP server for end-to-end playtesting.

- [ ] Reference DM agent implementation (LangChain / Claude / GPT) that connects via MCP
- [ ] System prompt: DM instructions, PF2e narrative style guide, session rules
- [ ] Adventure module loader — structured JSON adventure (scenes, NPCs, objectives)
- [ ] DM agent evaluates player input → selects MCP tool → narrates result
- [ ] Out-of-combat dialogue and skill challenge handling
- [ ] Long-term memory: session notes, relationship tracking, quest state

---

### Phase 8 — Polish & Extensibility

- [ ] Additional official content: *Rage of Elements*, *Dark Archive*, *Secrets of Magic*
- [ ] Third-party content plugin API
- [ ] Multiplayer: multi-human party sharing one DM session
- [ ] Automated playtesting harness — run 1000 combats, verify statistical outcomes
- [ ] Performance: benchmark engine with 20-actor encounters
- [ ] Public API documentation

---

## Milestone Summary

| Milestone | Phases | Goal |
|---|---|---|
| **M1 — Schema Complete** | 0–1 | All data shapes defined and validated |
| **M2 — Engine Alpha** | 2 | Core combat loop playable in unit tests |
| **M3 — Content Complete** | 3 | Full CRB spell/feat/item data |
| **M4 — Encounter System** | 4 | Full encounter + exploration loop |
| **M5 — MCP Server** | 5 | AI DM can drive a combat via MCP tools |
| **M6 — Playable CRPG** | 6–7 | Human player + AI DM running a real session |
| **M7 — Production** | 8 | Polished, extensible, publicly usable |

---

## Out of Scope (for now)

- Paizo Product Identity content (Golarion lore, iconic characters, setting-specific deities)
- Visual assets or 3D rendering
- Real-time multiplayer networking (turn-based only at first)
- Pathfinder 1e or other systems
