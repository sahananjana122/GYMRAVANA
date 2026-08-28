# GymRAVANA local AI workspace

This directory is reserved for the final, locally trained progression-readiness prototype.

## Current stage

The privacy-safe exporter, three guarded notebooks, dataset-integrity contract, fail-closed local inference service and administrator-controlled Laravel prediction boundary exist. Notebook 03 is ready to explain and export a candidate only after Notebook 02 produces a valid selection report from sufficient genuine evidence. There is currently no trained model or active AI Master Gate prediction.

## Collect genuine labels first

Open **Admin → AI Data Readiness** to see the live collection pipeline. A member account does not become training evidence automatically. The required path is:

1. a member requests a genuine session from the trainer directory;
2. the trainer or administrator accepts and schedules the request;
3. the assigned trainer records an honest monthly readiness decision in **Trainer → Monthly Tracker**;
4. the readiness page counts that timestamped decision toward the guarded dataset gates.

Pending requests and ordinary goals or notes do not count as labels. The readiness page is monitoring-only and cannot manufacture relationships or outcomes.

## One-time Python setup

GymRAVANA uses Python 3.12 in a project-local `.venv`. This keeps the AI packages separate from Laravel, XAMPP and other Python projects. The `.venv` directory is ignored by Git and must not be committed.

Open PowerShell in the `ravana-app` directory and run:

```powershell
powershell -ExecutionPolicy Bypass -File ai\setup-environment.ps1
```

The script creates `.venv` when needed and installs the exact tested packages from `ai/requirements-lock.txt`. `ai/requirements.txt` lists only the direct notebook, model-serialization and local-service dependencies so it is easier to understand what this project intentionally uses.

The tested environment is Python 3.12.10, JupyterLab 4.6.3, pandas 3.0.5, scikit-learn 1.9.0, FastAPI 0.141.1 and Uvicorn 0.52.4.

Do not activate `.venv` globally or install these packages into XAMPP. PHP and Python are separate runtimes in this project.

## Prepare and open Notebook 01

Generate the privacy-safe dataset from the Laravel project root:

```powershell
php artisan gymravana:export-readiness-data
```

This creates ignored local files:

- `ai/data/readiness_dataset.csv`
- `ai/data/readiness_dataset.metadata.json`

The metadata records schema version `1`, the expected columns and target, the row and class counts, and a SHA-256 fingerprint of the exact CSV bytes. Do not edit either generated file by hand or combine a CSV with metadata from a different export. All three notebooks reject missing, altered or incompatible pairs before analysis, training or export.

The exporter includes only trainer-labeled behavioral rows. It excludes names, contact details, medical and therapy information, photographs, body measurements, and free-text notes.

Start JupyterLab from the same project directory:

```powershell
powershell -ExecutionPolicy Bypass -File ai\start-jupyter.ps1
```

Your browser will open JupyterLab. In its file list, double-click `01_data_preparation_and_eda.ipynb`, then use **Run → Run All Cells**. The notebook reads the ignored CSV generated above; it does not connect directly to the live database.

The notebook checks the dataset honestly. It will stop before train/test preparation when labels are empty, contain only one class, or do not contain enough member groups for a leakage-safe split.

Notebook 02 is available at `ai/notebooks/02_model_training_and_evaluation.ipynb`. It defines reproducible Logistic Regression and Random Forest comparisons, but trains them only after stricter row, class and member-group gates pass. All three notebooks reject contradictory targets for the same pseudonymous member and observation month. With an empty or insufficient export, Notebook 02 reports the missing evidence and performs no fitting, metric claim or model-selection report.

Notebook 03 is available at `ai/notebooks/03_model_explainability_and_export.ipynb`. When Notebook 02 has defensible held-out and grouped cross-validation results, it writes an ignored `model_selection.json` report bound to the exact CSV fingerprint. Notebook 03 verifies that handoff, reconstructs the deterministic grouped holdout, calculates model-agnostic permutation importance and anonymized local sensitivity examples, records limitations, retrains the reviewed candidate on all eligible rows, and exports the ignored local artifact package. With the current empty database, it exits successfully and writes no model files.

The possible final files are `readiness_model.joblib`, `feature_schema.json`, `model_metrics.json`, `model_metadata.json`, and `feature_importance.json`. Their existence still does not activate predictions in Laravel.

## Local inference-service foundation

Start the fail-closed service in a separate PowerShell terminal:

```powershell
powershell -ExecutionPolicy Bypass -File ai\start-inference-service.ps1
```

It listens only on `http://127.0.0.1:8001`. The `/health` endpoint is available now, but it reports the model as unavailable and `/v1/readiness/predict` returns HTTP `503` until the complete reviewed artifact package passes every compatibility and fingerprint check.

Laravel now contains a disabled-by-default, loopback-only client and a shared feature generator. The training CSV and live request therefore use one PHP calculation path for the same 14 non-medical fields. Administrators can request a prediction from **AI Data Readiness** only when service health confirms a reviewed model; valid results are stored privately with an input fingerprint and identical requests are idempotent. With no current artifact, the action remains unavailable and stores nothing. See [the local inference-service guide](../docs/ai/local-inference-service.md) for configuration, the endpoint contract, tests and important `joblib` trust warning.

No reviewed Kaggle or Hugging Face dataset has an equivalent readiness target and acceptable feature set. The documented candidate review is in `docs/ai/public-dataset-suitability.md`; unrelated or synthetic targets are not relabeled to manufacture model results.

Generated datasets and future model artifacts are ignored by Git. Do not commit private member-derived data or serialized models accidentally.

## Separate pose-recognition experiment

The user-supplied pose starter has now been audited and used in three additional executed notebooks:

- `04_pose_data_preparation.ipynb`;
- `05_pose_model_training_and_evaluation.ipynb`;
- `06_pose_explainability_and_prototype_export.ipynb`.

This is a separate five-class pose-identity prototype based on MediaPipe landmarks. It is not a replacement dataset for progression readiness and is not a form-correction model. The 15-image starter established the pipeline, after which the manually downloaded Yoga-107 archive supplied 300 relevant images. MediaPipe produced 271 valid landmark rows and the duplicate-grouped experiment is executed in `07_public_pose_retraining.ipynb`. Its local artifact remains `prototype_only` and `deployment_allowed: false` because the web data has no participant IDs, trainer-verified form labels or local-camera test. See [the pose prototype audit](../docs/ai/pose-prototype.md) for the complete results and evidence boundary.

The local service now exposes the trained artifact only through `POST /v1/pose/validate`, a loopback-only offline-validation endpoint. It verifies the model and MediaPipe fingerprints, accepts a bounded raw image without storing it, reports confidence/visibility quality failures and always returns `review_required: true` plus `deployment_allowed: false`. The consent-gated batch evaluator at `ai/pose/camera_validation.py` measures a pseudonymous local camera dataset without connecting pose output to Laravel members. Follow [the local pose-validation guide](../docs/ai/pose-local-validation.md) one participant at a time.
