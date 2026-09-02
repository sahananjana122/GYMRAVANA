<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberGameGoalProgress extends Model
{
    protected $table = 'member_game_goal_progress';

    protected $fillable = [
        'user_id', 'game_goal_id', 'current_value', 'pace_value', 'source',
        'evidence', 'recorded_by', 'recorded_at', 'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'pace_value' => 'decimal:2',
            'evidence' => 'array',
            'recorded_at' => 'datetime',
            'achieved_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(GameGoal::class, 'game_goal_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
