<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::role('admin')->value('id');
        $event = Event::query()->active()->orderBy('starts_at')->first();

        Notice::updateOrCreate(
            ['slug' => 'welcome-to-the-gymravana-notice-board'],
            [
                'created_by' => $adminId,
                'type' => Notice::TYPE_ANNOUNCEMENT,
                'title' => 'Welcome to the GymRAVANA Notice Board',
                'summary' => 'Official gym announcements, achievements and monthly community highlights now have one home.',
                'body' => 'Check this page for published operational announcements and community updates from the GymRAVANA administration team.',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now(),
            ],
        );

        if ($event) {
            Notice::updateOrCreate(
                ['slug' => 'upcoming-community-event'],
                [
                    'created_by' => $adminId,
                    'event_id' => $event->id,
                    'type' => Notice::TYPE_EVENT,
                    'title' => 'Upcoming community event',
                    'summary' => 'A reminder to explore the next special GymRAVANA event and enquire early if capacity is limited.',
                    'body' => 'The full schedule, venue and event information are maintained in the Events section. Follow the event link for the latest details.',
                    'is_featured' => false,
                    'is_published' => true,
                    'published_at' => now(),
                ],
            );
        }
    }
}
