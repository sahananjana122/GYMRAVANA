<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameLevel extends Model
{
    protected $fillable = [
        'number', 'name', 'description', 'is_active', 'unlocks_master_gate',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
            'unlocks_master_gate' => 'boolean',
        ];
    }

    public function goals(): HasMany
    {
        return $this->hasMany(GameGoal::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
