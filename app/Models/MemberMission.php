<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMission extends Model
{
    use HasFactory;

    protected $fillable = [
        'gamification_mission_id',
        'user_id',
        'joined_at',
        'progress_value',
        'completed_at',
        'reward_xp_awarded',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'completed_at' => 'datetime',
            'progress_value' => 'integer',
            'reward_xp_awarded' => 'integer',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(GamificationMission::class, 'gamification_mission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
