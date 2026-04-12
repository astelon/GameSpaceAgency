# push_to_tts.ps1
# Reads tts/Global.lua and sends it to the running TTS instance
# via the TTS external editor TCP API (localhost:39999, messageID=1 = Save & Play).
#
# TTS must be open with a save loaded for this to work.

param(
    [string]$ScriptPath  = "$PSScriptRoot\Global.lua",
    [string]$TtsHost     = "localhost",
    [int]   $Port        = 39999,
    [int]   $TimeoutMs   = 5000
)

$resolved = Resolve-Path $ScriptPath -ErrorAction SilentlyContinue
if (-not $resolved) {
    Write-Error "Script not found: $ScriptPath"
    exit 1
}

# Read as UTF-8 bytes and convert to string; avoids BOM and encoding surprises
$lua = [System.IO.File]::ReadAllText($resolved.Path, [System.Text.Encoding]::UTF8)

# Build JSON manually so we control escaping precisely.
# ConvertTo-Json in PowerShell 5.1 escapes non-ASCII chars as \uXXXX which can
# confuse TTS's JSON parser (e.g. → and — in description strings).
# System.Web.HttpUtility.JavaScriptStringEncode produces clean, TTS-compatible escaping.
Add-Type -AssemblyName System.Web
$escaped = [System.Web.HttpUtility]::JavaScriptStringEncode($lua)
# messageID=1 is "Save & Play" in the TTS external editor API
$payload  = "{`"messageID`":1,`"scriptStates`":[{`"name`":`"Global`",`"guid`":`"-1`",`"script`":`"$escaped`"}]}`n"
$bytes    = [System.Text.Encoding]::UTF8.GetBytes($payload)

try {
    $client = New-Object System.Net.Sockets.TcpClient
    $connect = $client.BeginConnect($TtsHost, $Port, $null, $null)
    $connected = $connect.AsyncWaitHandle.WaitOne($TimeoutMs, $false)

    if (-not $connected) {
        Write-Error "Could not connect to TTS on ${TtsHost}:${Port} within ${TimeoutMs}ms. Is Tabletop Simulator running with a save loaded?"
        $client.Close()
        exit 1
    }

    $null = $client.EndConnect($connect)
    $stream = $client.GetStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Flush()

    # Shut down the send side cleanly so TTS receives EOF before we close.
    # Then drain any bytes TTS sends back (it may send a response on the same
    # connection in some versions) and wait up to TimeoutMs for it to finish.
    $client.Client.Shutdown([System.Net.Sockets.SocketShutdown]::Send)
    $buf      = New-Object byte[] 512
    $deadline = [DateTime]::UtcNow.AddMilliseconds($TimeoutMs)
    while ([DateTime]::UtcNow -lt $deadline) {
        if ($client.Client.Poll(50000, [System.Net.Sockets.SelectMode]::SelectRead)) {
            $n = $stream.Read($buf, 0, $buf.Length)
            if ($n -eq 0) { break }   # TTS closed the connection — done
        }
    }

    $stream.Close()
    $client.Close()

    Write-Host "Global.lua sent to TTS ($($bytes.Length) bytes). TTS should reload now." -ForegroundColor Green
}
catch {
    Write-Error "Failed to send to TTS: $_"
    exit 1
}
