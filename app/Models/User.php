<?php

namespace App\Models;

use App\Notifications\MembershipRegistrationCompletedNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\VerifyEmail;
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

    public function membershipSubscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function activeMembershipSubscription(): HasOne
    {
        return $this->hasOne(MembershipSubscription::class)
            ->where('status', MembershipSubscription::STATUS_ACTIVE)
            ->latestOfMany('activated_at');
    }

    public function membershipPayments(): HasMany
    {
        return $this->hasMany(MembershipPayment::class);
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

    public function memberMissions(): HasMany
    {
        return $this->hasMany(MemberMission::class);
    }

    public function memberAchievements(): HasMany
    {
        return $this->hasMany(MemberAchievement::class);
    }

    public function gameGoalProgress(): HasMany
    {
        return $this->hasMany(MemberGameGoalProgress::class);
    }

    public function recordedGameGoalProgress(): HasMany
    {
        return $this->hasMany(MemberGameGoalProgress::class, 'recorded_by');
    }

    public function progressionReadinessPredictions(): HasMany
    {
        return $this->hasMany(ProgressionReadinessPrediction::class);
    }

    public function latestProgressionReadinessPrediction(): HasOne
    {
        return $this->hasOne(ProgressionReadinessPrediction::class)->latestOfMany('predicted_at');
    }

    public function masterGateApplications(): HasMany
    {
        return $this->hasMany(MasterGateApplication::class);
    }

    public function reviewedMasterGateApplications(): HasMany
    {
        return $this->hasMany(MasterGateApplication::class, 'reviewed_by');
    }

    public function changedReadinessLabels(): HasMany
    {
        return $this->hasMany(ReadinessLabelRevision::class, 'changed_by');
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

    public function sendEmailVerificationNotification(): void
    {
        if ($this->hasRole('member')) {
            $subscription = $this->membershipSubscriptions()
                ->active()
                ->with('tier')
                ->latest('activated_at')
                ->first();
            $profile = $this->memberProfile()->first();

            if ($subscription && filled($profile?->membership_number)) {
                $this->setRelation('memberProfile', $profile);
                $this->notify(new MembershipRegistrationCompletedNotification($subscription));
            }

            return;
        }

        $this->notify(new VerifyEmail);
    }

    public function totalPoints(): int
    {
        return (int) $this->workoutCompletions()->sum('points_awarded')
            + (int) $this->wellnessCompletions()->sum('points_awarded');
    }
}
