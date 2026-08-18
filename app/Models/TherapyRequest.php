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
        'user_id', 'therapy_category_id', 'name', 'contact_email', 'contact_phone', 'category',
        'preferred_datetime', 'notes', 'subject', 'symptoms', 'preferred_date', 'status', 'practitioner_notes',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'preferred_datetime' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function therapyCategory(): BelongsTo
    {
        return $this->belongsTo(TherapyCategory::class);
    }
}
