<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainerProfile extends Model
{
    public const STATUSES = ['pending_review', 'approved', 'rejected'];

    protected $fillable = ['user_id', 'slug', 'specialty', 'gender', 'bio', 'certifications', 'experience_years', 'photo_path', 'availability', 'status'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TrainerBooking::class);
    }

    public function memberPlans(): HasMany
    {
        return $this->hasMany(MemberPlan::class);
    }

    public function monthlyProgressReviews(): HasMany
    {
        return $this->hasMany(MonthlyProgressReview::class);
    }

    public function groupPrograms(): HasMany
    {
        return $this->hasMany(GroupProgram::class);
    }
}
