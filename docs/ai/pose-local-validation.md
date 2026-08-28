# Local pose inference and camera validation

## What is now available

GymRAVANA's FastAPI process now has a separate `POST /v1/pose/validate` boundary. It accepts one JPEG, PNG or WebP image as the raw request body, extracts MediaPipe landmarks in memory, applies the reviewed five-class Random Forest and returns a validation-only result.

This endpoint is not connected to a Laravel member route. It listens through the same loopback service on `127.0.0.1:8001`, refuses non-loopback clients, limits input to 5 MB and image dimensions from 64 to 4096 pixels, verifies the model and MediaPipe SHA-256 fingerprints, and never writes the uploaded image to disk.

Every response permanently says:

- `mode: local_camera_validation_only`;
- `review_required: true`;
- `deployment_allowed: false`.

The output identifies one of `balasana`, `mayurasana`, `salamba_sirsasana`, `urdhva_dhanurasana` or `virasana`. It does not score correctness, safety, therapy needs or progression readiness.

## Start the service

From the project root, open a separate PowerShell terminal and run:

```powershell
powershell -ExecutionPolicy Bypass -File ai\start-inference-service.ps1
```

Keep that terminal open. Visit `http://127.0.0.1:8001/health`. The `pose_validation` object should report `ready: true`, `mode: local_camera_validation_only` and `deployment_allowed: false`.

To validate one existing local image without saving another copy:

```powershell
$imagePath = 'C:\full\path\to\one-pose-image.jpg'
Invoke-RestMethod `
    -Method Post `
    -Uri 'http://127.0.0.1:8001/v1/pose/validate' `
    -ContentType 'image/jpeg' `
    -InFile $imagePath
```

Use `image/png` for a PNG and `image/webp` for a WebP file. A low-confidence, low-visibility or undetected pose returns `validation_accepted: false`; it is not forced into a trustworthy result.

## Prepare consented local-camera evidence

Use participant pseudonyms such as `P001`; never use names, email addresses or membership numbers in filenames. After a participant has been given an appropriate consent explanation, create their five folders:

```powershell
powershell -ExecutionPolicy Bypass -File ai\setup-camera-validation.ps1 -ParticipantId P001
```

The script creates this ignored structure:

```text
ai/data/pose_camera_validation/
├── participants.csv
└── P001/
    ├── balasana/
    ├── mayurasana/
    ├── salamba_sirsasana/
    ├── urdhva_dhanurasana/
    └── virasana/
```

`participants.csv` begins with `consent_confirmed` set to `no`. Change it to `yes` only after genuine consent. The evaluator refuses to process an unlisted participant or any participant whose value is still `no`.

Put each image into its known pose-identity folder. These folder names are identity labels only; do not call them correct-form labels. Use the same camera position the eventual application is expected to use, keep the whole body visible, and collect different lighting, clothing and background conditions.

For the assignment-level local evidence gate, target at least:

- 10 consented participants;
- 300 total images;
- all five poses for every participant;
- more than one recording session or lighting condition;
- at least 90% landmark-detection coverage;
- at least 80% detected-image macro F1;
- at least 70% detected-image recall for each pose.

These are engineering validation gates, not proof of medical safety or client-ready quality.

## Run the offline evaluation

After adding consented images, run:

```powershell
.\.venv\Scripts\python.exe -m ai.pose.camera_validation
```

The ignored outputs are:

- `ai/data/pose_camera_validation_results.csv` — one pseudonymous result per image;
- `ai/artifacts/pose_camera_validation_report.json` — overall, per-participant, per-class and evidence-gate results.

The report remains `deployment_allowed: false` even when the assignment thresholds pass. A separate human review is required before any member-facing design is considered.

## Privacy and safety rules

- Keep camera images inside the ignored `ai/data` directory.
- Never commit or push participant images or evaluation CSVs.
- Record only a pseudonym and consent state in this prototype.
- Do not collect medical conditions, therapy notes or names in this dataset.
- Do not expose Uvicorn on `0.0.0.0` or forward port 8001.
- Do not load downloaded or untrusted `joblib` files; Python serialization can execute code during loading.
