<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TherapyCondition extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

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
        return $this->belongsToMany(Treatment::class, 'condition_treatment')
            ->withPivot(['rationale', 'priority'])
            ->orderByPivot('priority');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(TherapyAppointment::class);
    }
}
