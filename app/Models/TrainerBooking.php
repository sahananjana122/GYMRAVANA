<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerBooking extends Model
{
    public const STATUSES = ['pending', 'accepted', 'declined', 'completed', 'cancelled'];

    public const PROGRAM_TYPES = [
        'Personal training',
        'Fitness assessment',
        'Strength training',
        'Conditioning',
        'Mobility coaching',
        'Yoga session',
        'Other',
    ];

    protected $fillable = ['trainer_profile_id', 'user_id', 'program_type', 'requested_datetime', 'status', 'notes'];

    protected function casts(): array
    {
        return ['requested_datetime' => 'datetime'];
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
