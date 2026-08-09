<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WorkoutPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['member', 'trainer', 'master', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $workouts = [
            ['title' => 'Beginner Full Body', 'description' => 'Three rounds of bodyweight squats, wall push-ups, glute bridges and a gentle plank. Rest when needed and prioritize safe form.', 'difficulty' => 'beginner', 'duration_minutes' => 25, 'points' => 10],
            ['title' => 'Upper Body Foundation', 'description' => 'A simple push and pull session using resistance bands or light weights. Stop if you experience pain.', 'difficulty' => 'beginner', 'duration_minutes' => 30, 'points' => 12],
            ['title' => 'Conditioning Circuit', 'description' => 'A moderate circuit of step-ups, lunges, mountain climbers and recovery walking for members familiar with basic exercise.', 'difficulty' => 'intermediate', 'duration_minutes' => 35, 'points' => 18],
        ];

        foreach ($workouts as $workout) {
            WorkoutPlan::updateOrCreate(['title' => $workout['title']], $workout + ['is_active' => true]);
        }

        $activities = [
            ['title' => 'Five-minute breathing reset', 'category' => 'breathing', 'description' => 'Sit comfortably and follow slow, even breathing for five minutes.', 'duration_minutes' => 5, 'points' => 5],
            ['title' => 'Mindful body scan', 'category' => 'meditation', 'description' => 'Notice sensations from head to toe without judging or trying to change them.', 'duration_minutes' => 10, 'points' => 8],
            ['title' => 'Evening screen-free routine', 'category' => 'lifestyle', 'description' => 'Spend the final thirty minutes before sleep away from phone and computer screens.', 'duration_minutes' => 30, 'points' => 6],
        ];

        foreach ($activities as $activity) {
            WellnessActivity::updateOrCreate(['title' => $activity['title']], $activity + ['is_active' => true]);
        }

        $adminEmail = config('gymravana.demo_admin_email');
        $adminPassword = config('gymravana.demo_admin_password');

        if (app()->environment('local') && $adminEmail && $adminPassword) {
            $admin = User::updateOrCreate(
                ['email' => $adminEmail],
                ['name' => 'Demo Admin', 'password' => Hash::make($adminPassword), 'email_verified_at' => now()],
            );
            $admin->syncRoles(['admin']);
        }
    }
}
