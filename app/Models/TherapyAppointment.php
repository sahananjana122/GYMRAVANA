<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapyAppointment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const THERAPIST_MANAGED_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'appointment_number', 'user_id', 'therapy_condition_id', 'treatment_id',
        'therapy_specialist_id', 'customer_name', 'contact_email', 'contact_phone',
        'preferred_datetime', 'confirmed_start_at', 'duration_minutes', 'required_arrival_at',
        'notes', 'preparation_instructions', 'specialist_message', 'status', 'scheduled_by',
        'confirmed_at', 'last_reminder_sent_at', 'reminder_count',
    ];

    protected function casts(): array
    {
        return [
            'preferred_datetime' => 'datetime',
            'confirmed_start_at' => 'datetime',
            'required_arrival_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'duration_minutes' => 'integer',
            'reminder_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'appointment_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(TherapyCondition::class, 'therapy_condition_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(TherapySpecialist::class, 'therapy_specialist_id');
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
            ->where('status', self::STATUS_CONFIRMED)
            ->where('confirmed_start_at', '>=', now());
    }

    public function isScheduled(): bool
    {
        return $this->confirmed_start_at !== null;
    }
}
