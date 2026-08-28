$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$environmentPath = Join-Path $projectRoot '.venv'
$environmentPython = Join-Path $environmentPath 'Scripts\python.exe'
$lockedRequirements = Join-Path $PSScriptRoot 'requirements-lock.txt'

Set-Location $projectRoot

if (-not (Test-Path -LiteralPath $environmentPython)) {
    $pythonLauncher = Get-Command py -ErrorAction SilentlyContinue

    if ($null -eq $pythonLauncher) {
        throw 'Python launcher not found. Install Python 3.12, restart PowerShell, and run this script again.'
    }

    Write-Host 'Creating the GymRAVANA Python environment...'
    & $pythonLauncher.Source -3.12 -m venv $environmentPath --prompt GymRAVANA-AI

    if ($LASTEXITCODE -ne 0) {
        throw 'Python could not create .venv. Confirm that Python 3.12 is installed.'
    }
}

Write-Host 'Installing the tested notebook dependencies...'
& $environmentPython -m pip install --requirement $lockedRequirements

if ($LASTEXITCODE -ne 0) {
    throw 'Dependency installation failed. Review the pip error shown above.'
}

& $environmentPython -c "import jupyterlab, kagglehub, mediapipe, nbformat, pandas, sklearn; print(f'JupyterLab {jupyterlab.__version__} | pandas {pandas.__version__} | scikit-learn {sklearn.__version__} | MediaPipe {mediapipe.__version__} | KaggleHub {kagglehub.__version__} | nbformat {nbformat.__version__}')"

if ($LASTEXITCODE -ne 0) {
    throw 'The environment was installed, but its import check failed.'
}

Write-Host ''
Write-Host 'AI environment ready.'
Write-Host 'Start it with: powershell -ExecutionPolicy Bypass -File ai\start-jupyter.ps1'
