# Public dataset suitability audit

Reviewed on 2026-08-26 for the GymRAVANA progression-readiness prototype.

## Decision

No reviewed Kaggle or Hugging Face dataset is approved as a substitute for GymRAVANA's trainer-recorded `ready_for_progression` label.

Using a different outcome such as experience level, churn or recommended workout plan and renaming it “progression readiness” would change the machine-learning problem. It would also make reported model results misleading. No public dataset was downloaded or combined with private GymRAVANA data during this review.

## Candidate review

| Candidate | Published purpose and target | Decision for readiness training |
| --- | --- | --- |
| [Kaggle Gym Members Exercise Dataset](https://www.kaggle.com/datasets/valakhorasani/gym-members-exercise-dataset/data) | 973 exercise-profile rows; `Experience_Level` from beginner to expert; Apache 2.0 | Rejected. Experience level is not a trainer's monthly readiness decision. Most columns are demographic, body-measurement or heart-rate fields that GymRAVANA intentionally excludes. |
| [Kaggle Daily Gym Attendance and Workout Activity](https://www.kaggle.com/datasets/zahranusratt/daily-gym-attendance-and-workout-activity-dataset/data) | Daily attendance and workout activity; CC0 | Rejected for supervised readiness training because it has no progression-readiness target. It may be useful only as unrelated attendance-analysis practice. |
| [Kaggle Gym Member Churn](https://www.kaggle.com/datasets/hassaan2580/churn-prediction-gym-members-dataset) | Membership/activity records with a `Churn` target; CC0 | Rejected. Churn is a business-retention outcome, not training readiness, and the published schema includes unnecessary identity/demographic fields. |
| [Hugging Face SmartFit Dataset](https://huggingface.co/datasets/Tomertg/SmartFit_Dataset) | Synthetic profiles predicting a recommended plan; MIT | Rejected. It is explicitly synthetic, its target is unrelated, and its schema includes injury and body-profile information excluded by the GymRAVANA design. |
| [Hugging Face Completed Workouts](https://huggingface.co/datasets/KasparER/completed_workouts) | Seven text-based workout-feedback rows | Rejected. It is too small, has no readiness label and does not match the exported behavioral schema. |

## Why Hugging Face is not required here

Hugging Face is useful for hosting datasets and pretrained models, especially text, image and audio models. GymRAVANA's current problem is supervised classification over a small tabular dataset created from its own operational records. A scikit-learn baseline is more appropriate and easier to explain than adding a pretrained language model that does not solve the defined prediction problem.

This does not prohibit a future public dataset. A future candidate must have documented provenance and licensing, an equivalent trainer-assessed readiness target, compatible non-sensitive behavioral features, and a defensible relationship to GymRAVANA members. It must be evaluated separately before any merge or transfer-learning claim.

## Approved evidence source

The approved source remains the privacy-safe Laravel export produced by:

```powershell
php artisan gymravana:export-readiness-data
```

Notebook 02 may train only when this export contains both classes across enough distinct member groups. Synthetic rows may still test UI or code paths, but they must not be reported as genuine model evidence.
