<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

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

    public function dashboardRouteName(): string
    {
        return match (true) {
            $this->hasRole('admin') => 'admin.dashboard',
            $this->hasRole('master') => 'master.dashboard',
            $this->hasRole('trainer') => 'trainer.dashboard',
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
