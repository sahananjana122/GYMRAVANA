<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapyRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'reviewed', 'scheduled', 'completed', 'cancelled'];

    protected $fillable = [
        'user_id', 'subject', 'symptoms', 'preferred_date', 'status', 'practitioner_notes',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
