from dataclasses import dataclass
from hashlib import sha256
from pathlib import Path
from typing import Any
import json

import joblib
import pandas as pd
import sklearn


EXPECTED_FEATURES = (
    "workout_completions",
    "wellness_completions",
    "trainer_sessions_scheduled",
    "trainer_sessions_completed",
    "attendance_rate",
    "cancelled_or_declined_sessions",
    "active_days",
    "consistency_rate",
    "activity_points",
    "previous_goal_completion",
    "previous_rating",
    "workout_change",
    "consistency_change",
    "previous_assessment",
)

REQUIRED_ARTIFACTS = (
    "readiness_model.joblib",
    "feature_schema.json",
    "model_metrics.json",
    "model_metadata.json",
    "feature_importance.json",
)


class ArtifactUnavailable(RuntimeError):
    """Raised when the reviewed Notebook 03 artifact package is unavailable or unsafe."""


@dataclass(frozen=True)
class LoadedArtifacts:
    model: Any
    model_version: str
    threshold: float
    global_importance: list[dict[str, Any]]
    metadata: dict[str, Any]


class ArtifactRegistry:
    def __init__(self, directory: Path) -> None:
        self.directory = directory.resolve()

    def status(self) -> dict[str, Any]:
        missing = [name for name in REQUIRED_ARTIFACTS if not (self.directory / name).is_file()]
        if missing:
            return {
                "ready": False,
                "reason": "A complete reviewed Notebook 03 artifact package is not available.",
                "missing_files": missing,
                "model_version": None,
            }

        try:
            loaded = self.load()
        except ArtifactUnavailable as exception:
            return {
                "ready": False,
                "reason": str(exception),
                "missing_files": [],
                "model_version": None,
            }

        return {
            "ready": True,
            "reason": None,
            "missing_files": [],
            "model_version": loaded.model_version,
        }

    def load(self) -> LoadedArtifacts:
        missing = [name for name in REQUIRED_ARTIFACTS if not (self.directory / name).is_file()]
        if missing:
            raise ArtifactUnavailable(
                "Missing required artifact files: " + ", ".join(missing)
            )

        schema = self._json("feature_schema.json")
        metadata = self._json("model_metadata.json")
        metrics = self._json("model_metrics.json")
        importance = self._json("feature_importance.json")
        model_path = self.directory / "readiness_model.joblib"

        if schema.get("schema_version") != 1:
            raise ArtifactUnavailable("Unsupported feature-schema version.")
        if tuple(schema.get("feature_order", [])) != EXPECTED_FEATURES:
            raise ArtifactUnavailable("Artifact feature order does not match the service contract.")
        if metadata.get("artifact_version") != 1:
            raise ArtifactUnavailable("Unsupported model-artifact version.")
        if metadata.get("scikit_learn_version") != sklearn.__version__:
            raise ArtifactUnavailable("Model and service scikit-learn versions do not match.")
        if not metadata.get("dataset_sha256"):
            raise ArtifactUnavailable("Model metadata has no training-dataset fingerprint.")
        if not metrics.get("holdout_results") or not metrics.get("cross_validation_results"):
            raise ArtifactUnavailable("Reviewed evaluation metrics are incomplete.")

        actual_model_hash = sha256(model_path.read_bytes()).hexdigest()
        if metadata.get("model_sha256") != actual_model_hash:
            raise ArtifactUnavailable("Serialized-model fingerprint does not match its metadata.")

        global_importance = importance.get("global_permutation_importance")
        if not isinstance(global_importance, list) or not global_importance:
            raise ArtifactUnavailable("Model explanation artifact is incomplete.")

        try:
            model = joblib.load(model_path)
        except Exception as exception:
            raise ArtifactUnavailable("Serialized model could not be loaded safely.") from exception
        if not callable(getattr(model, "predict_proba", None)):
            raise ArtifactUnavailable("Serialized model does not support probability prediction.")

        model_name = str(metadata.get("model_name", "unknown"))
        threshold = float(metadata.get("decision_threshold", 0.5))
        if not 0 <= threshold <= 1:
            raise ArtifactUnavailable("Model decision threshold is invalid.")

        return LoadedArtifacts(
            model=model,
            model_version=f"{model_name}-{actual_model_hash[:12]}",
            threshold=threshold,
            global_importance=global_importance,
            metadata=metadata,
        )

    def predict(self, features: dict[str, Any]) -> dict[str, Any]:
        artifacts = self.load()
        ordered = pd.DataFrame([[features[name] for name in EXPECTED_FEATURES]], columns=EXPECTED_FEATURES)

        try:
            probability = float(artifacts.model.predict_proba(ordered)[0, 1])
        except Exception as exception:
            raise ArtifactUnavailable("The reviewed model could not evaluate this feature row.") from exception
        if not 0 <= probability <= 1:
            raise ArtifactUnavailable("The model returned an invalid probability.")

        top_factors = sorted(
            artifacts.global_importance,
            key=lambda item: abs(float(item.get("importance_mean", 0))),
            reverse=True,
        )[:5]

        return {
            "model_version": artifacts.model_version,
            "predicted_ready": probability >= artifacts.threshold,
            "readiness_probability": round(probability, 5),
            "decision_threshold": artifacts.threshold,
            "explanation": [
                {
                    "feature": str(item.get("feature", "unknown")),
                    "global_permutation_importance": round(float(item.get("importance_mean", 0)), 6),
                }
                for item in top_factors
            ],
            "disclaimer": "Advisory non-medical model output. A trainer or Master must make the final decision.",
        }

    def _json(self, name: str) -> dict[str, Any]:
        try:
            document = json.loads((self.directory / name).read_text(encoding="utf-8"))
        except (OSError, UnicodeError, json.JSONDecodeError) as exception:
            raise ArtifactUnavailable(f"{name} is unreadable or invalid JSON.") from exception
        if not isinstance(document, dict):
            raise ArtifactUnavailable(f"{name} must contain a JSON object.")

        return document
