<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerBooking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const TRAINER_MANAGED_STATUSES = [
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const PROGRAM_TYPES = [
        'Personal training',
        'Fitness assessment',
        'Strength training',
        'Conditioning',
        'Mobility coaching',
        'Yoga session',
        'Other',
    ];

    protected $fillable = [
        'trainer_profile_id',
        'user_id',
        'program_type',
        'requested_datetime',
        'confirmed_start_at',
        'duration_minutes',
        'required_arrival_at',
        'status',
        'notes',
        'preparation_instructions',
        'trainer_message',
        'scheduled_by',
        'confirmed_at',
        'last_reminder_sent_at',
        'reminder_count',
    ];

    protected function casts(): array
    {
        return [
            'requested_datetime' => 'datetime',
            'confirmed_start_at' => 'datetime',
            'required_arrival_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'duration_minutes' => 'integer',
            'reminder_count' => 'integer',
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

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_start_at');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACCEPTED)
            ->where('confirmed_start_at', '>=', now());
    }

    public function isScheduled(): bool
    {
        return $this->confirmed_start_at !== null;
    }
}
