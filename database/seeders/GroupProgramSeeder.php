<?php

namespace Database\Seeders;

use App\Models\GroupProgram;
use App\Models\TrainerProfile;
use Illuminate\Database\Seeder;

class GroupProgramSeeder extends Seeder
{
    public function run(): void
    {
        $trainerId = TrainerProfile::approved()->orderBy('id')->value('id');

        $programs = [
            [
                'name' => 'Fat Burning Yoga Classes',
                'slug' => 'fat-burning-yoga-classes',
                'description' => 'An active yoga class focused on movement, conditioning and sustainable calorie-burning practice.',
                'schedule_info' => "Monday, Wednesday & Friday\nMorning: 8:00 AM–9:00 AM\nEvening: 7:00 PM–8:00 PM",
                'level' => 'All ages',
                'duration_minutes' => 60,
                'capacity' => 20,
                'image_path' => 'images/landing/group-fat-burning-yoga-classes.jpg',
            ],
            [
                'name' => 'Zumba Classes',
                'slug' => 'zumba-classes',
                'description' => 'An energetic dance-fitness class with approachable, repeatable movement patterns.',
                'schedule_info' => "Tuesday & Friday\nMorning: 8:00 AM–9:00 AM\nEvening: 7:00 PM–8:00 PM",
                'level' => 'All ages',
                'duration_minutes' => 60,
                'capacity' => 24,
                'image_path' => 'images/landing/group-zumba-classes.jpg',
            ],
            [
                'name' => 'Special Yoga Meditation Class',
                'slug' => 'special-yoga-meditation-class',
                'description' => 'Balancing the Nadi System, Stress Relief, Anapanasati Meditation & Yoga Exercises.',
                'schedule_info' => "Saturday\n6:30 PM–8:00 PM",
                'level' => 'All ages',
                'duration_minutes' => 90,
                'capacity' => 20,
                'image_path' => 'images/landing/group-special-yoga-meditation-class.jpg',
            ],
        ];

        GroupProgram::whereNotIn('slug', GroupProgram::PUBLIC_SLUGS)
            ->update(['is_active' => false]);

        foreach ($programs as $program) {
            GroupProgram::updateOrCreate(
                ['slug' => $program['slug']],
                $program + [
                    'trainer_profile_id' => $trainerId,
                    'is_active' => true,
                ],
            );
        }
    }
}
