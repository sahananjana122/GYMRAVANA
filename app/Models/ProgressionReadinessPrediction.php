<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgressionReadinessPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'monthly_progress_review_id',
        'observation_month',
        'model_version',
        'predicted_ready',
        'readiness_probability',
        'feature_snapshot',
        'input_fingerprint',
        'explanation',
        'predicted_at',
    ];

    protected function casts(): array
    {
        return [
            'predicted_ready' => 'boolean',
            'readiness_probability' => 'decimal:5',
            'observation_month' => 'date',
            'feature_snapshot' => 'array',
            'explanation' => 'array',
            'predicted_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function monthlyReview(): BelongsTo
    {
        return $this->belongsTo(MonthlyProgressReview::class, 'monthly_progress_review_id');
    }

    public function gateApplications(): HasMany
    {
        return $this->hasMany(MasterGateApplication::class);
    }
}
