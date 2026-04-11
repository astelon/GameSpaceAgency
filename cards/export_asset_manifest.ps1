param(
    [string]$CsvPath = (Join-Path $PSScriptRoot 'cards.csv'),
    [string]$OutputDir = (Join-Path $PSScriptRoot 'output')
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-RelativeArtPath {
    param($Card)

    if ([string]::IsNullOrWhiteSpace($Card.ArtFile)) {
        return $null
    }

    return ($Card.ArtFile -replace '\\', '/')
}

if (-not (Test-Path $CsvPath)) {
    throw ('CSV file not found: {0}' -f $CsvPath)
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$cards = Import-Csv -Path $CsvPath
$cardsRoot = [System.IO.Path]::GetFullPath($PSScriptRoot)

$assetManifest = foreach ($card in $cards) {
    $relativeArtPath = Get-RelativeArtPath -Card $card
    $absoluteArtPath = $null
    $artExists = $false

    if ($relativeArtPath) {
        $absoluteArtPath = Join-Path $cardsRoot ($relativeArtPath -replace '/', '\\')
        $artExists = Test-Path $absoluteArtPath
    }

    [pscustomobject]@{
        CardID = $card.CardID
        Name = $card.Name
        Type = $card.Type
        MissionType = $card.MissionType
        Tier = $card.Tier
        ArtFile = $relativeArtPath
        AbsoluteArtPath = $absoluteArtPath
        ArtExists = $artExists
        Color = $card.Color
        Prompt = if (-not [string]::IsNullOrWhiteSpace($card.ImageDescription)) { $card.ImageDescription } else { $card.DESCRIPTION }
    }
}

$ttsManifest = [pscustomobject]@{
    GeneratedAt = (Get-Date).ToString('o')
    SourceCsv = $CsvPath
    Decks = ($assetManifest | Group-Object Type | ForEach-Object {
        [pscustomobject]@{
            DeckType = $_.Name
            Count = $_.Count
            Cards = ($_.Group | ForEach-Object {
                [pscustomobject]@{
                    CardID = $_.CardID
                    Name = $_.Name
                    Tier = $_.Tier
                    MissionType = $_.MissionType
                    ArtFile = $_.ArtFile
                    AbsoluteArtPath = $_.AbsoluteArtPath
                    ArtExists = $_.ArtExists
                }
            })
        }
    })
}

$assetManifestPath = Join-Path $OutputDir 'card_asset_manifest.json'
$ttsManifestPath = Join-Path $OutputDir 'tts_deck_manifest.json'

$assetManifest | ConvertTo-Json -Depth 5 | Set-Content -Path $assetManifestPath -Encoding UTF8
$ttsManifest | ConvertTo-Json -Depth 6 | Set-Content -Path $ttsManifestPath -Encoding UTF8

Write-Host ('Wrote asset manifest to {0}' -f $assetManifestPath)
Write-Host ('Wrote TTS manifest to {0}' -f $ttsManifestPath)