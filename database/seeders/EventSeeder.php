<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'title' => 'Moonlight Wellness Social',
                'slug' => 'moonlight-wellness-social',
                'event_type' => 'party',
                'summary' => 'An evening of light movement, music and community connection at the GymRAVANA studio.',
                'description' => 'A relaxed studio social for members and guests featuring an accessible mobility warm-up, music, refreshments and time to meet the coaching community.',
                'starts_at' => now()->addWeeks(6)->setTime(18, 30),
                'ends_at' => now()->addWeeks(6)->setTime(21, 30),
                'venue' => 'GymRAVANA Main Studio, Colombo',
                'capacity' => 80,
                'is_featured' => true,
            ],
            [
                'title' => 'Colombo Endurance Challenge',
                'slug' => 'colombo-endurance-challenge',
                'event_type' => 'endurance',
                'summary' => 'A coached outdoor endurance event with approachable distance options for different fitness levels.',
                'description' => 'Participants can choose a supported beginner or intermediate route. Coaches provide a group warm-up, pacing guidance, hydration checkpoints and a structured cool-down.',
                'starts_at' => now()->addWeeks(10)->setTime(6, 0),
                'ends_at' => now()->addWeeks(10)->setTime(12, 0),
                'venue' => 'Viharamahadevi Park, Colombo',
                'capacity' => 120,
                'is_featured' => false,
            ],
            [
                'title' => 'Mobility and Recovery Workshop',
                'slug' => 'mobility-recovery-workshop',
                'event_type' => 'workshop',
                'summary' => 'A practical small-group workshop covering warm-ups, mobility routines and post-training recovery.',
                'description' => 'GymRAVANA coaches demonstrate simple routines participants can use before and after training. The workshop is educational and suitable for beginners.',
                'starts_at' => now()->addWeeks(4)->setTime(9, 0),
                'ends_at' => now()->addWeeks(4)->setTime(11, 0),
                'venue' => 'GymRAVANA Mind Studio, Colombo',
                'capacity' => 30,
                'is_featured' => false,
            ],
        ] as $event) {
            Event::updateOrCreate(
                ['slug' => $event['slug']],
                $event + ['image_path' => null, 'is_active' => true],
            );
        }
    }
}
