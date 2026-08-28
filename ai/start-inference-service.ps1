$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$python = Join-Path $projectRoot '.venv\Scripts\python.exe'

if (-not (Test-Path -LiteralPath $python)) {
    throw 'The project-local Python environment is missing. Run ai\setup-environment.ps1 first.'
}

Set-Location -LiteralPath $projectRoot
& $python -m uvicorn ai.service.main:app --host 127.0.0.1 --port 8001
