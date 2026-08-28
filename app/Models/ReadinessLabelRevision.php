<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadinessLabelRevision extends Model
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const CLEARED = 'cleared';

    public $timestamps = false;

    protected $fillable = [
        'monthly_progress_review_id',
        'trainer_profile_id',
        'user_id',
        'changed_by',
        'change_type',
        'previous_label',
        'new_label',
        'previous_rationale',
        'new_rationale',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_label' => 'boolean',
            'new_label' => 'boolean',
            'changed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(MonthlyProgressReview::class, 'monthly_progress_review_id');
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
