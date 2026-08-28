<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'achievement_id',
        'user_id',
        'progress_value',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_value' => 'integer',
            'unlocked_at' => 'datetime',
        ];
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
