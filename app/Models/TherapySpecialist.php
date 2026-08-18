<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TherapySpecialist extends Model
{
    protected $fillable = [
        'name', 'slug', 'gender', 'specialization', 'bio', 'qualifications',
        'experience_years', 'photo_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'specialist_treatment');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(TherapyAppointment::class);
    }
}
