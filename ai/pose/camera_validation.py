from __future__ import annotations

import argparse
import json
import mimetypes
from datetime import UTC, datetime
from pathlib import Path
from typing import Any

import pandas as pd
from sklearn.metrics import accuracy_score, f1_score

from ai.pose.public_workflow import EXPECTED_CLASSES
from ai.service.pose_artifacts import PoseArtifactUnavailable, PoseImageInvalid, PoseValidationRegistry

ALLOWED_SUFFIXES = {".jpeg", ".jpg", ".png", ".webp"}
CONSENT_FILE = "participants.csv"
CONSENT_COLUMNS = ["participant_id", "consent_confirmed"]


def _consented_participants(dataset_directory: Path) -> set[str]:
    consent_path = dataset_directory / CONSENT_FILE
    if not consent_path.is_file():
        raise ValueError(
            f"{CONSENT_FILE} is required with columns participant_id,consent_confirmed before local images are processed."
        )
    consent = pd.read_csv(consent_path, dtype=str).fillna("")
    if list(consent.columns) != CONSENT_COLUMNS:
        raise ValueError(f"{CONSENT_FILE} must contain exactly: {', '.join(CONSENT_COLUMNS)}")
    consent["participant_id"] = consent["participant_id"].str.strip()
    consent["consent_confirmed"] = consent["consent_confirmed"].str.strip().str.lower()
    if consent["participant_id"].eq("").any() or consent["participant_id"].duplicated().any():
        raise ValueError("Participant IDs must be non-empty and unique pseudonyms.")
    unsupported = set(consent["consent_confirmed"]) - {"yes", "no"}
    if unsupported:
        raise ValueError("consent_confirmed accepts only yes or no.")
    return set(consent.loc[consent["consent_confirmed"] == "yes", "participant_id"])


def _image_records(dataset_directory: Path, participants: set[str]) -> list[dict[str, Any]]:
    records: list[dict[str, Any]] = []
    for participant_directory in sorted(path for path in dataset_directory.iterdir() if path.is_dir()):
        participant_id = participant_directory.name
        if participant_id not in participants:
            if any(participant_directory.rglob("*")):
                raise ValueError(f"Images exist for {participant_id}, but affirmative consent is not recorded.")
            continue
        for pose_directory in sorted(path for path in participant_directory.iterdir() if path.is_dir()):
            if pose_directory.name not in EXPECTED_CLASSES:
                raise ValueError(f"Unknown pose folder for {participant_id}: {pose_directory.name}")
            for image_path in sorted(path for path in pose_directory.iterdir() if path.is_file()):
                if image_path.suffix.lower() not in ALLOWED_SUFFIXES:
                    raise ValueError(f"Unsupported camera-validation file: {image_path.name}")
                if image_path.is_symlink():
                    raise ValueError(f"Symbolic links are not accepted: {image_path.name}")
                media_type = mimetypes.guess_type(image_path.name)[0]
                if media_type == "image/jpg":
                    media_type = "image/jpeg"
                records.append(
                    {
                        "participant_id": participant_id,
                        "actual_pose": pose_directory.name,
                        "relative_path": image_path.relative_to(dataset_directory).as_posix(),
                        "path": image_path,
                        "media_type": media_type,
                    }
                )
    if not records:
        raise ValueError("No consented camera-validation images were found.")
    return records


def evaluate_camera_directory(
    dataset_directory: Path,
    registry: PoseValidationRegistry,
    results_csv: Path,
    report_json: Path,
) -> dict[str, Any]:
    dataset_directory = dataset_directory.resolve()
    participants = _consented_participants(dataset_directory)
    images = _image_records(dataset_directory, participants)
    rows: list[dict[str, Any]] = []

    for image in images:
        try:
            result = registry.validate_image(image["path"].read_bytes(), str(image["media_type"]))
            rows.append(
                {
                    "participant_id": image["participant_id"],
                    "actual_pose": image["actual_pose"],
                    "relative_path": image["relative_path"],
                    "landmarks_detected": result["landmarks_detected"],
                    "validation_accepted": result["validation_accepted"],
                    "quality_reason": result["quality_reason"],
                    "predicted_pose": result["predicted_pose"],
                    "confidence": result["confidence"],
                    "core_visibility": result["core_visibility"],
                    "model_version": result["model_version"],
                    "error": None,
                }
            )
        except (PoseImageInvalid, PoseArtifactUnavailable, OSError) as exception:
            rows.append(
                {
                    "participant_id": image["participant_id"],
                    "actual_pose": image["actual_pose"],
                    "relative_path": image["relative_path"],
                    "landmarks_detected": False,
                    "validation_accepted": False,
                    "quality_reason": "processing_error",
                    "predicted_pose": None,
                    "confidence": None,
                    "core_visibility": None,
                    "model_version": None,
                    "error": str(exception),
                }
            )

    frame = pd.DataFrame(rows)
    predicted = frame[frame["predicted_pose"].notna()].copy()
    accepted = frame[frame["validation_accepted"]].copy()
    metrics = {
        "image_count": len(frame),
        "participant_count": int(frame["participant_id"].nunique()),
        "landmark_detection_rate": round(float(frame["landmarks_detected"].mean()), 6),
        "validation_acceptance_rate": round(float(frame["validation_accepted"].mean()), 6),
        "prediction_accuracy_when_detected": (
            round(float(accuracy_score(predicted["actual_pose"], predicted["predicted_pose"])), 6)
            if not predicted.empty
            else None
        ),
        "prediction_macro_f1_when_detected": (
            round(
                float(
                    f1_score(
                        predicted["actual_pose"],
                        predicted["predicted_pose"],
                        labels=EXPECTED_CLASSES,
                        average="macro",
                        zero_division=0,
                    )
                ),
                6,
            )
            if not predicted.empty
            else None
        ),
        "accepted_prediction_accuracy": (
            round(float(accuracy_score(accepted["actual_pose"], accepted["predicted_pose"])), 6)
            if not accepted.empty
            else None
        ),
    }
    participant_results = []
    for participant_id, group in frame.groupby("participant_id", sort=True):
        participant_predicted = group[group["predicted_pose"].notna()]
        participant_results.append(
            {
                "participant_id": participant_id,
                "image_count": len(group),
                "landmark_detection_rate": round(float(group["landmarks_detected"].mean()), 6),
                "accuracy_when_detected": (
                    round(
                        float(
                            accuracy_score(
                                participant_predicted["actual_pose"], participant_predicted["predicted_pose"]
                            )
                        ),
                        6,
                    )
                    if not participant_predicted.empty
                    else None
                ),
            }
        )

    class_results = []
    for class_name in EXPECTED_CLASSES:
        group = frame[frame["actual_pose"] == class_name]
        class_predicted = group[group["predicted_pose"].notna()]
        class_results.append(
            {
                "class": class_name,
                "image_count": len(group),
                "landmark_detection_rate": (
                    round(float(group["landmarks_detected"].mean()), 6) if not group.empty else None
                ),
                "recall_when_detected": (
                    round(float((class_predicted["predicted_pose"] == class_name).mean()), 6)
                    if not class_predicted.empty
                    else None
                ),
            }
        )

    evidence_gates = {
        "minimum_10_consented_participants": metrics["participant_count"] >= 10,
        "minimum_300_local_images": metrics["image_count"] >= 300,
        "minimum_90_percent_landmark_detection": metrics["landmark_detection_rate"] >= 0.9,
        "minimum_80_percent_macro_f1": (
            metrics["prediction_macro_f1_when_detected"] is not None
            and metrics["prediction_macro_f1_when_detected"] >= 0.8
        ),
        "all_five_classes_present": all(item["image_count"] > 0 for item in class_results),
        "minimum_70_percent_recall_each_class": all(
            item["recall_when_detected"] is not None and item["recall_when_detected"] >= 0.7
            for item in class_results
        ),
    }

    report = {
        "schema_version": 1,
        "created_at_utc": datetime.now(UTC).isoformat(),
        "mode": "local_camera_validation_only",
        "classes": EXPECTED_CLASSES,
        "metrics": metrics,
        "participant_results": participant_results,
        "class_results": class_results,
        "evidence_gates": evidence_gates,
        "local_camera_evidence_passed": all(evidence_gates.values()),
        "deployment_allowed": False,
        "limitations": [
            "Folder names are expected identity labels, not form-correctness labels.",
            "Results must be reviewed per participant and camera setup.",
            "This report cannot support medical, safety, therapy, or progression-readiness claims.",
        ],
    }
    results_csv.parent.mkdir(parents=True, exist_ok=True)
    frame.to_csv(results_csv, index=False, lineterminator="\n")
    report_json.parent.mkdir(parents=True, exist_ok=True)
    report_json.write_text(json.dumps(report, indent=2), encoding="utf-8")
    return report


def main() -> None:
    parser = argparse.ArgumentParser(description="Evaluate consented GymRAVANA local-camera pose images.")
    parser.add_argument("--dataset", type=Path, default=Path("ai/data/pose_camera_validation"))
    parser.add_argument("--artifacts", type=Path, default=Path("ai/artifacts"))
    parser.add_argument("--landmarker", type=Path, default=Path("ai/models/pose_landmarker_lite.task"))
    parser.add_argument("--results", type=Path, default=Path("ai/data/pose_camera_validation_results.csv"))
    parser.add_argument("--report", type=Path, default=Path("ai/artifacts/pose_camera_validation_report.json"))
    arguments = parser.parse_args()
    registry = PoseValidationRegistry(arguments.artifacts, arguments.landmarker)
    report = evaluate_camera_directory(arguments.dataset, registry, arguments.results, arguments.report)
    print(json.dumps(report, indent=2))


if __name__ == "__main__":
    main()
