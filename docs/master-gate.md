# Master Gate decision workflow

Master Gate is an auditable advanced-progression workflow. It is not a role, automatic rank or single machine-learning decision.

## Current application requirements

The policy values live in `config/master_gate.php`:

| Requirement | Current policy |
| --- | --- |
| Automatic level | Level 6 or higher |
| Completed timed challenges | At least 1 |
| Distinct active days | At least 30 |
| Historical activity streak | At least 7 days |
| Trainer assessment | `ready` assessment recorded within 120 days |

Members can request human review after these five transparent requirements pass. The request stores an immutable JSON snapshot of every current value and target.

## AI criterion

The final criteria list also contains a local progression-readiness prediction no more than 90 days old. Prediction rows belong in `progression_readiness_predictions` and include:

- model version;
- ready/not-ready result;
- optional probability;
- exact non-sensitive feature snapshot;
- optional structured explanation;
- prediction time.

Only an administrator can request a prediction from **AI Data Readiness**, and the action is enabled only when the localhost service confirms that a reviewed artifact package is ready. The server requires a genuine trainer assessment and stores nothing for disabled, offline, missing-model or malformed responses. Because no genuine model exists yet, the member and reviewer interfaces still display `Not evaluated`.

## Human decision and overrides

Every application remains pending until an authorized human records a decision. Administrators are the human reviewers in the current undergraduate MVP because the active role system contains no `master` account.

- A positive model result never grants access automatically.
- Approving while any current requirement is unmet is a human override.
- An override cannot be saved without a written reason.
- Decision notes, reviewer, decision time and the supporting prediction ID are stored.
- Approval may later be revoked with a new written review note.
- Approval does not change the member's Spatie role.

This design allows a dedicated Master account to be introduced later without rewriting application or prediction history.

## Routes and authorization

- Member status and applications: `/member/master-gate`
- Administrator review queue: `/admin/master-gate`
- Administrator AI readiness and controlled prediction action: `/admin/ai-readiness`

Both route groups use authentication and server-side Spatie role middleware. A member can only withdraw their own pending request.

## Privacy boundaries

The gate excludes diagnoses, therapy data, measurements, photographs, identity fields and purchases. Free-text trainer rationale remains human audit material and is not copied into the model feature snapshot.
