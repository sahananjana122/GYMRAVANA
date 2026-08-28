from __future__ import annotations

import json
from collections import defaultdict
from pathlib import Path
from typing import Any

import joblib
import mediapipe as mp
import numpy as np
import pandas as pd
import sklearn
from mediapipe.tasks import python
from mediapipe.tasks.python import vision
from PIL import Image, UnidentifiedImageError
from sklearn.inspection import permutation_importance
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, f1_score
from sklearn.model_selection import StratifiedGroupKFold

from ai.pose.workflow import FEATURE_NAMES, _candidate_factories, landmark_features, sha256_file

PUBLIC_TASK = "five_class_yoga_pose_identity"
PUBLIC_SOURCE_KIND = "yoga107_web_image"
EXPECTED_CLASSES = [
    "balasana",
    "mayurasana",
    "salamba_sirsasana",
    "urdhva_dhanurasana",
    "virasana",
]
PERCEPTUAL_HASH_DISTANCE = 4
FINAL_HOLDOUT_FOLDS = 5
DEVELOPMENT_FOLDS = 4
RANDOM_STATE = 42


def image_dhash(path: Path, hash_size: int = 8) -> str:
    with Image.open(path) as image:
        grayscale = image.convert("L").resize((hash_size + 1, hash_size), Image.Resampling.LANCZOS)
        pixels = np.asarray(grayscale)
    bits = pixels[:, 1:] > pixels[:, :-1]
    value = sum(int(bit) << index for index, bit in enumerate(bits.flatten()))

    return f"{value:0{hash_size * hash_size // 4}x}"


def hamming_distance(first: str, second: str) -> int:
    return (int(first, 16) ^ int(second, 16)).bit_count()


class _DisjointSet:
    def __init__(self, items: list[str]) -> None:
        self.parent = {item: item for item in items}

    def find(self, item: str) -> str:
        while self.parent[item] != item:
            self.parent[item] = self.parent[self.parent[item]]
            item = self.parent[item]
        return item

    def union(self, first: str, second: str) -> None:
        first_root = self.find(first)
        second_root = self.find(second)
        if first_root != second_root:
            self.parent[second_root] = first_root


def _verified_manifest(dataset_directory: Path) -> dict[str, Any]:
    manifest_path = dataset_directory / "extraction_manifest.json"
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    if manifest.get("image_count") != len(manifest.get("records", [])):
        raise ValueError("The extraction manifest image count is inconsistent.")
    if sorted(manifest.get("class_counts", {})) != EXPECTED_CLASSES:
        raise ValueError("The extraction manifest does not contain the expected five classes.")
    return manifest


def _audit_images(dataset_directory: Path, manifest: dict[str, Any]) -> tuple[list[dict[str, Any]], list[dict[str, str]]]:
    audited: list[dict[str, Any]] = []
    failures: list[dict[str, str]] = []
    for item in manifest["records"]:
        path = dataset_directory / item["relative_path"]
        try:
            if sha256_file(path) != item["sha256"]:
                raise ValueError("sha256_mismatch")
            with Image.open(path) as image:
                image.verify()
            with Image.open(path) as image:
                width, height = image.size
            audited.append(
                {
                    **item,
                    "sample_id": f"yoga107_{len(audited) + len(failures) + 1:04d}",
                    "width": width,
                    "height": height,
                    "dhash": image_dhash(path),
                }
            )
        except (OSError, UnidentifiedImageError, ValueError) as exception:
            failures.append({"relative_path": item["relative_path"], "reason": str(exception)})
    return audited, failures


def _duplicate_groups(records: list[dict[str, Any]]) -> tuple[dict[str, str], list[dict[str, Any]]]:
    by_class: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for record in records:
        by_class[record["canonical_class"]].append(record)

    group_by_sample: dict[str, str] = {}
    group_summaries: list[dict[str, Any]] = []
    for canonical_class, class_records in sorted(by_class.items()):
        samples = [record["sample_id"] for record in class_records]
        disjoint_set = _DisjointSet(samples)
        for index, first in enumerate(class_records):
            for second in class_records[index + 1 :]:
                if first["sha256"] == second["sha256"] or hamming_distance(first["dhash"], second["dhash"]) <= PERCEPTUAL_HASH_DISTANCE:
                    disjoint_set.union(first["sample_id"], second["sample_id"])

        members_by_root: dict[str, list[str]] = defaultdict(list)
        for sample in samples:
            members_by_root[disjoint_set.find(sample)].append(sample)
        for group_index, members in enumerate(sorted(members_by_root.values()), start=1):
            group_id = f"{canonical_class}_visual_group_{group_index:03d}"
            for sample in members:
                group_by_sample[sample] = group_id
            group_summaries.append({"group_id": group_id, "class": canonical_class, "members": members})

    return group_by_sample, group_summaries


def prepare_public_features(
    dataset_directory: Path,
    model_path: Path,
    output_csv: Path,
    output_metadata: Path,
) -> dict[str, Any]:
    manifest = _verified_manifest(dataset_directory)
    audited, image_failures = _audit_images(dataset_directory, manifest)
    group_by_sample, group_summaries = _duplicate_groups(audited)

    exact_hash_classes: dict[str, set[str]] = defaultdict(set)
    for record in audited:
        exact_hash_classes[record["sha256"]].add(record["canonical_class"])
    conflicting_hashes = {digest for digest, classes in exact_hash_classes.items() if len(classes) > 1}
    cross_class_near_duplicates = [
        {
            "first_sample_id": first["sample_id"],
            "first_class": first["canonical_class"],
            "second_sample_id": second["sample_id"],
            "second_class": second["canonical_class"],
            "hamming_distance": hamming_distance(first["dhash"], second["dhash"]),
        }
        for index, first in enumerate(audited)
        for second in audited[index + 1 :]
        if first["canonical_class"] != second["canonical_class"]
        and hamming_distance(first["dhash"], second["dhash"]) <= PERCEPTUAL_HASH_DISTANCE
    ]

    options = vision.PoseLandmarkerOptions(
        base_options=python.BaseOptions(model_asset_path=str(model_path)),
        running_mode=vision.RunningMode.IMAGE,
        num_poses=1,
        min_pose_detection_confidence=0.4,
        min_pose_presence_confidence=0.4,
    )
    rows: list[dict[str, Any]] = []
    detection_failures: list[dict[str, str]] = []
    with vision.PoseLandmarker.create_from_options(options) as landmarker:
        for record in audited:
            if record["sha256"] in conflicting_hashes:
                detection_failures.append({"sample_id": record["sample_id"], "reason": "conflicting_exact_label"})
                continue
            image_path = dataset_directory / record["relative_path"]
            try:
                result = landmarker.detect(mp.Image.create_from_file(str(image_path)))
            except (RuntimeError, ValueError) as exception:
                detection_failures.append({"sample_id": record["sample_id"], "reason": f"decode_error: {exception}"})
                continue
            if not result.pose_landmarks:
                detection_failures.append({"sample_id": record["sample_id"], "reason": "pose_not_detected"})
                continue
            rows.append(
                {
                    "sample_id": record["sample_id"],
                    "visual_group_id": group_by_sample[record["sample_id"]],
                    "source_kind": PUBLIC_SOURCE_KIND,
                    "canonical_pose": record["canonical_class"],
                    "relative_path": record["relative_path"],
                    "image_sha256": record["sha256"],
                    "image_dhash": record["dhash"],
                    "width": record["width"],
                    "height": record["height"],
                    **landmark_features(result.pose_landmarks[0]),
                }
            )

    frame = pd.DataFrame(rows)
    columns = [
        "sample_id",
        "visual_group_id",
        "source_kind",
        "canonical_pose",
        "relative_path",
        "image_sha256",
        "image_dhash",
        "width",
        "height",
        *FEATURE_NAMES,
    ]
    frame = frame.reindex(columns=columns)
    if sorted(frame["canonical_pose"].unique()) != EXPECTED_CLASSES:
        raise ValueError("Landmark extraction did not retain every expected pose class.")

    output_csv.parent.mkdir(parents=True, exist_ok=True)
    frame.to_csv(output_csv, index=False, lineterminator="\n")
    successful_group_ids = set(frame["visual_group_id"])
    successful_group_summaries = [group for group in group_summaries if group["group_id"] in successful_group_ids]
    metadata = {
        "schema_version": 2,
        "task": PUBLIC_TASK,
        "target": "canonical_pose",
        "row_count": len(frame),
        "class_count": int(frame["canonical_pose"].nunique()),
        "class_counts": {key: int(value) for key, value in frame["canonical_pose"].value_counts().sort_index().items()},
        "visual_group_count": int(frame["visual_group_id"].nunique()),
        "duplicate_group_count": sum(len(group["members"]) > 1 for group in successful_group_summaries),
        "cross_class_near_duplicate_pairs": cross_class_near_duplicates,
        "classes": EXPECTED_CLASSES,
        "columns": columns,
        "feature_names": FEATURE_NAMES,
        "image_audit_failures": image_failures,
        "landmark_failures": detection_failures,
        "extraction_manifest_sha256": sha256_file(dataset_directory / "extraction_manifest.json"),
        "source_archive_sha256": manifest["archive_sha256"],
        "pose_landmarker_sha256": sha256_file(model_path),
        "dataset_sha256": sha256_file(output_csv),
        "split_policy": "Near-duplicate visual groups remain together; subject grouping is impossible because no participant IDs are provided.",
        "limitations": [
            "Yoga-107 consists of web images and does not provide participant identifiers.",
            "Visual grouping reduces duplicate leakage but cannot guarantee subject-independent evaluation.",
            "The labels describe pose identity and are not trainer-verified correctness scores.",
            "The images do not represent GymRAVANA's eventual local camera environment.",
        ],
    }
    output_metadata.write_text(json.dumps(metadata, indent=2), encoding="utf-8")
    return metadata


def load_verified_public_features(csv_path: Path, metadata_path: Path) -> tuple[pd.DataFrame, dict[str, Any]]:
    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    frame = pd.read_csv(csv_path)
    checks = {
        "schema_version": metadata.get("schema_version") == 2,
        "task": metadata.get("task") == PUBLIC_TASK,
        "dataset_sha256": metadata.get("dataset_sha256") == sha256_file(csv_path),
        "row_count": metadata.get("row_count") == len(frame),
        "columns": metadata.get("columns") == list(frame.columns),
        "target": metadata.get("target") == "canonical_pose",
        "feature_names": metadata.get("feature_names") == FEATURE_NAMES,
        "classes": metadata.get("classes") == EXPECTED_CLASSES,
    }
    failed = [name for name, passed in checks.items() if not passed]
    if failed:
        raise ValueError(f"Public pose dataset integrity checks failed: {failed}")
    return frame, metadata


def _metrics(actual: pd.Series, predicted: np.ndarray, labels: list[str]) -> dict[str, Any]:
    return {
        "row_count": len(actual),
        "accuracy": round(float(accuracy_score(actual, predicted)), 6),
        "macro_f1": round(float(f1_score(actual, predicted, average="macro", zero_division=0)), 6),
        "confusion_matrix": confusion_matrix(actual, predicted, labels=labels).tolist(),
        "classification_report": classification_report(
            actual,
            predicted,
            labels=labels,
            output_dict=True,
            zero_division=0,
        ),
    }


def evaluate_public_candidates(csv_path: Path, metadata_path: Path, report_path: Path) -> dict[str, Any]:
    frame, metadata = load_verified_public_features(csv_path, metadata_path)
    final_splitter = StratifiedGroupKFold(n_splits=FINAL_HOLDOUT_FOLDS, shuffle=True, random_state=RANDOM_STATE)
    development_indices, holdout_indices = next(
        final_splitter.split(frame[FEATURE_NAMES], frame["canonical_pose"], frame["visual_group_id"])
    )
    development = frame.iloc[development_indices].copy()
    holdout = frame.iloc[holdout_indices].copy()
    if set(development["visual_group_id"]) & set(holdout["visual_group_id"]):
        raise ValueError("A visual duplicate group leaked into the final holdout.")

    candidates: dict[str, Any] = {}
    development_splitter = StratifiedGroupKFold(
        n_splits=DEVELOPMENT_FOLDS,
        shuffle=True,
        random_state=RANDOM_STATE,
    )
    fold_indices = list(
        development_splitter.split(
            development[FEATURE_NAMES],
            development["canonical_pose"],
            development["visual_group_id"],
        )
    )
    for name, factory in _candidate_factories().items():
        folds: list[dict[str, Any]] = []
        for fold_number, (train_indices, validation_indices) in enumerate(fold_indices, start=1):
            train = development.iloc[train_indices]
            validation = development.iloc[validation_indices]
            model = factory()
            model.fit(train[FEATURE_NAMES], train["canonical_pose"])
            prediction = model.predict(validation[FEATURE_NAMES])
            folds.append({"fold": fold_number, **_metrics(validation["canonical_pose"], prediction, EXPECTED_CLASSES)})
        candidates[name] = {
            "folds": folds,
            "mean_accuracy": round(float(np.mean([fold["accuracy"] for fold in folds])), 6),
            "mean_macro_f1": round(float(np.mean([fold["macro_f1"] for fold in folds])), 6),
        }

    selected_name = sorted(
        candidates,
        key=lambda name: (-candidates[name]["mean_macro_f1"], -candidates[name]["mean_accuracy"], name),
    )[0]
    selected_model = _candidate_factories()[selected_name]()
    selected_model.fit(development[FEATURE_NAMES], development["canonical_pose"])
    holdout_predictions = selected_model.predict(holdout[FEATURE_NAMES])
    holdout_metrics = _metrics(holdout["canonical_pose"], holdout_predictions, EXPECTED_CLASSES)
    holdout_metrics["predictions"] = [
        {
            "sample_id": sample_id,
            "relative_path": relative_path,
            "actual": actual,
            "predicted": predicted,
        }
        for sample_id, relative_path, actual, predicted in zip(
            holdout["sample_id"],
            holdout["relative_path"],
            holdout["canonical_pose"],
            holdout_predictions,
            strict=True,
        )
    ]

    evidence_gates = {
        "minimum_250_landmark_rows": len(frame) >= 250,
        "minimum_10_visual_groups": frame["visual_group_id"].nunique() >= 10,
        "participant_ids_available": False,
        "trainer_verified_labels": False,
        "local_camera_test_available": False,
        "form_correctness_target_available": False,
    }
    blockers = [
        "The web dataset has no participant IDs, so subject-independent evaluation is impossible.",
        "Pose labels are not trainer-verified form-correctness labels.",
        "No out-of-domain test has been recorded with the intended GymRAVANA camera setup.",
        "This model recognizes five pose identities and must not score safety, correctness, or progression readiness.",
    ]
    report = {
        "schema_version": 2,
        "task": PUBLIC_TASK,
        "dataset_sha256": metadata["dataset_sha256"],
        "feature_names": FEATURE_NAMES,
        "split_policy": metadata["split_policy"],
        "development_rows": len(development),
        "holdout_rows": len(holdout),
        "development_visual_groups": int(development["visual_group_id"].nunique()),
        "holdout_visual_groups": int(holdout["visual_group_id"].nunique()),
        "selection_rule": "Highest duplicate-grouped development mean macro F1, then accuracy, then stable name order.",
        "candidates": candidates,
        "selected_model": selected_name,
        "holdout": holdout_metrics,
        "evidence_gates": evidence_gates,
        "deployment_allowed": False,
        "deployment_blockers": blockers,
    }
    report_path.parent.mkdir(parents=True, exist_ok=True)
    report_path.write_text(json.dumps(report, indent=2), encoding="utf-8")
    return report


def export_public_prototype(
    csv_path: Path,
    metadata_path: Path,
    selection_report_path: Path,
    artifact_directory: Path,
) -> dict[str, Any]:
    frame, metadata = load_verified_public_features(csv_path, metadata_path)
    selection = json.loads(selection_report_path.read_text(encoding="utf-8"))
    if selection.get("dataset_sha256") != metadata["dataset_sha256"]:
        raise ValueError("The public pose report belongs to a different feature dataset.")
    if selection.get("deployment_allowed") is not False:
        raise ValueError("A public prototype must remain non-deployable.")

    model_name = selection["selected_model"]
    final_splitter = StratifiedGroupKFold(n_splits=FINAL_HOLDOUT_FOLDS, shuffle=True, random_state=RANDOM_STATE)
    development_indices, holdout_indices = next(
        final_splitter.split(frame[FEATURE_NAMES], frame["canonical_pose"], frame["visual_group_id"])
    )
    development = frame.iloc[development_indices]
    holdout = frame.iloc[holdout_indices]
    explanation_model = _candidate_factories()[model_name]()
    explanation_model.fit(development[FEATURE_NAMES], development["canonical_pose"])
    importance = permutation_importance(
        explanation_model,
        holdout[FEATURE_NAMES],
        holdout["canonical_pose"],
        scoring="f1_macro",
        n_repeats=20,
        random_state=RANDOM_STATE,
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
    model_path = artifact_directory / "pose_identity_public_prototype.joblib"
    model = _candidate_factories()[model_name]()
    model.fit(frame[FEATURE_NAMES], frame["canonical_pose"])
    joblib.dump(model, model_path)
    artifact_metadata = {
        "schema_version": 2,
        "task": PUBLIC_TASK,
        "prototype_only": True,
        "deployment_allowed": False,
        "model_name": model_name,
        "classes": EXPECTED_CLASSES,
        "feature_names": FEATURE_NAMES,
        "training_rows": len(frame),
        "dataset_sha256": metadata["dataset_sha256"],
        "source_archive_sha256": metadata["source_archive_sha256"],
        "pose_landmarker_sha256": metadata["pose_landmarker_sha256"],
        "scikit_learn_version": sklearn.__version__,
        "model_sha256": sha256_file(model_path),
        "evaluation": selection["holdout"],
        "evidence_gates": selection["evidence_gates"],
        "deployment_blockers": selection["deployment_blockers"],
        "intended_use": "Undergraduate five-class pose-identity pipeline demonstration only.",
        "prohibited_claims": [
            "form correctness",
            "injury prevention",
            "medical or therapy advice",
            "progression readiness",
            "subject-independent accuracy",
            "production accuracy",
        ],
    }
    (artifact_directory / "pose_identity_public_prototype.metadata.json").write_text(
        json.dumps(artifact_metadata, indent=2), encoding="utf-8"
    )
    (artifact_directory / "pose_identity_public_prototype.feature_importance.json").write_text(
        json.dumps(importance_rows, indent=2), encoding="utf-8"
    )
    return artifact_metadata
