<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupProgram extends Model
{
    protected $fillable = [
        'trainer_profile_id', 'name', 'slug', 'description', 'schedule_info', 'level',
        'duration_minutes', 'capacity', 'image_path', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(GroupProgramRegistration::class);
    }
}
