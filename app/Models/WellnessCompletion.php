<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellnessCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'wellness_activity_id', 'completed_on', 'points_awarded',
    ];

    protected function casts(): array
    {
        return ['completed_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wellnessActivity(): BelongsTo
    {
        return $this->belongsTo(WellnessActivity::class);
    }
}
