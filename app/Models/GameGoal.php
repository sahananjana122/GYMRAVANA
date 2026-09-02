<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameGoal extends Model
{
    public const METRIC_DURATION = 'duration';
    public const METRIC_PERCENTAGE = 'percentage';
    public const METRIC_REPETITIONS = 'repetitions';
    public const METRIC_STABILITY = 'stability';
    public const METRIC_PACE_DURATION = 'pace_duration';

    public const METRICS = [
        self::METRIC_DURATION,
        self::METRIC_PERCENTAGE,
        self::METRIC_REPETITIONS,
        self::METRIC_STABILITY,
        self::METRIC_PACE_DURATION,
    ];

    public const VALIDATION_TRAINER = 'trainer_review';
    public const VALIDATION_AI_TRAINER = 'ai_and_trainer';
    public const VALIDATION_ACTIVITY = 'activity_record';

    public const VALIDATION_METHODS = [
        self::VALIDATION_TRAINER,
        self::VALIDATION_AI_TRAINER,
        self::VALIDATION_ACTIVITY,
    ];

    public const PACE_KMH = 'km_per_hour';
    public const PACE_MIN_KM = 'minutes_per_km';
    public const PACE_UNITS = [self::PACE_KMH, self::PACE_MIN_KM];

    protected $fillable = [
        'game_level_id', 'slug', 'exercise_name', 'metric_type', 'target_value',
        'pace_target', 'pace_unit', 'validation_method', 'instructions',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'pace_target' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(MemberGameGoalProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function requirementLabel(): string
    {
        $target = $this->formattedNumber($this->target_value);

        return match ($this->metric_type) {
            self::METRIC_DURATION => $target.' minutes',
            self::METRIC_PERCENTAGE => $target.'% form completion',
            self::METRIC_REPETITIONS => $target.' valid repetitions',
            self::METRIC_STABILITY => 'Stable form without unacceptable shaking',
            self::METRIC_PACE_DURATION => $target.' minutes continuously at '.$this->paceLabel(),
            default => $target,
        };
    }

    public function validationLabel(): string
    {
        return match ($this->validation_method) {
            self::VALIDATION_AI_TRAINER => 'AI evidence + trainer review',
            self::VALIDATION_ACTIVITY => 'Saved activity record',
            default => 'Trainer review',
        };
    }

    public function progressLabel(float $value, ?float $pace = null): string
    {
        $current = $this->formattedNumber($value);

        return match ($this->metric_type) {
            self::METRIC_DURATION => $current.' minutes',
            self::METRIC_PERCENTAGE => $current.'%',
            self::METRIC_REPETITIONS => $current.' valid repetitions',
            self::METRIC_STABILITY => $value >= 1 ? 'Verified stable' : 'Awaiting verification',
            self::METRIC_PACE_DURATION => $current.' minutes'.($pace !== null ? ' at '.$this->formattedNumber($pace).' '.$this->paceUnitLabel() : ''),
            default => $current,
        };
    }

    private function paceLabel(): string
    {
        if ($this->pace_target === null) {
            return 'the configured required pace';
        }

        return $this->formattedNumber($this->pace_target).' '.$this->paceUnitLabel();
    }

    private function paceUnitLabel(): string
    {
        return $this->pace_unit === self::PACE_MIN_KM ? 'min/km or faster' : 'km/h or faster';
    }

    private function formattedNumber(mixed $value): string
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? number_format($number, 0)
            : rtrim(rtrim(number_format($number, 2), '0'), '.');
    }
}
