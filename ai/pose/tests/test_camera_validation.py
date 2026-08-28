from __future__ import annotations

import tempfile
import unittest
from pathlib import Path

from ai.pose.camera_validation import evaluate_camera_directory


class FakeValidationRegistry:
    def validate_image(self, image_bytes: bytes, media_type: str):
        return {
            "model_version": "fixture-123",
            "landmarks_detected": True,
            "validation_accepted": True,
            "quality_reason": "accepted_for_offline_validation",
            "predicted_pose": "balasana",
            "confidence": 0.8,
            "core_visibility": 0.9,
        }


class CameraValidationTest(unittest.TestCase):
    def test_consent_required_camera_report_uses_pseudonymous_participants(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            dataset = root / "dataset"
            image_directory = dataset / "P001" / "balasana"
            image_directory.mkdir(parents=True)
            (dataset / "participants.csv").write_text(
                "participant_id,consent_confirmed\nP001,yes\n",
                encoding="utf-8",
            )
            (image_directory / "capture_001.jpg").write_bytes(b"fixture")

            report = evaluate_camera_directory(
                dataset,
                FakeValidationRegistry(),
                root / "results.csv",
                root / "report.json",
            )

            self.assertEqual(1, report["metrics"]["image_count"])
            self.assertEqual(1, report["metrics"]["participant_count"])
            self.assertEqual(1.0, report["metrics"]["prediction_accuracy_when_detected"])
            self.assertFalse(report["local_camera_evidence_passed"])
            self.assertFalse(report["deployment_allowed"])
            self.assertTrue((root / "results.csv").is_file())
            self.assertTrue((root / "report.json").is_file())

    def test_camera_images_are_not_processed_without_consent_manifest(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            dataset = Path(temporary_directory)
            (dataset / "P001" / "balasana").mkdir(parents=True)

            with self.assertRaisesRegex(ValueError, "participants.csv is required"):
                evaluate_camera_directory(
                    dataset,
                    FakeValidationRegistry(),
                    dataset / "results.csv",
                    dataset / "report.json",
                )


if __name__ == "__main__":
    unittest.main()
