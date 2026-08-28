from hashlib import sha256
from pathlib import Path
from tempfile import TemporaryDirectory
import json
import unittest

import joblib
import numpy as np
import sklearn
from fastapi.testclient import TestClient

from ai.service.artifacts import EXPECTED_FEATURES
from ai.service.main import create_app


VALID_FEATURES = {
    "workout_completions": 12,
    "wellness_completions": 5,
    "trainer_sessions_scheduled": 4,
    "trainer_sessions_completed": 3,
    "attendance_rate": 0.75,
    "cancelled_or_declined_sessions": 1,
    "active_days": 14,
    "consistency_rate": 0.45,
    "activity_points": 290,
    "previous_goal_completion": 80,
    "previous_rating": 4,
    "workout_change": 2,
    "consistency_change": 0.08,
    "previous_assessment": "on_track",
}


class DummyReadinessModel:
    """Software-test fixture only; never written to GymRAVANA's artifact directory."""

    def predict_proba(self, frame):
        return np.array([[0.2, 0.8] for _ in range(len(frame))])


class InferenceServiceTest(unittest.TestCase):
    def test_health_is_available_but_model_is_unavailable_without_artifacts(self):
        with TemporaryDirectory() as directory:
            response = TestClient(create_app(Path(directory))).get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("available", response.json()["service"])
        self.assertEqual("unavailable", response.json()["model"])
        self.assertIn("readiness_model.joblib", response.json()["missing_files"])

    def test_prediction_fails_closed_without_reviewed_artifacts(self):
        with TemporaryDirectory() as directory:
            response = TestClient(create_app(Path(directory))).post(
                "/v1/readiness/predict",
                json=VALID_FEATURES,
            )

        self.assertEqual(503, response.status_code)
        self.assertEqual("model_unavailable", response.json()["detail"]["code"])

    def test_request_contract_rejects_unknown_or_invalid_features(self):
        with TemporaryDirectory() as directory:
            client = TestClient(create_app(Path(directory)))
            invalid = {**VALID_FEATURES, "attendance_rate": 1.2, "member_name": "Private Name"}
            response = client.post("/v1/readiness/predict", json=invalid)

        self.assertEqual(422, response.status_code)
        error_types = {error["type"] for error in response.json()["detail"]}
        self.assertIn("less_than_equal", error_types)
        self.assertIn("extra_forbidden", error_types)

    def test_prediction_uses_a_complete_fingerprinted_test_artifact_package(self):
        with TemporaryDirectory() as directory:
            artifact_directory = Path(directory)
            self.write_test_artifacts(artifact_directory)
            client = TestClient(create_app(artifact_directory))

            health = client.get("/health")
            response = client.post("/v1/readiness/predict", json=VALID_FEATURES)

        self.assertEqual("ready", health.json()["model"])
        self.assertEqual(200, response.status_code)
        self.assertTrue(response.json()["predicted_ready"])
        self.assertEqual(0.8, response.json()["readiness_probability"])
        self.assertEqual("workout_completions", response.json()["explanation"][0]["feature"])
        self.assertNotIn("member_name", response.json())

    def test_health_rejects_a_model_fingerprint_mismatch(self):
        with TemporaryDirectory() as directory:
            artifact_directory = Path(directory)
            self.write_test_artifacts(artifact_directory)
            metadata_path = artifact_directory / "model_metadata.json"
            metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
            metadata["model_sha256"] = "not-the-model-fingerprint"
            self.write_json(metadata_path, metadata)

            response = TestClient(create_app(artifact_directory)).get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("unavailable", response.json()["model"])
        self.assertIn("fingerprint", response.json()["reason"])

    def write_test_artifacts(self, directory: Path) -> None:
        model_path = directory / "readiness_model.joblib"
        joblib.dump(DummyReadinessModel(), model_path)
        model_hash = sha256(model_path.read_bytes()).hexdigest()
        self.write_json(directory / "feature_schema.json", {
            "schema_version": 1,
            "feature_order": list(EXPECTED_FEATURES),
        })
        self.write_json(directory / "model_metrics.json", {
            "holdout_results": [{"model": "test_fixture", "f1": 0.8}],
            "cross_validation_results": [{"model": "test_fixture", "mean_f1": 0.75}],
        })
        self.write_json(directory / "model_metadata.json", {
            "artifact_version": 1,
            "model_name": "test-fixture",
            "model_sha256": model_hash,
            "dataset_sha256": "fixture-dataset-hash",
            "decision_threshold": 0.5,
            "scikit_learn_version": sklearn.__version__,
        })
        self.write_json(directory / "feature_importance.json", {
            "global_permutation_importance": [
                {"feature": feature, "importance_mean": 1 - index / 100}
                for index, feature in enumerate(EXPECTED_FEATURES)
            ],
            "local_examples": [],
        })

    def write_json(self, path: Path, value: dict) -> None:
        path.write_text(json.dumps(value), encoding="utf-8")


if __name__ == "__main__":
    unittest.main()
