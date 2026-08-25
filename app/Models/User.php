<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workoutCompletions(): HasMany
    {
        return $this->hasMany(WorkoutCompletion::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function wellnessCompletions(): HasMany
    {
        return $this->hasMany(WellnessCompletion::class);
    }

    public function therapyRequests(): HasMany
    {
        return $this->hasMany(TherapyRequest::class);
    }

    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function therapySpecialist(): HasOne
    {
        return $this->hasOne(TherapySpecialist::class);
    }

    public function enrolledServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'member_service')->withPivot('started_at');
    }

    public function trainerBookings(): HasMany
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function groupProgramRegistrations(): HasMany
    {
        return $this->hasMany(GroupProgramRegistration::class);
    }

    public function therapyAppointments(): HasMany
    {
        return $this->hasMany(TherapyAppointment::class);
    }

    public function contactEnquiries(): HasMany
    {
        return $this->hasMany(ContactEnquiry::class);
    }

    public function createdNotices(): HasMany
    {
        return $this->hasMany(Notice::class, 'created_by');
    }

    public function noticeHighlights(): HasMany
    {
        return $this->hasMany(Notice::class, 'member_id');
    }

    public function createdFinancialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'created_by');
    }

    public function dashboardRouteName(): string
    {
        return match (true) {
            $this->hasRole('admin') => 'admin.dashboard',
            $this->hasRole('trainer') => 'trainer.dashboard',
            $this->hasRole('therapist') => 'therapist.dashboard',
            $this->hasRole('member') => 'member.dashboard',
            default => 'dashboard',
        };
    }

    public function totalPoints(): int
    {
        return (int) $this->workoutCompletions()->sum('points_awarded')
            + (int) $this->wellnessCompletions()->sum('points_awarded');
    }
}
