<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPlanItem extends Model
{
    protected $fillable = [
        'member_plan_id',
        'day_of_week',
        'scheduled_time',
        'section',
        'title',
        'instructions',
        'target',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MemberPlan::class, 'member_plan_id');
    }

    public function dayLabel(): string
    {
        return match ($this->day_of_week) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => 'Flexible day',
        };
    }

    public function timeLabel(): ?string
    {
        return $this->scheduled_time ? substr($this->scheduled_time, 0, 5) : null;
    }
}
