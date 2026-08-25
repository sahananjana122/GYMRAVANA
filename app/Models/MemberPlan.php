<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MemberPlan extends Model
{
    public const TYPE_WORKOUT = 'workout';

    public const TYPE_MEAL = 'meal';

    public const TYPES = [self::TYPE_WORKOUT, self::TYPE_MEAL];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'user_id',
        'trainer_profile_id',
        'created_by',
        'supersedes_plan_id',
        'type',
        'title',
        'overview',
        'start_date',
        'end_date',
        'status',
        'version',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'assigned_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supersededPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_plan_id');
    }

    public function newerVersion(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MemberPlanItem::class)
            ->orderByRaw('day_of_week IS NULL, day_of_week')
            ->orderBy('scheduled_time')
            ->orderBy('display_order');
    }

    public function scopeVisibleToMember(Builder $query): Builder
    {
        return $query->whereNot('status', self::STATUS_DRAFT);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query): void {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', today());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            });
    }
}
