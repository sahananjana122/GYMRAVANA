<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MembershipSubscription extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'membership_tier_id',
        'status',
        'amount_snapshot',
        'duration_months',
        'is_initial',
        'starts_on',
        'ends_on',
        'activated_at',
        'cancelled_at',
        'registration_notification_sent_at',
        'two_day_reminder_sent_at',
        'one_day_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_snapshot' => 'decimal:2',
            'duration_months' => 'integer',
            'is_initial' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'registration_notification_sent_at' => 'datetime',
            'two_day_reminder_sent_at' => 'datetime',
            'one_day_reminder_sent_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(MembershipPayment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
