<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    public const TYPES = [
        'party',
        'endurance',
        'workshop',
        'community',
        'other',
    ];

    protected $fillable = [
        'title',
        'slug',
        'event_type',
        'summary',
        'description',
        'starts_at',
        'ends_at',
        'venue',
        'capacity',
        'image_path',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }
}
