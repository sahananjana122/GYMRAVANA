<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GamificationMission extends Model
{
    use HasFactory;

    public const KIND_QUEST = 'quest';

    public const KIND_CHALLENGE = 'challenge';

    public const KINDS = [self::KIND_QUEST, self::KIND_CHALLENGE];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED];

    public const METRIC_WORKOUTS = 'workout_completions';

    public const METRIC_WELLNESS = 'wellness_completions';

    public const METRIC_TRAINER_SESSIONS = 'trainer_sessions';

    public const METRIC_ACTIVE_DAYS = 'active_days';

    public const METRIC_LONGEST_STREAK = 'longest_streak';

    public const METRICS = [
        self::METRIC_WORKOUTS,
        self::METRIC_WELLNESS,
        self::METRIC_TRAINER_SESSIONS,
        self::METRIC_ACTIVE_DAYS,
        self::METRIC_LONGEST_STREAK,
    ];

    protected $fillable = [
        'kind',
        'title',
        'slug',
        'description',
        'metric',
        'target_value',
        'reward_xp',
        'starts_on',
        'ends_on',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'target_value' => 'integer',
            'reward_xp' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(MemberMission::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isJoinable(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->starts_on?->isAfter(today())) {
            return false;
        }

        return ! $this->ends_on?->isBefore(today());
    }

    public static function metricLabels(): array
    {
        return [
            self::METRIC_WORKOUTS => 'Workout completions',
            self::METRIC_WELLNESS => 'Mind activity completions',
            self::METRIC_TRAINER_SESSIONS => 'Completed trainer sessions',
            self::METRIC_ACTIVE_DAYS => 'Distinct active days',
            self::METRIC_LONGEST_STREAK => 'Longest activity streak',
        ];
    }
}
