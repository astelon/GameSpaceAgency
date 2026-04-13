# Contributing to GameSpaceAgency — PF2e Engine

Thank you for your interest in contributing! This project is building a fully-modelled Pathfinder 2e (PF2e) rules engine designed to be driven by an AI Dungeon Master over MCP, eventually powering a PF2e CRPG.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Getting Started](#getting-started)
- [Repository Structure](#repository-structure)
- [Contribution Workflow](#contribution-workflow)
- [Coding Standards](#coding-standards)
- [Adding PF2e Content](#adding-pf2e-content)
- [Testing](#testing)
- [Opening Issues](#opening-issues)
- [License & ORC Attribution](#license--orc-attribution)

---

## Project Overview

The PF2e engine is split into three concerns:

| Layer | Description |
|---|---|
| **Data Models** | Typed representations of every PF2e entity: characters, feats, spells, items, conditions, actions, etc. |
| **Rules Engine** | Stateless functions that resolve game mechanics: checks, saves, attacks, damage, conditions, action economy. |
| **MCP Interface** | A Model Context Protocol server that exposes the rules engine to an AI DM agent, enabling a full CRPG loop. |

---

## Getting Started

1. **Fork** the repository and clone your fork locally.
2. Install dependencies (see `README.md` for per-module instructions as they are added).
3. Create a **feature branch** off `main`:
   ```bash
   git checkout -b feat/<short-description>
   ```
4. Make your changes, write or update tests, then open a pull request against `main`.

---

## Repository Structure

```
/
├── engine/               # PF2e rules engine (core logic)
│   ├── models/           # TypeScript/Python data model definitions
│   ├── rules/            # Mechanics resolvers (checks, combat, spells…)
│   └── data/             # Static PF2e content (feats, spells, ancestries…)
├── mcp/                  # MCP server — exposes engine to AI DM agent
├── crpg/                 # Optional CRPG client (UI / game loop)
├── docs/                 # Design docs, API references, rule summaries
├── cards/                # Legacy Space Agency card game assets
└── tts/                  # Legacy Tabletop Simulator exports
```

> **Note:** The `engine/`, `mcp/`, and `crpg/` directories will be scaffolded as the roadmap progresses. See `ROADMAP.md` for the delivery plan.

---

## Contribution Workflow

1. Check open issues and the `ROADMAP.md` before starting work to avoid duplication.
2. For significant changes, open a **discussion issue** first to agree on design.
3. Keep pull requests focused — one feature or fix per PR.
4. Reference the relevant roadmap phase (e.g. `Phase 2 — Rules Engine`) in your PR description.
5. All PRs require at least one approving review before merging.

---

## Coding Standards

- **Language**: TypeScript is preferred for the engine and MCP server; Python is acceptable for data pipeline scripts.
- **Style**: Follow the existing file's style. Linter configs will be added per module.
- **Naming**: Use PF2e terminology exactly as it appears in the published rules (e.g. `StrikeAction`, `FortitudeSave`, `MAP` for Multiple Attack Penalty).
- **Immutability**: Rules functions should be pure and stateless wherever possible — take game state in, return new state out.
- **No copyrighted text**: Do not paste verbatim text from Paizo publications. Use stat values and mechanical descriptions only, consistent with the ORC License.

---

## Adding PF2e Content

Content lives in `engine/data/` as structured JSON or TypeScript constant files, one file per content category (feats, spells, items, conditions, etc.).

When adding a new entry:
1. Follow the schema defined in `engine/models/` for that content type.
2. Include the source book and page reference in the `source` field.
3. Add a unit test in `engine/data/__tests__/` that validates schema conformance.
4. Keep mechanical descriptions factual (numbers and keywords), not narrative flavour text.

---

## Testing

- Unit tests live alongside source files in `__tests__/` directories.
- Run the full suite with:
  ```bash
  npm test          # or: python -m pytest
  ```
- Every rules function must have tests covering the happy path and key edge cases (e.g. critical success/failure, MAP, conditions modifying DCs).
- Tests for the MCP server use a mock game state fixture — do not require a live AI connection.

---

## Opening Issues

Use the following labels:

| Label | Use for |
|---|---|
| `model` | Data model design or schema changes |
| `rules` | Rules engine logic or mechanic implementation |
| `mcp` | MCP server / AI DM integration |
| `crpg` | CRPG client / game loop |
| `content` | Adding feats, spells, items, or other PF2e data |
| `bug` | Something broken |
| `question` | Clarification needed |

---

## License & ORC Attribution

This project is released under the **ORC License**. All PF2e mechanics implemented here derive from the Pathfinder 2e System Reference Document (SRD) published by Paizo under the ORC License.

- Do **not** include Paizo's Product Identity (setting-specific lore, unique character names, etc.).
- Mechanical content (numbers, keywords, action structures) is covered by the ORC License and is free to use.
- Include a `source` field on every data entry pointing to the relevant SRD section.
