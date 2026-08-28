param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^[A-Za-z0-9_-]{3,30}$')]
    [string] $ParticipantId
)

$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$datasetRoot = Join-Path $projectRoot 'ai\data\pose_camera_validation'
$participantRoot = Join-Path $datasetRoot $ParticipantId
$consentPath = Join-Path $datasetRoot 'participants.csv'
$poseClasses = @(
    'balasana',
    'mayurasana',
    'salamba_sirsasana',
    'urdhva_dhanurasana',
    'virasana'
)

New-Item -ItemType Directory -Path $datasetRoot -Force | Out-Null

if (-not (Test-Path -LiteralPath $consentPath)) {
    Set-Content -LiteralPath $consentPath -Value "participant_id,consent_confirmed`n$ParticipantId,no" -Encoding utf8
} else {
    $participants = Import-Csv -LiteralPath $consentPath
    if ($participants.participant_id -notcontains $ParticipantId) {
        Add-Content -LiteralPath $consentPath -Value "$ParticipantId,no" -Encoding utf8
    }
}

foreach ($poseClass in $poseClasses) {
    New-Item -ItemType Directory -Path (Join-Path $participantRoot $poseClass) -Force | Out-Null
}

Write-Host "Created camera-validation folders for $ParticipantId."
Write-Host "Consent remains 'no' in: $consentPath"
Write-Host "Change it to 'yes' only after the participant has genuinely consented, then add their images."
