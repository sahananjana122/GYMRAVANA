# Phase 7 AI data-readiness foundation

## Current status

GymRAVANA does not currently contain a trained progression-readiness model or Laravel prediction integration. The three-stage notebook workflow and fail-closed local FastAPI boundary are implemented, but the real database has no eligible labels and therefore no artifact has been produced. The Master Gate application and human-review workflow now exists, but its AI criterion honestly remains `Not evaluated` until a genuine local model creates a prediction record.

Phase 7 begins by collecting genuine trainer labels through the existing private monthly-review workflow. A trainer can record whether an assigned member appears ready for the next training progression and must explain the observed training evidence. The label is not shown publicly and does not unlock Master Gate by itself.

## Existing deterministic data

The application already records the following non-AI information:

- workout and wellness completions;
- transparent activity points;
- completed, accepted, cancelled and declined trainer bookings;
- distinct active days and monthly consistency;
- monthly goal completion, rating and assessment;
- trainer/member assignments through accepted or completed bookings.

Activity points and any future XP, levels, ranks, streaks, quests or achievements must remain deterministic application rules. They must not be described as machine learning.

## Supervised-learning observation

The proposed training row represents one member, one trainer and one completed review month.

The target label is:

`ready_for_progression`

Allowed values are `1` (ready), `0` (not ready yet), and `null` (not assessed). Only rows with a genuine `0` or `1` trainer label may be used for supervised training.

`readiness_rationale` supports human audit and label-quality review. It must not be passed to the first tabular model as a feature.

## Trainer collection workflow

Only a trainer with an accepted or completed booking relationship can review that member. In **Trainer → Monthly Tracker**, choose the observation month and use the readiness filter:

- **Needs assessment** shows assigned members without a usable `ready` or `not ready yet` decision for that month;
- **Assessed** shows members with a label and assessment timestamp;
- **Ready** and **Not ready yet** inspect each target class separately.

The progress strip shows assessed versus currently assigned members. The trainer dashboard deliberately reports ordinary review records and readiness labels as two different totals: saving goals or notes without a readiness decision does not count as supervised-learning evidence. Future-month reviews are rejected, and every non-null readiness decision requires a written behavioral rationale.

### Label revision provenance

The current label remains on `monthly_progress_reviews`, while each meaningful change creates an append-only `readiness_label_revisions` record. The audit records:

- whether the label was created, updated or cleared;
- the previous and new three-state decision;
- the previous and new private rationale;
- the responsible trainer account and change time.

Saving an unchanged label and rationale does not create a duplicate revision. Choosing **Not assessed** now correctly clears the label instead of converting it to **Not ready yet**. Administrators see the decision timeline under **AI Data Readiness**, but rationale text is deliberately omitted from that summary. Revision rows and every rationale remain excluded from the exported model dataset.

### Label-quality checks

The **AI Data Readiness** page also reports evidence-quality signals that simple row totals cannot reveal:

- contradictory `ready` and `not ready yet` labels for the same member and observation month;
- legacy rationales shorter than 20 characters;
- reviews with three or more recorded label/rationale changes;
- the percentage of labels supplied by the most represented trainer;
- the percentage belonging to the minority target class.

A contradictory member-month group is a hard training blocker even when all six numerical sufficiency gates pass. The page identifies the member, month, trainers and decisions without displaying either trainer's private rationale. New non-null labels require at least 20 rationale characters. Trainer concentration, class share and revision frequency are review signals rather than automatic evidence deletion; an administrator must interpret them in context.

Notebooks 01, 02 and 03 independently group the pseudonymous export by `member_key` and `observation_month`. They stop before analysis, training or export if more than one target value exists in a group. Adding more rows does not repair contradictory ground truth.

## Candidate feature schema

Only information available on or before the review month may be used:

| Feature | Source | Notes |
| --- | --- | --- |
| `workout_completions` | workout completions | Count in the observation month up to the label time |
| `wellness_completions` | wellness completions | Count in the observation month up to the label time |
| `trainer_sessions_scheduled` | trainer bookings | Accepted or completed sessions scheduled before the label |
| `trainer_sessions_completed` | trainer bookings | Completed sessions only |
| `attendance_rate` | trainer bookings | Completed divided by scheduled sessions considered |
| `cancelled_or_declined_sessions` | trainer bookings | Transparent negative attendance signal |
| `active_days` | completion and session dates | Distinct activity days |
| `consistency_rate` | active days | Active days divided by days considered |
| `activity_points` | completion records | Existing deterministic points, not AI output |
| `previous_goal_completion` | prior monthly review | Lagged value only |
| `previous_rating` | prior monthly review | Lagged value only |
| `previous_assessment` | prior monthly review | Lagged value only and encoded explicitly |
| `workout_change` | completion records | Current count minus previous-month count |
| `consistency_change` | activity dates | Current rate minus previous-month rate |

Database identifiers such as user ID and trainer profile ID are join keys, not model features.

## Excluded information

The first model must exclude:

- names, email addresses, phone numbers and profile photographs;
- injury, diagnosis, therapy request or therapy appointment information;
- body measurements and progress photographs;
- free-text trainer notes and readiness rationale;
- the current review's rating, assessment or goal completion when they were recorded at the same time as the readiness label;
- future activity or any information created after the label date.

These exclusions protect privacy and reduce target leakage.

## Versioned export and integrity check

Each exporter run writes one CSV and one matching metadata file. Metadata schema version `1` records the target, exact column order, row/class counts, source description and SHA-256 fingerprint of the CSV. Notebooks 01, 02 and 03 independently recalculate the fingerprint and reject the input before analysis when:

- either file is missing;
- the CSV has been edited since export;
- the CSV and metadata came from different exporter runs;
- the schema version, row count, columns or target do not match.

This makes an assignment experiment reproducible and prevents accidental evaluation of an unknown file. The fingerprint proves file integrity only; it does not prove that the trainer labels are truthful or representative.

## Known limitations

- The historical database has no readiness labels, so it cannot yet train a defensible supervised model.
- Demo or synthetic records may test software behavior but must not be presented as genuine training evidence.
- A model should not be trained until enough real labels exist to inspect missing values, duplicates and class balance.
- The active Spatie role system has no `master` role. Administrators are explicitly identified as the human reviewers for the undergraduate MVP; approval does not alter a member role.

## Admin data-readiness checkpoint

Administrators can open **Progression → AI Data Readiness** at `/admin/ai-readiness`. The screen reads the trainer-recorded labels from the database and shows why Notebook 02 currently permits or blocks model training. It is deliberately read-only: it does not create labels, train a model, save predictions, or approve a member.

The screen also exposes the complete ground-truth collection pipeline:

1. registered member accounts;
2. distinct trainer-member relationships backed by an accepted or completed booking;
3. those relationships with a timestamped readiness decision for the current month;
4. the total eligible readiness labels available to the guarded notebooks.

This distinction is important: a member account, a pending booking and an ordinary monthly note are not supervised-learning evidence. Multiple accepted/completed bookings for the same trainer and member count as one current relationship for monthly coverage. A label is included in the coverage figure only when that same trainer-member pair has a valid booking relationship. The page identifies the exact next operational action, but never auto-assigns a member, creates a booking or fills a missing outcome.

The minimum engineering gates are:

| Gate | Minimum |
| --- | ---: |
| Total labeled rows | 30 |
| Ready rows | 10 |
| Not-ready rows | 10 |
| Distinct members overall | 10 |
| Distinct members represented in the ready class | 5 |
| Distinct members represented in the not-ready class | 5 |

The Laravel values live in `config/ai_readiness.php` and tests verify that they match the constants in Notebook 02. Passing all six checks only allows prototype evaluation to begin. It does not prove label authenticity, sample representativeness, accuracy, fairness, safety, or client readiness. Demo and synthetic labels must never be counted as genuine assignment evidence even though software cannot determine a record's real-world provenance by itself.

## Implemented Master Gate boundary

The gate's deterministic eligibility, application snapshot and human decision audit are implemented separately from the model. See [the Master Gate workflow](../master-gate.md). The `progression_readiness_predictions` table is an integration contract, not evidence that a model exists. It cannot be populated through the member or administrator interfaces.

## Explainability and artifact gate

Notebook 02 writes `ai/artifacts/model_selection.json` only when its evidence gates, grouped holdout and grouped cross-validation all succeed. The report records the dataset SHA-256 fingerprint, exact feature order, selection rule and evaluation results; it contains no serialized model.

Notebook 03 refuses a missing, stale or incompatible report. After a valid handoff it uses permutation importance on the untouched grouped holdout and anonymized local sensitivity examples. SHAP is deliberately not added for the current empty/minimum-size data stage: permutation importance works for both baseline pipelines without introducing a heavier method that cannot yet be interpreted credibly. The explanations describe model behavior rather than causes.

Only then can Notebook 03 create the ignored local artifact package: `readiness_model.joblib`, `feature_schema.json`, `model_metrics.json`, `model_metadata.json`, and `feature_importance.json`. Model metadata contains the dataset fingerprint, serialized-model fingerprint, library version, intended use and limitations. These files do not activate Laravel inference by themselves. The [local inference service](local-inference-service.md) validates the complete package and otherwise remains unavailable.

Laravel's `ReadinessFeatureService` is now the single calculation path for both labeled exporter rows and live member snapshots. `ReadinessInferenceClient` is disabled by default, permits only loopback HTTP, sends exactly those 14 fields, and returns a typed unavailable result for offline, missing-model or invalid responses.

The administrator-only prediction workflow requires a member's genuine trainer assessment before contacting the service. A valid result is stored with its observation month, trainer-review link, exact private feature snapshot, explanation, model version and SHA-256 input fingerprint. The database prevents duplicate rows for an identical member/model/month/input request. Unavailable or malformed results store nothing. The current empty artifact directory therefore still produces no prediction.

## How to move forward

For now, members should request genuine sessions from the public trainer directory. A trainer or administrator must accept and schedule a real request before that trainer can assess the member. Assigned trainers should then enter honest monthly readiness assessments through **Monthly Tracker**. The administrator should monitor **AI Data Readiness** without trying to force the counters to pass. Once all six gates are met with real observations, run `php artisan gymravana:export-readiness-data`, open Notebook 01, and investigate every warning before running Notebook 02. Review Notebook 02's held-out and grouped cross-validation results before running Notebook 03. The local service will remain unavailable until Notebook 03 produces a defensible reviewed artifact package. See the [public dataset suitability audit](public-dataset-suitability.md) for why the reviewed Kaggle and Hugging Face candidates were rejected.
