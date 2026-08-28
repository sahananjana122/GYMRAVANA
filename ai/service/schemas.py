from typing import Literal

from pydantic import BaseModel, ConfigDict, Field


class ReadinessFeatures(BaseModel):
    """The exact non-medical feature contract exported by Notebook 03."""

    model_config = ConfigDict(extra="forbid")

    workout_completions: int = Field(ge=0)
    wellness_completions: int = Field(ge=0)
    trainer_sessions_scheduled: int = Field(ge=0)
    trainer_sessions_completed: int = Field(ge=0)
    attendance_rate: float | None = Field(default=None, ge=0, le=1)
    cancelled_or_declined_sessions: int = Field(ge=0)
    active_days: int = Field(ge=0)
    consistency_rate: float = Field(ge=0, le=1)
    activity_points: int = Field(ge=0)
    previous_goal_completion: int | None = Field(default=None, ge=0, le=100)
    previous_rating: int | None = Field(default=None, ge=1, le=5)
    workout_change: int
    consistency_change: float = Field(ge=-1, le=1)
    previous_assessment: Literal["needs_support", "on_track", "excellent"] | None = None


class PredictionResponse(BaseModel):
    model_version: str
    predicted_ready: bool
    readiness_probability: float = Field(ge=0, le=1)
    decision_threshold: float = Field(ge=0, le=1)
    explanation: list[dict[str, str | float]]
    disclaimer: str


class PoseValidationResponse(BaseModel):
    mode: Literal["local_camera_validation_only"]
    model_version: str
    landmarks_detected: bool
    validation_accepted: bool
    quality_reason: str
    predicted_pose: str | None
    confidence: float | None = Field(default=None, ge=0, le=1)
    class_probabilities: dict[str, float]
    core_visibility: float | None = Field(default=None, ge=0, le=1)
    image_width: int = Field(ge=64, le=4096)
    image_height: int = Field(ge=64, le=4096)
    explanation: list[dict[str, str | float]]
    review_required: Literal[True]
    deployment_allowed: Literal[False]
    disclaimer: str
