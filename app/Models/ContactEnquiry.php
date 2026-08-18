<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEnquiry extends Model
{
    public const STATUSES = ['new', 'in_progress', 'resolved', 'closed'];

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'subject', 'message', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
