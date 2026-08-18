<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    public const TYPES = ['nadi', 'yoga_therapy', 'other'];

    protected $fillable = ['therapy_category_id', 'name', 'slug', 'treatment_type', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function therapyCategory(): BelongsTo
    {
        return $this->belongsTo(TherapyCategory::class);
    }

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(TherapyCondition::class, 'condition_treatment')
            ->withPivot(['rationale', 'priority']);
    }

    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(TherapySpecialist::class, 'specialist_treatment');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(TherapyAppointment::class);
    }
}
