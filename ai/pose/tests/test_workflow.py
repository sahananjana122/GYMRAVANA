import math
import tempfile
import unittest
import zipfile
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

from ai.pose.download_public_subset import DATASET_HANDLE, SOURCE_DIRECTORIES, download
from ai.pose.extract_public_subset import extract_subset
from ai.pose.public_workflow import EXPECTED_CLASSES, hamming_distance
from ai.pose.workflow import (
    EXCLUDED_POSES,
    FEATURE_NAMES,
    MINIMUM_DEPLOYMENT_ROWS,
    MINIMUM_DEPLOYMENT_SOURCES,
    POSE_ALIASES,
    landmark_features,
)


class PoseWorkflowTest(unittest.TestCase):
    def test_landmark_feature_extraction_matches_the_versioned_contract(self) -> None:
        landmarks = [
            SimpleNamespace(
                x=(index % 6) / 5,
                y=(index // 6) / 5,
                z=0.0,
                visibility=0.8 + ((index % 3) * 0.05),
            )
            for index in range(33)
        ]

        features = landmark_features(landmarks)

        self.assertEqual(FEATURE_NAMES, list(features))
        self.assertTrue(all(math.isfinite(value) for value in features.values()))
        self.assertGreaterEqual(features["visibility_mean"], 0.0)
        self.assertLessEqual(features["visibility_mean"], 1.0)

    def test_conflicting_santulanasana_is_not_mapped_to_a_training_class(self) -> None:
        self.assertIn("santulanasana", EXCLUDED_POSES)
        self.assertNotIn("santulanasana", POSE_ALIASES)
        self.assertEqual(5, len(POSE_ALIASES))

    def test_starter_evidence_thresholds_cannot_be_mistaken_for_deployment_gates(self) -> None:
        self.assertGreaterEqual(MINIMUM_DEPLOYMENT_ROWS, 250)
        self.assertGreaterEqual(MINIMUM_DEPLOYMENT_SOURCES, 10)

    def test_public_pose_hash_contract_is_stable(self) -> None:
        self.assertEqual(0, hamming_distance("0123456789abcdef", "0123456789abcdef"))
        self.assertEqual(64, hamming_distance("0000000000000000", "ffffffffffffffff"))
        self.assertEqual(
            ["balasana", "mayurasana", "salamba_sirsasana", "urdhva_dhanurasana", "virasana"],
            EXPECTED_CLASSES,
        )

    def test_public_downloader_uses_supported_archive_download_and_verifies_classes(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            destination = Path(temporary_directory)
            for source_directory in SOURCE_DIRECTORIES.values():
                (destination / "dataset" / source_directory).mkdir(parents=True)

            with patch(
                "ai.pose.download_public_subset.kagglehub.dataset_download",
                return_value=str(destination),
            ) as dataset_download:
                download(destination)

            dataset_download.assert_called_once_with(
                DATASET_HANDLE,
                output_dir=str(destination),
            )

    def test_public_archive_extractor_selects_only_the_five_canonical_classes(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            root = Path(temporary_directory)
            archive_path = root / "dataset.zip"
            output_directory = root / "output"
            with zipfile.ZipFile(archive_path, "w") as archive:
                for canonical_class, source_class in SOURCE_DIRECTORIES.items():
                    archive.writestr(f"dataset/{source_class}/sample.png", b"image")
                archive.writestr("dataset/unrelated_pose/sample.png", b"unrelated")

            manifest = extract_subset(archive_path, output_directory)

            self.assertEqual(5, manifest["image_count"])
            self.assertEqual({name: 1 for name in SOURCE_DIRECTORIES}, manifest["class_counts"])
            self.assertFalse((output_directory / "unrelated_pose").exists())
            self.assertTrue((output_directory / "extraction_manifest.json").is_file())


if __name__ == "__main__":
    unittest.main()
