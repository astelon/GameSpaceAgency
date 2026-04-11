# Convert all SVG files in cards/art to PNG for nanDECK
# Tries ImageMagick `magick` first, then Inkscape if available.

$artDir = Join-Path $PSScriptRoot "art"
if (-not (Test-Path $artDir)) { Write-Error "art directory not found: $artDir"; exit 1 }

$svgs = Get-ChildItem -Path $artDir -Filter *.svg -File -Recurse
if ($svgs.Count -eq 0) { Write-Host "No SVG files found in $artDir"; exit 0 }

# check for magick
$hasMagick = (Get-Command magick -ErrorAction SilentlyContinue) -ne $null
$hasInkscape = (Get-Command inkscape -ErrorAction SilentlyContinue) -ne $null

if (-not $hasMagick -and -not $hasInkscape) {
    Write-Error "Neither ImageMagick (magick) nor Inkscape found in PATH. Install one or run conversions manually."; exit 2
}

foreach ($svg in $svgs) {
    $png = [System.IO.Path]::ChangeExtension($svg.FullName, '.png')
    Write-Host "Converting $($svg.Name) -> $(Split-Path $png -Leaf)"
    if ($hasMagick) {
        & magick convert -density 300 -background transparent "$($svg.FullName)" -alpha on PNG32:"$png"
        if ($LASTEXITCODE -ne 0) { Write-Warning "magick failed for $($svg.Name)" }
    }
    elseif ($hasInkscape) {
        # Inkscape newer CLI: inkscape <input> --export-filename=<output>
        & inkscape "$($svg.FullName)" --export-filename="$png"
        if ($LASTEXITCODE -ne 0) { Write-Warning "inkscape failed for $($svg.Name)" }
    }
}

Write-Host "Done. Updated PNGs are in: $artDir"
