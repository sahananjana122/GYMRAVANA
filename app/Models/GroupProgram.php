<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupProgram extends Model
{
    public const PUBLIC_SLUGS = [
        'fat-burning-yoga-classes',
        'zumba-classes',
        'special-yoga-meditation-class',
    ];

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

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('slug', self::PUBLIC_SLUGS);
    }

    public function scopeInDisplayOrder(Builder $query): Builder
    {
        return $query->orderByRaw(
            "CASE slug
                WHEN 'fat-burning-yoga-classes' THEN 1
                WHEN 'zumba-classes' THEN 2
                WHEN 'special-yoga-meditation-class' THEN 3
                ELSE 4
            END"
        );
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
