<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyProgressReview extends Model
{
    public const ASSESSMENT_NEEDS_SUPPORT = 'needs_support';

    public const ASSESSMENT_ON_TRACK = 'on_track';

    public const ASSESSMENT_EXCELLENT = 'excellent';

    public const ASSESSMENTS = [
        self::ASSESSMENT_NEEDS_SUPPORT,
        self::ASSESSMENT_ON_TRACK,
        self::ASSESSMENT_EXCELLENT,
    ];

    protected $fillable = [
        'trainer_profile_id',
        'user_id',
        'review_month',
        'monthly_goals',
        'goal_completion_percent',
        'rating',
        'assessment',
        'ready_for_progression',
        'readiness_rationale',
        'readiness_assessed_at',
        'trainer_notes',
        'next_month_goals',
    ];

    protected function casts(): array
    {
        return [
            'review_month' => 'date',
            'goal_completion_percent' => 'integer',
            'rating' => 'integer',
            'ready_for_progression' => 'boolean',
            'readiness_assessed_at' => 'datetime',
        ];
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function readinessLabelRevisions(): HasMany
    {
        return $this->hasMany(ReadinessLabelRevision::class);
    }
}
