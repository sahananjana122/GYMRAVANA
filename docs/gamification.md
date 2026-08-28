# Deterministic gamification rules

GymRAVANA's XP, levels, ranks and streaks are ordinary application calculations. They are not machine learning and must never be presented as AI output.

## Canonical data

No duplicate XP ledger is stored in the current foundation. The service calculates XP from existing canonical records, so historical workout and wellness completions are preserved and cannot be accidentally awarded twice during migration.

## XP sources

- Workout completion: the saved `points_awarded` value converts to XP at 1:1.
- Mind activity completion: the saved `points_awarded` value converts to XP at 1:1.
- Completed trainer session in the past: 25 XP.
- Unique monthly trainer review with 100% goal completion: 30 XP. Multiple trainers completing reviews for the same member/month still create only one reward.
- Every complete seven-day block in the member's longest activity streak: 20 XP.
- Completed quest or challenge: the XP reward frozen on that member's participation record when the published target is first reached.

The numerical rules live in `config/gamification.php`. Changing the configuration recalculates displayed XP for all members and must therefore be treated as a deliberate policy change.

## Quests and timed challenges

Administrators manage mission definitions at `/admin/gamification`. Members opt in at `/member/quests`.

- A quest may remain open without dates.
- A challenge requires a start date and end date.
- Progress starts when the member joins. Older records are intentionally excluded, even when their activity date falls inside the mission window.
- Qualifying records are workout completions, Mind-activity completions and completed trainer sessions. Missions may also count distinct activity days or the longest streak made from those records.
- Total XP and level cannot be mission metrics because rewarding XP from an XP target would create a circular scoring rule.
- Once one member joins, the mission type, metric, target, reward and date window are immutable. Administrators may still correct wording or archive the mission.
- A completed participation stores both `completed_at` and `reward_xp_awarded`. Re-running progress synchronisation cannot award it again.

Mission progress is refreshed after saved workouts, saved Mind activities, completed trainer sessions, monthly reviews, and when a member opens their dashboard or quest page. The browser never submits a progress number.

## Achievements

Achievements are automatic lifetime milestones. Active definitions may measure the mission metrics above, total XP, or member level.

- Members do not join achievements.
- An unlock is stored once in `member_achievements` with its progress and unlock time.
- Achievements award no XP, roles, permissions or Master access.
- After an unlock exists, its metric and threshold are immutable. The definition can be made inactive while preserving historical unlocks.

## Levels and ranks

Every 100 XP advances one level. Members begin at Level 1 with zero XP.

| Minimum level | Automatic rank |
| --- | --- |
| 1 | Initiate |
| 2 | Foundation |
| 4 | Challenger |
| 6 | Vanguard |
| 9 | Elite |

There is no automatic `Master` rank. The separate [Master Gate workflow](master-gate.md) combines configurable deterministic requirements, a genuine local-model result when available, and an auditable human decision.

## Streak definition

An active day is a date containing at least one workout completion, mind-activity completion or completed trainer session. Multiple activities on one date count as one active day.

A current streak contains consecutive dates and remains active when its latest date is today or yesterday. This gives a member the rest of the current day to maintain yesterday's streak. A gap of two or more days resets the current streak to zero; the historical longest streak remains visible.

## Excluded records

Therapy appointments/requests, body measurements, photographs, purchases, profile information, medical information and trainer AI-readiness labels do not award XP. This avoids incentivising sensitive health-service usage and keeps the score separate from the model target.
