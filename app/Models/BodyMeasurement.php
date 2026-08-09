<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'recorded_on', 'weight_kg', 'height_cm', 'chest_cm', 'waist_cm', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'chest_cm' => 'decimal:2',
            'waist_cm' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
