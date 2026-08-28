from __future__ import annotations

import io
import json
from dataclasses import dataclass
from hashlib import sha256
from pathlib import Path
from typing import Any

import joblib
import mediapipe as mp
import numpy as np
import pandas as pd
import sklearn
from mediapipe.tasks import python
from mediapipe.tasks.python import vision
from PIL import Image, ImageOps, UnidentifiedImageError

from ai.pose.public_workflow import EXPECTED_CLASSES, PUBLIC_TASK
from ai.pose.workflow import FEATURE_NAMES, landmark_features

POSE_MODEL_FILE = "pose_identity_public_prototype.joblib"
POSE_METADATA_FILE = "pose_identity_public_prototype.metadata.json"
POSE_IMPORTANCE_FILE = "pose_identity_public_prototype.feature_importance.json"
REQUIRED_POSE_ARTIFACTS = (POSE_MODEL_FILE, POSE_METADATA_FILE, POSE_IMPORTANCE_FILE)
ALLOWED_IMAGE_TYPES = {"image/jpeg", "image/png", "image/webp"}
MAX_IMAGE_BYTES = 5 * 1024 * 1024
MIN_IMAGE_SIDE = 64
MAX_IMAGE_SIDE = 4096
MIN_VALIDATION_CONFIDENCE = 0.65
MIN_CORE_VISIBILITY = 0.55


class PoseArtifactUnavailable(RuntimeError):
    """Raised when the local validation artifact is missing or incompatible."""


class PoseImageInvalid(ValueError):
    """Raised when an image cannot safely enter the local validation pipeline."""


@dataclass(frozen=True)
class LoadedPoseArtifacts:
    model: Any
    model_version: str
    importance: list[dict[str, Any]]
    metadata: dict[str, Any]


class PoseValidationRegistry:
    def __init__(self, artifact_directory: Path, landmarker_path: Path) -> None:
        self.artifact_directory = artifact_directory.resolve()
        self.landmarker_path = landmarker_path.resolve()

    def status(self) -> dict[str, Any]:
        missing = [name for name in REQUIRED_POSE_ARTIFACTS if not (self.artifact_directory / name).is_file()]
        if not self.landmarker_path.is_file():
            missing.append(self.landmarker_path.name)
        if missing:
            return {
                "ready": False,
                "mode": "local_camera_validation_only",
                "reason": "The complete local pose-validation package is unavailable.",
                "missing_files": missing,
                "model_version": None,
                "deployment_allowed": False,
            }

        try:
            loaded = self.load()
        except PoseArtifactUnavailable as exception:
            return {
                "ready": False,
                "mode": "local_camera_validation_only",
                "reason": str(exception),
                "missing_files": [],
                "model_version": None,
                "deployment_allowed": False,
            }

        return {
            "ready": True,
            "mode": "local_camera_validation_only",
            "reason": None,
            "missing_files": [],
            "model_version": loaded.model_version,
            "deployment_allowed": False,
        }

    def load(self) -> LoadedPoseArtifacts:
        missing = [name for name in REQUIRED_POSE_ARTIFACTS if not (self.artifact_directory / name).is_file()]
        if not self.landmarker_path.is_file():
            missing.append(self.landmarker_path.name)
        if missing:
            raise PoseArtifactUnavailable("Missing pose-validation files: " + ", ".join(missing))

        metadata = self._json(POSE_METADATA_FILE)
        importance_document = self._json_list(POSE_IMPORTANCE_FILE)
        model_path = self.artifact_directory / POSE_MODEL_FILE

        checks = {
            "schema version": metadata.get("schema_version") == 2,
            "task": metadata.get("task") == PUBLIC_TASK,
            "prototype-only marker": metadata.get("prototype_only") is True,
            "deployment block": metadata.get("deployment_allowed") is False,
            "feature contract": metadata.get("feature_names") == FEATURE_NAMES,
            "class contract": metadata.get("classes") == EXPECTED_CLASSES,
            "scikit-learn version": metadata.get("scikit_learn_version") == sklearn.__version__,
            "training evidence": isinstance(metadata.get("training_rows"), int) and metadata["training_rows"] >= 250,
        }
        failed = [name for name, passed in checks.items() if not passed]
        if failed:
            raise PoseArtifactUnavailable("Pose artifact contract failed: " + ", ".join(failed))

        evidence = metadata.get("evidence_gates")
        if not isinstance(evidence, dict):
            raise PoseArtifactUnavailable("Pose artifact evidence gates are missing.")
        if any(
            evidence.get(name) is not False
            for name in ("participant_ids_available", "trainer_verified_labels", "local_camera_test_available")
        ):
            raise PoseArtifactUnavailable("This service accepts only the reviewed validation-only evidence state.")

        actual_model_hash = self._sha256_file(model_path)
        if metadata.get("model_sha256") != actual_model_hash:
            raise PoseArtifactUnavailable("Pose model fingerprint does not match its metadata.")
        actual_landmarker_hash = self._sha256_file(self.landmarker_path)
        if metadata.get("pose_landmarker_sha256") != actual_landmarker_hash:
            raise PoseArtifactUnavailable("MediaPipe landmarker fingerprint does not match the training metadata.")

        importance_features = [item.get("feature") for item in importance_document if isinstance(item, dict)]
        if len(importance_document) != len(FEATURE_NAMES) or set(importance_features) != set(FEATURE_NAMES):
            raise PoseArtifactUnavailable("Pose feature-importance artifact is incomplete.")

        try:
            model = joblib.load(model_path)
        except Exception as exception:
            raise PoseArtifactUnavailable("Pose model could not be loaded safely.") from exception
        if not callable(getattr(model, "predict_proba", None)) or not callable(getattr(model, "predict", None)):
            raise PoseArtifactUnavailable("Pose model does not support the required prediction methods.")
        if list(getattr(model, "classes_", [])) != EXPECTED_CLASSES:
            raise PoseArtifactUnavailable("Pose model classes do not match the reviewed contract.")

        model_name = str(metadata.get("model_name", "unknown"))
        return LoadedPoseArtifacts(
            model=model,
            model_version=f"{model_name}-{actual_model_hash[:12]}",
            importance=importance_document,
            metadata=metadata,
        )

    def validate_image(self, image_bytes: bytes, media_type: str) -> dict[str, Any]:
        if media_type not in ALLOWED_IMAGE_TYPES:
            raise PoseImageInvalid("Only JPEG, PNG, or WebP images are accepted.")
        if not image_bytes:
            raise PoseImageInvalid("The image body is empty.")
        if len(image_bytes) > MAX_IMAGE_BYTES:
            raise PoseImageInvalid("The image exceeds the 5 MB validation limit.")

        artifacts = self.load()
        image_array, width, height = self._decode_image(image_bytes)
        options = vision.PoseLandmarkerOptions(
            base_options=python.BaseOptions(model_asset_path=str(self.landmarker_path)),
            running_mode=vision.RunningMode.IMAGE,
            num_poses=1,
            min_pose_detection_confidence=0.4,
            min_pose_presence_confidence=0.4,
        )
        with vision.PoseLandmarker.create_from_options(options) as landmarker:
            result = landmarker.detect(mp.Image(image_format=mp.ImageFormat.SRGB, data=image_array))

        if not result.pose_landmarks:
            return self._no_landmarks_response(artifacts, width, height)

        features = landmark_features(result.pose_landmarks[0])
        frame = pd.DataFrame([[features[name] for name in FEATURE_NAMES]], columns=FEATURE_NAMES)
        try:
            probabilities = np.asarray(artifacts.model.predict_proba(frame)[0], dtype=float)
        except Exception as exception:
            raise PoseArtifactUnavailable("The pose model could not evaluate the extracted landmarks.") from exception
        if len(probabilities) != len(EXPECTED_CLASSES) or not np.isfinite(probabilities).all():
            raise PoseArtifactUnavailable("The pose model returned invalid class probabilities.")
        if (probabilities < 0).any() or not np.isclose(probabilities.sum(), 1.0, atol=1e-5):
            raise PoseArtifactUnavailable("The pose model probabilities are outside the valid range.")

        best_index = int(np.argmax(probabilities))
        predicted_pose = EXPECTED_CLASSES[best_index]
        confidence = float(probabilities[best_index])
        visibility = float(features["visibility_mean"])
        accepted = confidence >= MIN_VALIDATION_CONFIDENCE and visibility >= MIN_CORE_VISIBILITY
        if confidence < MIN_VALIDATION_CONFIDENCE:
            quality_reason = "low_model_confidence"
        elif visibility < MIN_CORE_VISIBILITY:
            quality_reason = "low_landmark_visibility"
        else:
            quality_reason = "accepted_for_offline_validation"

        top_features = sorted(
            artifacts.importance,
            key=lambda item: float(item.get("mean_importance", 0)),
            reverse=True,
        )[:5]
        return {
            "mode": "local_camera_validation_only",
            "model_version": artifacts.model_version,
            "landmarks_detected": True,
            "validation_accepted": accepted,
            "quality_reason": quality_reason,
            "predicted_pose": predicted_pose,
            "confidence": round(confidence, 6),
            "class_probabilities": {
                class_name: round(float(probability), 6)
                for class_name, probability in zip(EXPECTED_CLASSES, probabilities, strict=True)
            },
            "core_visibility": round(visibility, 6),
            "image_width": width,
            "image_height": height,
            "explanation": [
                {
                    "feature": str(item["feature"]),
                    "global_permutation_importance": round(float(item.get("mean_importance", 0)), 8),
                }
                for item in top_features
            ],
            "review_required": True,
            "deployment_allowed": False,
            "disclaimer": (
                "Offline pose-identity validation only. This output does not assess form, safety, therapy needs, "
                "or progression readiness."
            ),
        }

    def _decode_image(self, image_bytes: bytes) -> tuple[np.ndarray, int, int]:
        try:
            with Image.open(io.BytesIO(image_bytes)) as image:
                image.verify()
            with Image.open(io.BytesIO(image_bytes)) as image:
                width, height = image.size
                if min(width, height) < MIN_IMAGE_SIDE:
                    raise PoseImageInvalid("Both image dimensions must be at least 64 pixels.")
                if max(width, height) > MAX_IMAGE_SIDE:
                    raise PoseImageInvalid("Image dimensions may not exceed 4096 pixels.")
                normalized = ImageOps.exif_transpose(image).convert("RGB")
                width, height = normalized.size
                image_array = np.ascontiguousarray(np.asarray(normalized, dtype=np.uint8))
        except PoseImageInvalid:
            raise
        except (OSError, UnidentifiedImageError) as exception:
            raise PoseImageInvalid("The request body is not a readable image.") from exception
        return image_array, width, height

    def _no_landmarks_response(self, artifacts: LoadedPoseArtifacts, width: int, height: int) -> dict[str, Any]:
        return {
            "mode": "local_camera_validation_only",
            "model_version": artifacts.model_version,
            "landmarks_detected": False,
            "validation_accepted": False,
            "quality_reason": "pose_not_detected",
            "predicted_pose": None,
            "confidence": None,
            "class_probabilities": {},
            "core_visibility": None,
            "image_width": width,
            "image_height": height,
            "explanation": [],
            "review_required": True,
            "deployment_allowed": False,
            "disclaimer": (
                "Offline pose-identity validation only. This output does not assess form, safety, therapy needs, "
                "or progression readiness."
            ),
        }

    def _json(self, name: str) -> dict[str, Any]:
        try:
            document = json.loads((self.artifact_directory / name).read_text(encoding="utf-8"))
        except (OSError, UnicodeError, json.JSONDecodeError) as exception:
            raise PoseArtifactUnavailable(f"{name} is unreadable or invalid JSON.") from exception
        if not isinstance(document, dict):
            raise PoseArtifactUnavailable(f"{name} must contain a JSON object.")
        return document

    def _json_list(self, name: str) -> list[dict[str, Any]]:
        try:
            document = json.loads((self.artifact_directory / name).read_text(encoding="utf-8"))
        except (OSError, UnicodeError, json.JSONDecodeError) as exception:
            raise PoseArtifactUnavailable(f"{name} is unreadable or invalid JSON.") from exception
        if not isinstance(document, list):
            raise PoseArtifactUnavailable(f"{name} must contain a JSON array.")
        return document

    @staticmethod
    def _sha256_file(path: Path) -> str:
        digest = sha256()
        with path.open("rb") as stream:
            for chunk in iter(lambda: stream.read(1024 * 1024), b""):
                digest.update(chunk)
        return digest.hexdigest()
