param(
    [ValidateSet('generate', 'manifest')]
    [string]$Command = 'generate',
    [string]$CsvPath = (Join-Path $PSScriptRoot 'cards.csv'),
    [string]$OutputDir = (Join-Path $PSScriptRoot 'art\generated'),
    [string]$ManifestPath = (Join-Path $PSScriptRoot 'output\art_manifest.json'),
    [string[]]$CardId,
    [switch]$MissingOnly,
    [switch]$Force,
    [switch]$UpdateCsvArtFile,
    [string]$Model = 'black-forest-labs/FLUX.1-schnell',
    [string]$NegativePrompt = '',
    [int]$Seed = 0,
    [string]$Token = $env:HUGGINGFACE_API_TOKEN
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Fall back to Windows Credential Manager if token not supplied via env or parameter
if ([string]::IsNullOrWhiteSpace($Token)) {
    try {
        [void][Windows.Security.Credentials.PasswordVault, Windows.Security.Credentials, ContentType=WindowsRuntime]
        $vault = New-Object Windows.Security.Credentials.PasswordVault
        $cred  = $vault.Retrieve('GameSpaceAgency', 'HuggingFace')
        $cred.RetrievePassword()
        $Token = $cred.Password
    } catch {
        # Vault entry not found — token will remain blank and fail later with a clear error
    }
}

function Test-Blank {
    param([string]$Value)
    return [string]::IsNullOrWhiteSpace($Value)
}

function Get-PromptSpec {
    param($Card)

    if ($Card.PSObject.Properties.Name -contains 'ImageDescription' -and -not (Test-Blank $Card.ImageDescription)) {
        return [pscustomobject]@{
            Prompt = $Card.ImageDescription.Trim()
            PromptField = 'ImageDescription'
        }
    }

    if ($Card.PSObject.Properties.Name -contains 'DESCRIPTION' -and -not (Test-Blank $Card.DESCRIPTION)) {
        return [pscustomobject]@{
            Prompt = $Card.DESCRIPTION.Trim()
            PromptField = 'DESCRIPTION'
        }
    }

    $fallback = @($Card.Type, $Card.Name, $Card.Flavor) -join ' '
    return [pscustomobject]@{
        Prompt = $fallback.Trim()
        PromptField = 'fallback'
    }
}

function Get-GeneratedRelativePath {
    param(
        $Card,
        [string]$BaseOutputDir
    )

    if (-not (Test-Blank $Card.ArtFile) -and $Card.ArtFile -like 'art/generated/*') {
        return ($Card.ArtFile -replace '\\', '/')
    }

    $rootPath = [System.IO.Path]::GetFullPath($PSScriptRoot)
    $outputPath = [System.IO.Path]::GetFullPath($BaseOutputDir)

    if (-not $outputPath.StartsWith($rootPath, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw ('OutputDir must stay inside the cards folder so ArtFile stays relative: {0}' -f $BaseOutputDir)
    }

    $relativeDirectory = $outputPath.Substring($rootPath.Length).TrimStart('\')
    if (Test-Blank $relativeDirectory) {
        $relativeDirectory = 'art/generated'
    }

    $relativeDirectory = $relativeDirectory -replace '\\', '/'
    return ('{0}/{1}.jpg' -f $relativeDirectory, $Card.CardID)
}

function Save-Manifest {
    param(
        [array]$Entries,
        [string]$Path
    )

    $directory = Split-Path -Parent $Path
    if (-not (Test-Path $directory)) {
        New-Item -ItemType Directory -Path $directory | Out-Null
    }

    $json = $Entries | ConvertTo-Json -Depth 6
    Set-Content -Path $Path -Value $json -Encoding UTF8
}

function Invoke-HuggingFaceImageGeneration {
    param(
        [string]$ModelName,
        [string]$Prompt,
        [string]$Negative,
        [int]$SeedValue,
        [string]$ApiToken,
        [string]$DestinationPath
    )

    if (Test-Blank $ApiToken) {
        throw 'Missing Hugging Face token. Set HUGGINGFACE_API_TOKEN or pass -Token.'
    }

    $uri = 'https://router.huggingface.co/hf-inference/models/{0}' -f $ModelName
    $payload = @{
        inputs = $Prompt
        parameters = @{}
    }

    if ($SeedValue -gt 0) {
        $payload.parameters.seed = $SeedValue
    }

    $jsonBody = $payload | ConvertTo-Json -Depth 5
    Add-Type -AssemblyName System.Net.Http
    $handler = New-Object System.Net.Http.HttpClientHandler
    $client = New-Object System.Net.Http.HttpClient($handler)
    $client.Timeout = [TimeSpan]::FromMinutes(5)
    $client.DefaultRequestHeaders.Authorization = New-Object System.Net.Http.Headers.AuthenticationHeaderValue('Bearer', $ApiToken)

    try {
        $content = New-Object System.Net.Http.StringContent($jsonBody, [System.Text.Encoding]::UTF8, 'application/json')
        $response = $client.PostAsync($uri, $content).GetAwaiter().GetResult()
        $mediaType = $null
        if ($response.Content.Headers.ContentType) {
            $mediaType = $response.Content.Headers.ContentType.MediaType
        }

        if (-not $response.IsSuccessStatusCode) {
            $errorText = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
            throw ('Hugging Face request failed: {0} {1}' -f [int]$response.StatusCode, $errorText)
        }

        if ($mediaType -and $mediaType.StartsWith('image/')) {
            $bytes = $response.Content.ReadAsByteArrayAsync().GetAwaiter().GetResult()
            [System.IO.File]::WriteAllBytes($DestinationPath, $bytes)
            return
        }

        $text = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
        throw ('Expected image output but received {0}: {1}' -f $mediaType, $text)
    }
    finally {
        $client.Dispose()
        $handler.Dispose()
    }
}

if (-not (Test-Path $CsvPath)) {
    throw ('CSV file not found: {0}' -f $CsvPath)
}

$cards = @(Import-Csv -Path $CsvPath)
$selectedCards = @($cards)

if ($CardId -and $CardId.Count -gt 0) {
    $idSet = @{}
    foreach ($id in $CardId) {
        $idSet[$id.Trim()] = $true
    }

    $selectedCards = @($cards | Where-Object { $idSet.ContainsKey($_.CardID) })
}

if ($selectedCards.Count -eq 0) {
    throw 'No cards matched the requested selection.'
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$manifestEntries = @()

foreach ($card in $selectedCards) {
    $promptSpec = Get-PromptSpec -Card $card
    $relativeArtPath = Get-GeneratedRelativePath -Card $card -BaseOutputDir $OutputDir
    $destinationPath = Join-Path $PSScriptRoot ($relativeArtPath -replace '/', '\\')
    $destinationDir = Split-Path -Parent $destinationPath

    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir | Out-Null
    }

    $entry = [pscustomobject]@{
        CardID = $card.CardID
        Name = $card.Name
        Type = $card.Type
        Prompt = $promptSpec.Prompt
        PromptField = $promptSpec.PromptField
        NegativePrompt = $NegativePrompt
        Model = $Model
        Seed = $Seed
        ArtFile = $relativeArtPath
        GeneratedAt = (Get-Date).ToString('o')
    }

    $manifestEntries += $entry

    if ($Command -eq 'manifest') {
        continue
    }

    if ($MissingOnly -and (Test-Path $destinationPath) -and -not $Force) {
        Write-Host ('Skipping {0} because generated art already exists.' -f $card.CardID)
        continue
    }

    if ((Test-Path $destinationPath) -and -not $Force) {
        Write-Host ('Skipping {0}; use -Force to overwrite {1}.' -f $card.CardID, $relativeArtPath)
        continue
    }

    Write-Host ('Generating art for {0} ({1}) -> {2}' -f $card.CardID, $card.Name, $relativeArtPath)
    Invoke-HuggingFaceImageGeneration -ModelName $Model -Prompt $promptSpec.Prompt -Negative $NegativePrompt -SeedValue $Seed -ApiToken $Token -DestinationPath $destinationPath

    if ($UpdateCsvArtFile) {
        $card.ArtFile = $relativeArtPath
    }
}

Save-Manifest -Entries $manifestEntries -Path $ManifestPath
Write-Host ('Wrote manifest to {0}' -f $ManifestPath)

if ($UpdateCsvArtFile) {
    $cards | Export-Csv -Path $CsvPath -NoTypeInformation -Encoding UTF8
    Write-Host ('Updated ArtFile values in {0}' -f $CsvPath)
}