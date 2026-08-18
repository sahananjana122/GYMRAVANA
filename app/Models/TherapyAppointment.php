<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapyAppointment extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    protected $fillable = [
        'appointment_number', 'user_id', 'therapy_condition_id', 'treatment_id',
        'therapy_specialist_id', 'customer_name', 'contact_email', 'contact_phone',
        'preferred_datetime', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return ['preferred_datetime' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'appointment_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(TherapyCondition::class, 'therapy_condition_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(TherapySpecialist::class, 'therapy_specialist_id');
    }
}
