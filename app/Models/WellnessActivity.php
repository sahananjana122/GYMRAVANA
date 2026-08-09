<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WellnessActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'category', 'description', 'duration_minutes', 'points', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function completions(): HasMany
    {
        return $this->hasMany(WellnessCompletion::class);
    }
}
