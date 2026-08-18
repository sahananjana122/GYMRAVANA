<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupProgramRegistration extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'attended', 'cancelled'];

    protected $fillable = [
        'group_program_id', 'user_id', 'name', 'email', 'phone', 'preferred_session', 'notes', 'status',
    ];

    public function groupProgram(): BelongsTo
    {
        return $this->belongsTo(GroupProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
