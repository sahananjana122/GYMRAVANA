from pathlib import Path

from fastapi import FastAPI, HTTPException, Request, status

from ai.service.artifacts import ArtifactRegistry, ArtifactUnavailable
from ai.service.pose_artifacts import (
    ALLOWED_IMAGE_TYPES,
    MAX_IMAGE_BYTES,
    PoseArtifactUnavailable,
    PoseImageInvalid,
    PoseValidationRegistry,
)
from ai.service.schemas import PoseValidationResponse, PredictionResponse, ReadinessFeatures


def create_app(
    artifact_directory: Path | None = None,
    pose_landmarker_path: Path | None = None,
    pose_registry: PoseValidationRegistry | None = None,
) -> FastAPI:
    directory = artifact_directory or Path(__file__).resolve().parents[1] / "artifacts"
    registry = ArtifactRegistry(directory)
    landmarker_path = pose_landmarker_path or Path(__file__).resolve().parents[1] / "models" / "pose_landmarker_lite.task"
    pose_validation = pose_registry or PoseValidationRegistry(directory, landmarker_path)
    application = FastAPI(
        title="GymRAVANA Local AI Service",
        description="Local-only readiness and offline pose-validation inference boundaries.",
        version="0.2.0",
    )

    @application.get("/health")
    def health() -> dict:
        model_status = registry.status()

        return {
            "service": "available",
            "model": "ready" if model_status["ready"] else "unavailable",
            **model_status,
            "pose_validation": pose_validation.status(),
        }

    @application.post("/v1/readiness/predict", response_model=PredictionResponse)
    def predict(features: ReadinessFeatures) -> dict:
        try:
            return registry.predict(features.model_dump())
        except ArtifactUnavailable as exception:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail={
                    "code": "model_unavailable",
                    "message": str(exception),
                },
            ) from exception

    @application.post("/v1/pose/validate", response_model=PoseValidationResponse)
    async def validate_pose(request: Request) -> dict:
        client_host = request.client.host if request.client else ""
        if client_host not in {"127.0.0.1", "::1", "localhost", "testclient"}:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail={"code": "loopback_required", "message": "Pose validation accepts loopback requests only."},
            )

        media_type = request.headers.get("content-type", "").split(";", 1)[0].strip().lower()
        if media_type not in ALLOWED_IMAGE_TYPES:
            raise HTTPException(
                status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
                detail={"code": "unsupported_image_type", "message": "Send a JPEG, PNG, or WebP image body."},
            )

        content_length = request.headers.get("content-length")
        if content_length:
            try:
                declared_length = int(content_length)
            except ValueError as exception:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail={"code": "invalid_content_length", "message": "Content-Length must be an integer."},
                ) from exception
            if declared_length > MAX_IMAGE_BYTES:
                raise HTTPException(
                    status_code=status.HTTP_413_CONTENT_TOO_LARGE,
                    detail={"code": "image_too_large", "message": "The image may not exceed 5 MB."},
                )

        image_bytes = await request.body()
        try:
            return pose_validation.validate_image(image_bytes, media_type)
        except PoseImageInvalid as exception:
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_CONTENT,
                detail={"code": "invalid_pose_image", "message": str(exception)},
            ) from exception
        except PoseArtifactUnavailable as exception:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail={"code": "pose_model_unavailable", "message": str(exception)},
            ) from exception

    return application


app = create_app()
