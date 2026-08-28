from __future__ import annotations

import hashlib
import json
import math
from collections.abc import Callable
from pathlib import Path
from typing import Any

import joblib
import mediapipe as mp
import numpy as np
import pandas as pd
from mediapipe.tasks import python
from mediapipe.tasks.python import vision
from sklearn.base import ClassifierMixin
from sklearn.ensemble import RandomForestClassifier
from sklearn.inspection import permutation_importance
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, f1_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC

POSE_ALIASES = {
    "virasana": "virasana",
    "adho_mukha_virasana": "balasana",
    "chakrasana": "urdhva_dhanurasana",
    "mayurasana": "mayurasana",
    "shirshasana": "salamba_sirsasana",
}
EXCLUDED_POSES = {"santulanasana"}
HOLDOUT_SOURCE = "source_03_correct_examples"
MINIMUM_DEPLOYMENT_ROWS = 250
MINIMUM_DEPLOYMENT_SOURCES = 10

FEATURE_NAMES = [
    "left_elbow_angle",
    "right_elbow_angle",
    "left_shoulder_angle",
    "right_shoulder_angle",
    "left_hip_angle",
    "right_hip_angle",
    "left_knee_angle",
    "right_knee_angle",
    "torso_angle_deg",
    "shoulder_slope_deg",
    "hip_slope_deg",
    "elbow_symmetry_abs",
    "shoulder_symmetry_abs",
    "hip_symmetry_abs",
    "knee_symmetry_abs",
    "shoulder_width_norm",
    "hip_width_norm",
    "wrist_distance_norm",
    "ankle_distance_norm",
    "visibility_mean",
]

CORE_LANDMARKS = [11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 27, 28]


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)

    return digest.hexdigest()


def _point(landmarks: list[Any], index: int) -> np.ndarray:
    item = landmarks[index]

    return np.array([item.x, item.y], dtype=float)


def _midpoint(first: np.ndarray, second: np.ndarray) -> np.ndarray:
    return (first + second) / 2.0


def _distance(first: np.ndarray, second: np.ndarray) -> float:
    return float(np.linalg.norm(first - second))


def _angle(first: np.ndarray, centre: np.ndarray, third: np.ndarray) -> float:
    left = first - centre
    right = third - centre
    denominator = np.linalg.norm(left) * np.linalg.norm(right)
    if denominator <= 1e-9:
        return 0.0
    cosine = float(np.clip(np.dot(left, right) / denominator, -1.0, 1.0))

    return float(np.degrees(np.arccos(cosine)))


def _slope(first: np.ndarray, second: np.ndarray) -> float:
    delta = second - first

    return float(np.degrees(np.arctan2(delta[1], delta[0])))


def landmark_features(landmarks: list[Any]) -> dict[str, float]:
    points = {index: _point(landmarks, index) for index in CORE_LANDMARKS}
    shoulder_midpoint = _midpoint(points[11], points[12])
    hip_midpoint = _midpoint(points[23], points[24])
    torso_scale = max(_distance(shoulder_midpoint, hip_midpoint), 1e-6)

    values = {
        "left_elbow_angle": _angle(points[11], points[13], points[15]),
        "right_elbow_angle": _angle(points[12], points[14], points[16]),
        "left_shoulder_angle": _angle(points[13], points[11], points[23]),
        "right_shoulder_angle": _angle(points[14], points[12], points[24]),
        "left_hip_angle": _angle(points[11], points[23], points[25]),
        "right_hip_angle": _angle(points[12], points[24], points[26]),
        "left_knee_angle": _angle(points[23], points[25], points[27]),
        "right_knee_angle": _angle(points[24], points[26], points[28]),
        "torso_angle_deg": abs(
            float(
                np.degrees(
                    np.arctan2(
                        shoulder_midpoint[0] - hip_midpoint[0],
                        hip_midpoint[1] - shoulder_midpoint[1],
                    )
                )
            )
        ),
        "shoulder_slope_deg": _slope(points[11], points[12]),
        "hip_slope_deg": _slope(points[23], points[24]),
        "shoulder_width_norm": _distance(points[11], points[12]) / torso_scale,
        "hip_width_norm": _distance(points[23], points[24]) / torso_scale,
        "wrist_distance_norm": _distance(points[15], points[16]) / torso_scale,
        "ankle_distance_norm": _distance(points[27], points[28]) / torso_scale,
        "visibility_mean": float(np.mean([landmarks[index].visibility for index in CORE_LANDMARKS])),
    }
    values.update(
        {
            "elbow_symmetry_abs": abs(values["left_elbow_angle"] - values["right_elbow_angle"]),
            "shoulder_symmetry_abs": abs(values["left_shoulder_angle"] - values["right_shoulder_angle"]),
            "hip_symmetry_abs": abs(values["left_hip_angle"] - values["right_hip_angle"]),
            "knee_symmetry_abs": abs(values["left_knee_angle"] - values["right_knee_angle"]),
        }
    )

    return {name: round(float(values[name]), 8) for name in FEATURE_NAMES}


def prepare_features(
    starter_directory: Path,
    model_path: Path,
    output_csv: Path,
    output_metadata: Path,
) -> dict[str, Any]:
    annotations_path = starter_directory / "pose_annotations.csv"
    annotations = pd.read_csv(annotations_path)
    annotations = annotations[~annotations["pose_name"].isin(EXCLUDED_POSES)].copy()
    annotations["canonical_pose"] = annotations["pose_name"].map(POSE_ALIASES)
    if annotations["canonical_pose"].isna().any():
        missing = sorted(annotations.loc[annotations["canonical_pose"].isna(), "pose_name"].unique())
        raise ValueError(f"Missing canonical pose mappings: {missing}")

    options = vision.PoseLandmarkerOptions(
        base_options=python.BaseOptions(model_asset_path=str(model_path)),
        running_mode=vision.RunningMode.IMAGE,
        num_poses=1,
        min_pose_detection_confidence=0.4,
        min_pose_presence_confidence=0.4,
    )
    records: list[dict[str, Any]] = []
    failures: list[dict[str, str]] = []
    with vision.PoseLandmarker.create_from_options(options) as landmarker:
        for annotation in annotations.to_dict("records"):
            image_path = starter_directory / annotation["image_path"]
            result = landmarker.detect(mp.Image.create_from_file(str(image_path)))
            if not result.pose_landmarks:
                failures.append({"sample_id": annotation["sample_id"], "reason": "pose_not_detected"})
                continue
            records.append(
                {
                    "sample_id": annotation["sample_id"],
                    "source_id": annotation["source_id"],
                    "source_kind": annotation["source_kind"],
                    "pose_name": annotation["pose_name"],
                    "canonical_pose": annotation["canonical_pose"],
                    "form_class": annotation["form_class"],
                    **landmark_features(result.pose_landmarks[0]),
                }
            )

    features = pd.DataFrame(records)
    expected_columns = [
        "sample_id",
        "source_id",
        "source_kind",
        "pose_name",
        "canonical_pose",
        "form_class",
        *FEATURE_NAMES,
    ]
    features = features.reindex(columns=expected_columns)
    output_csv.parent.mkdir(parents=True, exist_ok=True)
    features.to_csv(output_csv, index=False, lineterminator="\n")
    metadata = {
        "schema_version": 1,
        "task": "five_class_yoga_pose_identity",
        "target": "canonical_pose",
        "row_count": len(features),
        "class_count": int(features["canonical_pose"].nunique()),
        "source_group_count": int(features["source_id"].nunique()),
        "classes": sorted(features["canonical_pose"].unique().tolist()),
        "columns": expected_columns,
        "feature_names": FEATURE_NAMES,
        "excluded_poses": sorted(EXCLUDED_POSES),
        "failed_samples": failures,
        "source_annotations_sha256": sha256_file(annotations_path),
        "pose_landmarker_sha256": sha256_file(model_path),
        "dataset_sha256": sha256_file(output_csv),
        "limitations": [
            "The source contains only AI/reference-collage crops.",
            "The source provides no real subject identifiers or expert verification.",
            "The task recognizes pose identity; it does not assess form correctness.",
            "Santulanasana is excluded because the supplied references conflict.",
        ],
    }
    output_metadata.write_text(json.dumps(metadata, indent=2), encoding="utf-8")

    return metadata


def load_verified_features(csv_path: Path, metadata_path: Path) -> tuple[pd.DataFrame, dict[str, Any]]:
    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    frame = pd.read_csv(csv_path)
    checks = {
        "schema_version": metadata.get("schema_version") == 1,
        "dataset_sha256": metadata.get("dataset_sha256") == sha256_file(csv_path),
        "row_count": metadata.get("row_count") == len(frame),
        "columns": metadata.get("columns") == list(frame.columns),
        "target": metadata.get("target") == "canonical_pose",
        "feature_names": metadata.get("feature_names") == FEATURE_NAMES,
    }
    failed = [name for name, passed in checks.items() if not passed]
    if failed:
        raise ValueError(f"Pose dataset integrity checks failed: {failed}")

    return frame, metadata


def _candidate_factories() -> dict[str, Callable[[], ClassifierMixin]]:
    return {
        "logistic_regression": lambda: Pipeline(
            [
                ("scale", StandardScaler()),
                (
                    "model",
                    LogisticRegression(max_iter=5000, class_weight="balanced", random_state=42),
                ),
            ]
        ),
        "random_forest": lambda: RandomForestClassifier(
            n_estimators=400,
            max_depth=5,
            min_samples_leaf=1,
            class_weight="balanced",
            random_state=42,
        ),
        "support_vector_machine": lambda: Pipeline(
            [
                ("scale", StandardScaler()),
                ("model", SVC(C=1.0, kernel="linear", class_weight="balanced", random_state=42)),
            ]
        ),
    }


def evaluate_candidates(csv_path: Path, metadata_path: Path, report_path: Path) -> dict[str, Any]:
    frame, metadata = load_verified_features(csv_path, metadata_path)
    training = frame[frame["source_id"] != HOLDOUT_SOURCE].copy()
    holdout = frame[frame["source_id"] == HOLDOUT_SOURCE].copy()
    if training.empty or holdout.empty:
        raise ValueError("The configured source-group holdout is unavailable.")
    if training["canonical_pose"].nunique() != metadata["class_count"]:
        raise ValueError("Training data does not represent every pose class.")
    if holdout["canonical_pose"].nunique() != metadata["class_count"]:
        raise ValueError("Holdout data does not represent every pose class.")

    x_train = training[FEATURE_NAMES]
    y_train = training["canonical_pose"]
    groups = training["source_id"]
    candidates: dict[str, Any] = {}
    for name, factory in _candidate_factories().items():
        fold_results = []
        for validation_source in sorted(groups.unique()):
            train_mask = groups != validation_source
            validation_mask = groups == validation_source
            model = factory()
            model.fit(x_train.loc[train_mask], y_train.loc[train_mask])
            predictions = model.predict(x_train.loc[validation_mask])
            fold_results.append(
                {
                    "validation_source": validation_source,
                    "accuracy": round(float(accuracy_score(y_train.loc[validation_mask], predictions)), 6),
                    "macro_f1": round(
                        float(f1_score(y_train.loc[validation_mask], predictions, average="macro", zero_division=0)),
                        6,
                    ),
                }
            )
        candidates[name] = {
            "folds": fold_results,
            "mean_accuracy": round(float(np.mean([fold["accuracy"] for fold in fold_results])), 6),
            "mean_macro_f1": round(float(np.mean([fold["macro_f1"] for fold in fold_results])), 6),
        }

    selected_name = sorted(
        candidates,
        key=lambda name: (
            -candidates[name]["mean_macro_f1"],
            -candidates[name]["mean_accuracy"],
            name,
        ),
    )[0]
    selected = _candidate_factories()[selected_name]()
    selected.fit(x_train, y_train)
    holdout_predictions = selected.predict(holdout[FEATURE_NAMES])
    labels = metadata["classes"]
    holdout_report = {
        "source": HOLDOUT_SOURCE,
        "row_count": len(holdout),
        "accuracy": round(float(accuracy_score(holdout["canonical_pose"], holdout_predictions)), 6),
        "macro_f1": round(
            float(f1_score(holdout["canonical_pose"], holdout_predictions, average="macro", zero_division=0)),
            6,
        ),
        "confusion_matrix": confusion_matrix(holdout["canonical_pose"], holdout_predictions, labels=labels).tolist(),
        "classification_report": classification_report(
            holdout["canonical_pose"],
            holdout_predictions,
            labels=labels,
            output_dict=True,
            zero_division=0,
        ),
        "predictions": [
            {
                "sample_id": sample_id,
                "actual": actual,
                "predicted": predicted,
            }
            for sample_id, actual, predicted in zip(
                holdout["sample_id"],
                holdout["canonical_pose"],
                holdout_predictions,
                strict=True,
            )
        ],
    }
    deployment_blockers = [
        f"Only {len(frame)} landmark rows are available; at least {MINIMUM_DEPLOYMENT_ROWS} are required.",
        f"Only {frame['source_id'].nunique()} source groups are available; at least {MINIMUM_DEPLOYMENT_SOURCES} are required.",
        "Every source is an AI/reference collage rather than trainer-verified real participant data.",
        "There is no trustworthy correct/incorrect form target.",
    ]
    report = {
        "schema_version": 1,
        "task": metadata["task"],
        "dataset_sha256": metadata["dataset_sha256"],
        "feature_names": FEATURE_NAMES,
        "selection_rule": "Highest source-grouped mean macro F1, then accuracy, then stable name order.",
        "candidates": candidates,
        "selected_model": selected_name,
        "holdout": holdout_report,
        "deployment_allowed": False,
        "deployment_blockers": deployment_blockers,
    }
    report_path.parent.mkdir(parents=True, exist_ok=True)
    report_path.write_text(json.dumps(report, indent=2), encoding="utf-8")

    return report


def export_prototype(
    csv_path: Path,
    metadata_path: Path,
    selection_report_path: Path,
    artifact_directory: Path,
) -> dict[str, Any]:
    frame, metadata = load_verified_features(csv_path, metadata_path)
    selection = json.loads(selection_report_path.read_text(encoding="utf-8"))
    if selection.get("dataset_sha256") != metadata["dataset_sha256"]:
        raise ValueError("The pose selection report belongs to a different dataset export.")
    if selection.get("feature_names") != FEATURE_NAMES:
        raise ValueError("The pose selection report feature contract is incompatible.")
    model_name = selection.get("selected_model")
    if model_name not in _candidate_factories():
        raise ValueError("The selected pose model is not supported.")

    model = _candidate_factories()[model_name]()
    model.fit(frame[FEATURE_NAMES], frame["canonical_pose"])
    holdout = frame[frame["source_id"] == HOLDOUT_SOURCE]
    importance = permutation_importance(
        model,
        holdout[FEATURE_NAMES],
        holdout["canonical_pose"],
        scoring="f1_macro",
        n_repeats=20,
        random_state=42,
    )
    importance_rows = sorted(
        [
            {
                "feature": feature,
                "mean_importance": round(float(mean), 8),
                "std_importance": round(float(std), 8),
            }
            for feature, mean, std in zip(
                FEATURE_NAMES,
                importance.importances_mean,
                importance.importances_std,
                strict=True,
            )
        ],
        key=lambda row: row["mean_importance"],
        reverse=True,
    )
    artifact_directory.mkdir(parents=True, exist_ok=True)
    model_path = artifact_directory / "pose_identity_prototype.joblib"
    joblib.dump(model, model_path)
    artifact_metadata = {
        "schema_version": 1,
        "task": metadata["task"],
        "prototype_only": True,
        "deployment_allowed": False,
        "model_name": model_name,
        "classes": metadata["classes"],
        "feature_names": FEATURE_NAMES,
        "dataset_sha256": metadata["dataset_sha256"],
        "model_sha256": sha256_file(model_path),
        "evaluation": selection["holdout"],
        "deployment_blockers": selection["deployment_blockers"],
        "intended_use": "Undergraduate pipeline demonstration for five-class pose identity only.",
        "prohibited_claims": [
            "form correctness",
            "injury prevention",
            "medical or therapy advice",
            "progression readiness",
            "production accuracy",
        ],
    }
    (artifact_directory / "pose_identity_prototype.metadata.json").write_text(
        json.dumps(artifact_metadata, indent=2),
        encoding="utf-8",
    )
    (artifact_directory / "pose_identity_prototype.feature_importance.json").write_text(
        json.dumps(importance_rows, indent=2),
        encoding="utf-8",
    )

    return artifact_metadata
