<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterGateApplication extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_DECLINED,
        self::STATUS_WITHDRAWN,
        self::STATUS_REVOKED,
    ];

    protected $fillable = [
        'user_id',
        'progression_readiness_prediction_id',
        'status',
        'member_statement',
        'eligibility_snapshot',
        'requested_at',
        'reviewed_by',
        'review_notes',
        'is_override',
        'override_reason',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_snapshot' => 'array',
            'requested_at' => 'datetime',
            'is_override' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(ProgressionReadinessPrediction::class, 'progression_readiness_prediction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
