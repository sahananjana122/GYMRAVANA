$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$environmentPython = Join-Path $projectRoot '.venv\Scripts\python.exe'
$notebookDirectory = Join-Path $PSScriptRoot 'notebooks'

if (-not (Test-Path -LiteralPath $environmentPython)) {
    throw 'The .venv environment is missing. Run ai\setup-environment.ps1 first.'
}

Set-Location $projectRoot
& $environmentPython -m jupyter lab $notebookDirectory
