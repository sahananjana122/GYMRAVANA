<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_events_page_shows_active_events_only(): void
    {
        Event::query()->delete();

        $upcoming = Event::factory()->create(['title' => 'Public Endurance Day']);
        $past = Event::factory()->past()->create(['title' => 'Previous Community Social']);
        $hidden = Event::factory()->inactive()->create(['title' => 'Private Planning Event']);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee($upcoming->title)
            ->assertSee($past->title)
            ->assertDontSee($hidden->title);
    }

    public function test_non_admin_cannot_access_event_management(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.events.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_remove_an_event(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $startsAt = now()->addMonth()->startOfHour();

        $this->actingAs($admin)
            ->post(route('admin.events.store'), $this->payload([
                'title' => 'Assignment Fitness Festival',
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $startsAt->copy()->addHours(4)->format('Y-m-d H:i:s'),
            ]))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHasNoErrors();

        $event = Event::where('title', 'Assignment Fitness Festival')->firstOrFail();
        $this->assertSame('assignment-fitness-festival', $event->slug);
        $this->assertTrue($event->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.events.update', $event), $this->payload([
                'title' => 'Updated Fitness Festival',
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $startsAt->copy()->addHours(5)->format('Y-m-d H:i:s'),
                'is_featured' => '1',
            ], includeActive: false))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('updated-fitness-festival', $event->slug);
        $this->assertTrue($event->is_featured);
        $this->assertFalse($event->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'));

        $this->assertModelMissing($event);
    }

    public function test_event_end_time_cannot_be_before_its_start_time(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $startsAt = now()->addMonth()->startOfHour();

        $this->actingAs($admin)
            ->post(route('admin.events.store'), $this->payload([
                'title' => 'Invalid Date Event',
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $startsAt->copy()->subHour()->format('Y-m-d H:i:s'),
            ]))
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseMissing('events', ['title' => 'Invalid Date Event']);
    }

    private function payload(array $overrides = [], bool $includeActive = true): array
    {
        $payload = [
            'title' => 'Community Movement Event',
            'event_type' => 'community',
            'summary' => 'A public community movement event for members and guests.',
            'description' => 'A sufficiently detailed event description explaining the schedule and what participants can expect.',
            'starts_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
            'venue' => 'GymRAVANA Main Studio, Colombo',
            'capacity' => 60,
            'image_path' => null,
            'is_featured' => '0',
        ];

        if ($includeActive) {
            $payload['is_active'] = '1';
        }

        return array_replace($payload, $overrides);
    }
}
