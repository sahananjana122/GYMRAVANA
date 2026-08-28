# Local progression-readiness inference service

## What this stage does

GymRAVANA now contains a small FastAPI service at `ai/service`. It is the future local boundary between Laravel and a genuinely trained Python model. It currently makes no real predictions because Notebook 03 has not produced a model artifact.

This separation is intentional:

- Laravel continues to manage users, permissions, training records and Master Gate decisions;
- Python loads the reviewed scikit-learn pipeline and calculates a probability;
- the model remains advisory and never grants Master Gate access by itself.

## Starting the service

First complete the one-time environment setup from the project root:

```powershell
powershell -ExecutionPolicy Bypass -File ai\setup-environment.ps1
```

Then start the local service in a separate PowerShell terminal:

```powershell
powershell -ExecutionPolicy Bypass -File ai\start-inference-service.ps1
```

The script binds to `127.0.0.1:8001`. Keep that terminal running while testing. Do not change it to `0.0.0.0` or expose it to the internet; this is a local undergraduate prototype, not a production API.

Open `http://127.0.0.1:8001/health` to inspect its status. With the current empty dataset the correct response says that the service is available while the model is unavailable. The prediction endpoint is `POST /v1/readiness/predict`, but it returns HTTP `503` until every reviewed Notebook 03 artifact exists and passes validation.

The same local process now reports a separate `pose_validation` health object and provides `POST /v1/pose/validate`. The pose boundary is usable only for offline local-camera evaluation and remains disconnected from member routes. It does not alter the unavailable progression-readiness model. See [the local pose-validation guide](pose-local-validation.md) for its image limits, artifact checks, consent structure and evaluation command.

## Safety checks

The service requires the complete ignored package:

- `readiness_model.joblib`;
- `feature_schema.json`;
- `model_metrics.json`;
- `model_metadata.json`;
- `feature_importance.json`.

Before loading the pipeline it verifies the feature order, artifact/schema versions, scikit-learn version, held-out and grouped cross-validation evidence, training-dataset fingerprint presence, serialized-model SHA-256 fingerprint and explanation data. Missing, stale, altered or incompatible artifacts leave the model unavailable.

`joblib` files use Python pickle internally. Never download an unknown model file or copy an artifact package from an untrusted person into `ai/artifacts`. The fingerprint detects accidental mismatch; it is not a digital signature proving who created a file. Only use artifacts produced locally by the reviewed Notebook 03 workflow.

Requests accept exactly the 14 approved behavioral fields. Names, IDs, contact information, medical/therapy data, photographs, body measurements and free text are rejected or absent from the contract.

## Laravel configuration and client

Laravel now has two reusable internal services:

- `ReadinessFeatureService` calculates the same 14-feature snapshot used by the training exporter;
- `ReadinessInferenceClient` sends only that exact contract and converts a valid API response into a typed result.

The client is disabled by default. The example environment values are:

```dotenv
AI_INFERENCE_ENABLED=false
AI_INFERENCE_URL=http://127.0.0.1:8001
AI_INFERENCE_CONNECT_TIMEOUT=1
AI_INFERENCE_TIMEOUT=3
```

Do not enable it merely because the service starts. Enable it only after Notebook 03 has produced and you have reviewed a real artifact package. The client accepts only plain HTTP loopback hosts (`127.0.0.1`, `localhost` or IPv6 loopback); it refuses an external URL before sending the snapshot.

Offline, disabled, missing-model, malformed-response and feature-contract failures return an unavailable result rather than throwing an error into the member dashboard. Model version length, probability, threshold and global explanation fields are checked before later code can store them.

## Testing

Run the service tests from the project root:

```powershell
.\.venv\Scripts\python.exe -m unittest discover -s ai\service\tests -v
```

The successful-prediction test creates a temporary, clearly identified software fixture outside `ai/artifacts`. It tests the API boundary only and must never be described as a trained GymRAVANA model.

## Admin-controlled prediction workflow

Administrators use **Progression → AI Data Readiness** to see service/model health and members who have a genuine trainer readiness assessment. **Generate prediction** is enabled only when the localhost health response confirms that a reviewed model is ready.

The server repeats every safety check even if someone manually sends the protected POST request. It requires a member account and genuine trainer assessment, calculates the shared 14-feature snapshot, and calls the loopback-only client. Disabled, offline, missing-model or invalid responses create no database row and leave Master Gate as `Not evaluated`.

A valid response creates a private `progression_readiness_predictions` row containing the model version, observation month, result, probability, exact feature snapshot, structured global explanation, prediction time and SHA-256 input fingerprint. The fingerprint includes the observation month, trainer-review context and ordered features. Repeating an identical request for the same model reuses the existing row instead of creating misleading duplicates.

The workflow is implemented but currently unavailable because no genuine artifact exists and `AI_INFERENCE_ENABLED` remains false. A prediction is advisory evidence only; it never approves Master Gate automatically.
