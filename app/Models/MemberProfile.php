<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    protected $fillable = [
        'user_id',
        'membership_number',
        'membership_tier_id',
        'joined_at',
        'before_photo_path',
        'after_photo_path',
        'phone',
        'share_measurements_with_trainer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'share_measurements_with_trainer' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class);
    }
}
