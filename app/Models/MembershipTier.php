<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTier extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'billing_period', 'features', 'is_featured', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'features' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    public function memberProfiles(): HasMany
    {
        return $this->hasMany(MemberProfile::class);
    }
}
