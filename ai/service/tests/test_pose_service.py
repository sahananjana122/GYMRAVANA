from __future__ import annotations

import json
import unittest
from io import BytesIO
from hashlib import sha256
from pathlib import Path
from tempfile import TemporaryDirectory

import joblib
import numpy as np
import sklearn
from fastapi.testclient import TestClient
from PIL import Image

from ai.pose.public_workflow import EXPECTED_CLASSES, PUBLIC_TASK
from ai.pose.workflow import FEATURE_NAMES
from ai.service.main import create_app
from ai.service.pose_artifacts import MAX_IMAGE_BYTES, PoseValidationRegistry


class DummyPoseModel:
    """Boundary-test fixture; it is never written to GymRAVANA's artifact directory."""

    classes_ = np.array(EXPECTED_CLASSES)

    def predict(self, frame):
        return np.array([EXPECTED_CLASSES[0] for _ in range(len(frame))])

    def predict_proba(self, frame):
        return np.array([[0.7, 0.1, 0.05, 0.1, 0.05] for _ in range(len(frame))])


class FakePoseRegistry:
    def status(self):
        return {
            "ready": True,
            "mode": "local_camera_validation_only",
            "reason": None,
            "missing_files": [],
            "model_version": "fixture-123",
            "deployment_allowed": False,
        }

    def validate_image(self, image_bytes: bytes, media_type: str):
        return {
            "mode": "local_camera_validation_only",
            "model_version": "fixture-123",
            "landmarks_detected": True,
            "validation_accepted": True,
            "quality_reason": "accepted_for_offline_validation",
            "predicted_pose": "balasana",
            "confidence": 0.7,
            "class_probabilities": {name: (0.7 if name == "balasana" else 0.075) for name in EXPECTED_CLASSES},
            "core_visibility": 0.9,
            "image_width": 640,
            "image_height": 480,
            "explanation": [{"feature": "torso_angle_deg", "global_permutation_importance": 0.07}],
            "review_required": True,
            "deployment_allowed": False,
            "disclaimer": "Offline pose-identity validation only.",
        }


class PoseServiceTest(unittest.TestCase):
    def test_health_reports_pose_validation_separately_from_readiness(self):
        with TemporaryDirectory() as directory:
            response = TestClient(create_app(Path(directory), pose_registry=FakePoseRegistry())).get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("unavailable", response.json()["model"])
        self.assertTrue(response.json()["pose_validation"]["ready"])
        self.assertFalse(response.json()["pose_validation"]["deployment_allowed"])

    def test_pose_endpoint_accepts_raw_local_validation_image_contract(self):
        with TemporaryDirectory() as directory:
            response = TestClient(create_app(Path(directory), pose_registry=FakePoseRegistry())).post(
                "/v1/pose/validate",
                content=b"fixture-image",
                headers={"Content-Type": "image/jpeg"},
            )

        self.assertEqual(200, response.status_code)
        self.assertEqual("balasana", response.json()["predicted_pose"])
        self.assertTrue(response.json()["review_required"])
        self.assertFalse(response.json()["deployment_allowed"])

    def test_pose_endpoint_rejects_unknown_media_and_oversized_payloads_before_inference(self):
        with TemporaryDirectory() as directory:
            client = TestClient(create_app(Path(directory), pose_registry=FakePoseRegistry()))
            unknown = client.post(
                "/v1/pose/validate",
                content=b"not-an-image",
                headers={"Content-Type": "application/octet-stream"},
            )
            oversized = client.post(
                "/v1/pose/validate",
                content=b"x" * (MAX_IMAGE_BYTES + 1),
                headers={"Content-Type": "image/png"},
            )

        self.assertEqual(415, unknown.status_code)
        self.assertEqual(413, oversized.status_code)

    def test_pose_registry_requires_fingerprinted_validation_only_artifacts(self):
        with TemporaryDirectory() as directory:
            root = Path(directory)
            landmarker_path = self.write_test_artifacts(root)
            registry = PoseValidationRegistry(root, landmarker_path)

            status = registry.status()
            loaded = registry.load()

        self.assertTrue(status["ready"])
        self.assertFalse(status["deployment_allowed"])
        self.assertEqual(EXPECTED_CLASSES, list(loaded.model.classes_))

    def test_pose_registry_rejects_a_model_fingerprint_mismatch(self):
        with TemporaryDirectory() as directory:
            root = Path(directory)
            landmarker_path = self.write_test_artifacts(root)
            metadata_path = root / "pose_identity_public_prototype.metadata.json"
            metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
            metadata["model_sha256"] = "invalid"
            metadata_path.write_text(json.dumps(metadata), encoding="utf-8")

            status = PoseValidationRegistry(root, landmarker_path).status()

        self.assertFalse(status["ready"])
        self.assertIn("fingerprint", status["reason"])

    def test_real_registry_rejects_unreadable_and_oversized_dimensions_before_landmark_inference(self):
        with TemporaryDirectory() as directory:
            root = Path(directory)
            landmarker_path = self.write_test_artifacts(root)
            client = TestClient(create_app(root, landmarker_path))
            unreadable = client.post(
                "/v1/pose/validate",
                content=b"not-a-readable-image",
                headers={"Content-Type": "image/png"},
            )
            image_stream = BytesIO()
            Image.new("RGB", (5000, 64)).save(image_stream, format="PNG")
            oversized_dimensions = client.post(
                "/v1/pose/validate",
                content=image_stream.getvalue(),
                headers={"Content-Type": "image/png"},
            )

        self.assertEqual(422, unreadable.status_code)
        self.assertEqual(422, oversized_dimensions.status_code)

    def write_test_artifacts(self, directory: Path) -> Path:
        model_path = directory / "pose_identity_public_prototype.joblib"
        joblib.dump(DummyPoseModel(), model_path)
        landmarker_path = directory / "pose_landmarker_lite.task"
        landmarker_path.write_bytes(b"test-landmarker-fixture")
        metadata = {
            "schema_version": 2,
            "task": PUBLIC_TASK,
            "prototype_only": True,
            "deployment_allowed": False,
            "model_name": "test-fixture",
            "classes": EXPECTED_CLASSES,
            "feature_names": FEATURE_NAMES,
            "training_rows": 271,
            "dataset_sha256": "fixture-dataset",
            "source_archive_sha256": "fixture-archive",
            "pose_landmarker_sha256": sha256(landmarker_path.read_bytes()).hexdigest(),
            "scikit_learn_version": sklearn.__version__,
            "model_sha256": sha256(model_path.read_bytes()).hexdigest(),
            "evidence_gates": {
                "minimum_250_landmark_rows": True,
                "minimum_10_visual_groups": True,
                "participant_ids_available": False,
                "trainer_verified_labels": False,
                "local_camera_test_available": False,
                "form_correctness_target_available": False,
            },
        }
        (directory / "pose_identity_public_prototype.metadata.json").write_text(
            json.dumps(metadata), encoding="utf-8"
        )
        (directory / "pose_identity_public_prototype.feature_importance.json").write_text(
            json.dumps(
                [
                    {"feature": feature, "mean_importance": 1 - index / 100, "std_importance": 0.01}
                    for index, feature in enumerate(FEATURE_NAMES)
                ]
            ),
            encoding="utf-8",
        )
        return landmarker_path


if __name__ == "__main__":
    unittest.main()
