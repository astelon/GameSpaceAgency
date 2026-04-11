# AI Art Pipeline

This project now supports a command-driven artwork workflow built around the card CSV.

## Source Of Truth

- `cards/cards.csv` remains the source of truth.
- `ArtFile` is the resolved asset path used by nanDECK, print exports, and later TTS exports.
- `ImageDescription` is the preferred prompt field for generated art.
- If `ImageDescription` is blank, the generator falls back to `DESCRIPTION`.

## Recommended Workflow

1. Tweak the card's `ImageDescription` in `cards/cards.csv`.
2. Generate or regenerate art from the command line.
3. Review the output in `cards/art/generated/`.
4. Keep iterating on the prompt until the image is good enough for prototype use.
5. Once the art is accepted, keep the generated file path in `ArtFile` or continue using placeholder category art until you are ready to switch.
6. Export the shared asset manifests so print and TTS builds both read the same final `ArtFile` values.

## Hugging Face Setup

Set a Hugging Face token before generating art.

PowerShell:

```powershell
$env:HUGGINGFACE_API_TOKEN = "your_token_here"
```

If PowerShell blocks local scripts on your machine, use a process-only bypass before running the generator:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
```

The generator defaults to `stabilityai/stable-diffusion-2-1`, but you can override the model with `-Model`.

## Commands

Generate art for one card:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\generate_ai_art.ps1 -CardId M01
```

Generate missing art for all cards:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\generate_ai_art.ps1 -MissingOnly
```

Generate a prompt manifest without calling the API:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\generate_ai_art.ps1 -Command manifest
```

Generate art and update `ArtFile` for the selected cards:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\generate_ai_art.ps1 -CardId P07,S03,S04 -UpdateCsvArtFile
```

Export shared print and TTS manifests from the current CSV:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\export_asset_manifest.ps1
```

Force regeneration with a different model:

```powershell
cd c:\Users\lgmar\GameSpaceAgency\cards
.\generate_ai_art.ps1 -CardId M12 -Model runwayml/stable-diffusion-v1-5 -Force
```

## Outputs

- Generated art files are written to `cards/art/generated/` by default.
- Prompt and generation metadata are written to `cards/output/art_manifest.json`.
- Shared downstream asset data are written to `cards/output/card_asset_manifest.json` and `cards/output/tts_deck_manifest.json`.

## Downstream Flow

- Print uses `ArtFile` directly through nanDECK.
- The asset exporter snapshots the resolved `ArtFile` values so future TTS scripts can consume the same paths without re-reading layout logic.
- This keeps generated art, print builds, and TTS deck setup on the same source of truth.

## Prompt Guidance

Use `ImageDescription` for generation-specific prompt writing.

Good prompts usually include:

- The card subject
- A clear camera angle or composition
- A consistent prototype art style
- Lighting and readability cues
- Any constraints that keep the image appropriate for a card frame

Example:

```text
Reusable lunar return capsule streaking through Earth's upper atmosphere, dramatic reentry glow, clean educational sci-fi illustration, readable silhouette, bright cinematic lighting
```

## Notes

- This pipeline is meant for prototype iteration, not final commercial-ready art.
- Start with single-card generation and prompt refinement before batch generation.
- The provider layer is intentionally simple so it can later be swapped to a local Stable Diffusion workflow without changing the CSV format.