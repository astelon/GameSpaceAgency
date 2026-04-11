# Space Agency — Card Set v0.1

A prototype card game built around space mission planning. Cards are defined in a CSV, rendered with nanDECK, and can be exported for print-and-play or Tabletop Simulator.

## Project Structure

| Path | Purpose |
|---|---|
| `cards/cards.csv` | Source of truth — all card data, prompts, and art file references |
| `cards/template.nandeck` | nanDECK layout script (print, A4 PDF) |
| `cards/template_tts.nandeck` | nanDECK layout script (Tabletop Simulator, per-card PNG) |
| `cards/art/icons/` | SVG/PNG type icons (Engine, Tank, Mission, etc.) |
| `cards/art/generated/` | AI-generated card art output |
| `cards/output/print/` | Rendered print-ready PDF (`space_agency_deck.pdf`) |
| `cards/output/cards/` | Per-card PNGs for Tabletop Simulator (`card_1.png` … `card_44.png`) |
| `cards/output/` | Exported manifests (art, asset, TTS deck) |
| `docs/rulebook.html` | Printable rulebook |
| `docs/ai_art_pipeline.md` | Detailed AI art workflow reference |

---

## One-Time Setup

### 1. Store your Hugging Face token

Run the VS Code task **AI Art: Store HF Token** (or set the environment variable):

```powershell
$env:HUGGINGFACE_API_TOKEN = "hf_your_token_here"
```

Your token needs the **"Make calls to Inference Providers"** permission enabled at huggingface.co/settings/tokens.

### 2. Confirm tools are installed

- **ImageMagick** (`magick`) or **Inkscape** — required to convert SVG icons to PNG
- **nanDECK** — installed at `C:\Program Files (x86)\nanDECK\nanDECK.exe`

---

## Build Flow

### Step 1 — Generate Card Art

Use VS Code tasks (Terminal → Run Task) or run scripts directly from `cards/`.

**Generate art for a specific card:**
```powershell
cd cards
.\generate_ai_art.ps1 -CardId M01 -Force -UpdateCsvArtFile
```

**Generate art for all cards that are missing it:**
```powershell
cd cards
.\generate_ai_art.ps1 -MissingOnly -UpdateCsvArtFile
```

Generated images are saved to `cards/art/generated/` and the `ArtFile` column in `cards.csv` is updated automatically when `-UpdateCsvArtFile` is passed.

### Step 2 — Convert SVG Icons to PNG

Type icons live in `cards/art/icons/` as SVGs. nanDECK requires PNGs.

Run the VS Code task **Cards: Convert SVG Icons to PNG**, or:

```powershell
cd cards
.\convert_svgs.ps1
```

This uses ImageMagick (`magick`) or Inkscape and outputs PNGs alongside each SVG in `cards/art/icons/`.

### Step 3 — Export Asset Manifests

Generates `cards/output/art_manifest.json`, `card_asset_manifest.json`, and `tts_deck_manifest.json` from the current state of `cards.csv`. Run this after any art or CSV changes so all downstream builds use consistent paths.

Run the VS Code task **AI Art: Export Asset Manifests**, or:

```powershell
cd cards
.\export_asset_manifest.ps1
```

---

## Print-and-Play

### Step 4a — Build the PDF with nanDECK

Run the VS Code task **Cards: Build Deck (nanDECK)**, or:

```powershell
& 'C:\Program Files (x86)\nanDECK\nanDECK.exe' 'cards\template.nandeck' /EXEC /CREATEPDF /OUTPUT="output\print\space_agency_deck"
```

The rendered PDF is saved to `cards/output/print/space_agency_deck.pdf`. Open nanDECK's GUI to preview individual cards or adjust the layout before exporting.

### Step 5a — Print the Rulebook

Open `docs/rulebook.html` in a browser and use the browser's print dialog. Print double-sided and fold for a booklet, or print single-sided for reference sheets.

---

## Tabletop Simulator

### Step 4b — Export Per-Card PNGs for TTS

Run the VS Code task **Cards: Export TTS Cards (PNG)**, or:

```powershell
& 'C:\Program Files (x86)\nanDECK\nanDECK.exe' 'cards\template_tts.nandeck' /EXEC /CREATEPNG /OUTPUT="output\cards\card"
```

This uses `template_tts.nandeck`, which is identical in design to `template.nandeck` but sets the page size to match the card size so nanDECK renders one PNG per card. Output is saved to `cards/output/cards/` as `card_1.png` through `card_44.png`. The PNGs use the same assets and layout as the print PDF, ensuring visual consistency.

In TTS, import each PNG as a **Custom Card** face, or reference `cards/output/tts_deck_manifest.json` (from Step 3) for the full deck metadata.

### Step 5b — Rulebook in TTS

Add `docs/rulebook.html` as a **Notebook** entry in your TTS save, or convert it to a PDF (print to PDF from a browser) and include it as a custom PDF object on the table.

---

## VS Code Tasks

Open the task runner with **Terminal → Run Task** (`Ctrl+Shift+P` → *Tasks: Run Task*).

### Setup

| Task | When to run |
|---|---|
| **AI Art: Store HF Token** | Once per machine. Prompts for your `hf_…` token and stores it in Windows Credential Manager. Avoids keeping the token in environment variables or files. |

### Art & Asset pipeline

| Task | What it does |
|---|---|
| **AI Art: Generate Specific Card** | Prompts for a card ID (e.g. `M01`, `S03`), calls Hugging Face, saves the image to `art/generated/`, and updates `ArtFile` in `cards.csv`. |
| **AI Art: Generate Missing Cards** | Runs generation for every card whose `ArtFile` is still pointing at a placeholder icon. Skips cards that already have generated art. |
| **Cards: Convert SVG Icons to PNG** | Converts every SVG in `art/icons/` to a same-named PNG using ImageMagick (`magick`) or Inkscape — whichever is on `PATH`. Run this after adding or editing any type icon SVG. |
| **AI Art: Export Asset Manifests** | Reads `cards.csv` and writes `output/art_manifest.json`, `output/card_asset_manifest.json`, and `output/tts_deck_manifest.json`. Run after any art or CSV change before building the deck. |

### Build

| Task | What it does |
|---|---|
| **Cards: Build Deck (nanDECK)** | Validates and renders all 44 cards to `output/print/space_agency_deck.pdf` using `template.nandeck`. Requires nanDECK at `C:\Program Files (x86)\nanDECK\nanDECK.exe`. |
| **Cards: Export TTS Cards (PNG)** | Renders one PNG per card to `output/cards/card_N.png` using `template_tts.nandeck`. Identical design to the print version — same assets, same layout. Use these files as card faces in Tabletop Simulator. |

### Recommended order

```
AI Art: Store HF Token          ← once per machine
AI Art: Generate Missing Cards  ← repeat until art looks good
Cards: Convert SVG Icons to PNG ← after any icon SVG edits
AI Art: Export Asset Manifests  ← after any CSV/art changes
Cards: Build Deck (nanDECK)     ← PDF → output/print/space_agency_deck.pdf
Cards: Export TTS Cards (PNG)   ← PNGs → output/cards/card_1…44.png
```

---

## Design Notes

- Defaults for playtests: 8 rounds, hand size 5, small credit economy — see `Space_Agency.md`.
- Card stats use embedded fields; Mission cards require matching Range and Payload conditions.
- Tweak stats and card counts after blind playtests; `cards.csv` is the only file that needs to change for balance updates.
