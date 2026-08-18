<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = ['service_category_id', 'trainer_profile_id', 'name', 'slug', 'summary', 'description', 'benefits', 'tags', 'level', 'equipment', 'duration_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'tags' => 'array', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function trainerProfile(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_service')->withPivot('started_at');
    }
}
