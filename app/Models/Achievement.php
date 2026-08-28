<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    public const METRIC_TOTAL_XP = 'total_xp';

    public const METRIC_LEVEL = 'level';

    public const METRICS = [
        ...GamificationMission::METRICS,
        self::METRIC_TOTAL_XP,
        self::METRIC_LEVEL,
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'metric',
        'threshold',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(MemberAchievement::class);
    }

    public static function metricLabels(): array
    {
        return GamificationMission::metricLabels() + [
            self::METRIC_TOTAL_XP => 'Total XP',
            self::METRIC_LEVEL => 'Member level',
        ];
    }
}
