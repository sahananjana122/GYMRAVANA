<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'workout_plan_id', 'completed_on', 'notes', 'points_awarded',
    ];

    protected function casts(): array
    {
        return ['completed_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }
}
