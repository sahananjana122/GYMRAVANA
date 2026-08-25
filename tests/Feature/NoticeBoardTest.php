<?php

namespace Tests\Feature;

use App\Models\BodyMeasurement;
use App\Models\Event;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoticeBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_board_only_shows_notices_whose_publication_time_has_arrived(): void
    {
        Notice::query()->delete();

        $published = Notice::factory()->create(['title' => 'Published Gym Notice']);
        $draft = Notice::factory()->draft()->create(['title' => 'Private Draft Notice']);
        $scheduled = Notice::factory()->scheduled()->create(['title' => 'Future Scheduled Notice']);

        $this->get(route('notices.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($scheduled->title);
    }

    public function test_non_admin_cannot_access_notice_management(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.notices.index'))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('admin.notices.store'), $this->announcementPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_update_filter_and_delete_an_event_notice(): void
    {
        $admin = $this->admin();
        $event = Event::factory()->create(['title' => 'Linked Community Challenge']);

        $this->actingAs($admin)
            ->get(route('admin.notices.create'))
            ->assertOk()
            ->assertSee('Create a notice')
            ->assertSee('explicitly consented');

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), $this->announcementPayload([
                'type' => Notice::TYPE_EVENT,
                'title' => 'Challenge registration is open',
                'event_id' => $event->id,
                'is_published' => '1',
            ]))
            ->assertRedirect(route('admin.notices.index'))
            ->assertSessionHasNoErrors();

        $notice = Notice::where('title', 'Challenge registration is open')->firstOrFail();
        $this->assertSame($admin->id, $notice->created_by);
        $this->assertSame($event->id, $notice->event_id);
        $this->assertTrue($notice->is_published);

        $this->actingAs($admin)
            ->get(route('admin.notices.edit', $notice))
            ->assertOk()
            ->assertSee('Edit Challenge registration is open');

        $this->actingAs($admin)
            ->get(route('admin.notices.index', ['search' => 'Challenge', 'type' => Notice::TYPE_EVENT, 'status' => 'published']))
            ->assertOk()
            ->assertSee($notice->title);

        $this->actingAs($admin)
            ->patch(route('admin.notices.update', $notice), $this->announcementPayload([
                'type' => Notice::TYPE_ACHIEVEMENT,
                'title' => 'Community challenge completed',
                'is_featured' => '1',
            ]))
            ->assertRedirect(route('admin.notices.index'))
            ->assertSessionHasNoErrors();

        $notice->refresh();
        $this->assertSame(Notice::TYPE_ACHIEVEMENT, $notice->type);
        $this->assertNull($notice->event_id);
        $this->assertTrue($notice->is_featured);

        $this->actingAs($admin)
            ->delete(route('admin.notices.destroy', $notice))
            ->assertRedirect(route('admin.notices.index'));

        $this->assertModelMissing($notice);
    }

    public function test_published_client_photographs_require_explicit_consent_and_private_measurements_are_not_exposed(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $member = User::factory()->create(['name' => 'Consenting Member']);
        $member->assignRole('member');
        BodyMeasurement::create([
            'user_id' => $member->id,
            'recorded_on' => now()->toDateString(),
            'weight_kg' => 78.55,
        ]);

        $payload = $this->monthlyClientPayload($member, [
            'before_image' => UploadedFile::fake()->create('before.jpg', 100, 'image/jpeg'),
            'is_published' => '1',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), $payload)
            ->assertSessionHasErrors('photo_consent_confirmed');

        $this->assertDatabaseMissing('notices', ['title' => 'August client achievement']);

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), $this->monthlyClientPayload($member, [
                'cover_image' => UploadedFile::fake()->create('approved-cover.jpg', 100, 'image/jpeg'),
                'before_image' => UploadedFile::fake()->create('approved-before.jpg', 100, 'image/jpeg'),
                'photo_consent_confirmed' => '1',
                'is_published' => '1',
            ]))
            ->assertRedirect(route('admin.notices.index'))
            ->assertSessionHasNoErrors();

        $notice = Notice::where('title', 'August client achievement')->firstOrFail();
        $this->assertTrue($notice->photo_consent_confirmed);
        $this->assertSame($admin->id, $notice->photo_consent_confirmed_by);
        $this->assertNotNull($notice->photo_consent_confirmed_at);
        Storage::disk('public')->assertExists($notice->cover_image_path);
        Storage::disk('public')->assertExists($notice->before_image_path);

        $this->get(route('notices.index'))
            ->assertOk()
            ->assertSee($notice->title)
            ->assertSee($member->name)
            ->assertSee('Sessions attended')
            ->assertDontSee('78.55');

        $oldCoverPath = $notice->cover_image_path;
        $oldBeforePath = $notice->before_image_path;

        $this->actingAs($admin)
            ->patch(route('admin.notices.update', $notice), $this->announcementPayload([
                'type' => Notice::TYPE_ACHIEVEMENT,
                'title' => 'Public team achievement',
            ]))
            ->assertSessionHasNoErrors();

        $notice->refresh();
        $this->assertNull($notice->member_id);
        $this->assertNull($notice->cover_image_path);
        $this->assertNull($notice->before_image_path);
        $this->assertFalse($notice->photo_consent_confirmed);
        Storage::disk('public')->assertMissing($oldCoverPath);
        Storage::disk('public')->assertMissing($oldBeforePath);
    }

    public function test_only_one_monthly_best_client_can_be_selected_per_month(): void
    {
        $admin = $this->admin();
        $firstMember = User::factory()->create();
        $firstMember->assignRole('member');
        $secondMember = User::factory()->create();
        $secondMember->assignRole('member');

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), $this->monthlyClientPayload($firstMember))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), $this->monthlyClientPayload($secondMember, [
                'title' => 'Another August winner',
            ]))
            ->assertSessionHasErrors('highlight_month');

        $this->assertDatabaseMissing('notices', ['title' => 'Another August winner']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function announcementPayload(array $overrides = []): array
    {
        return array_replace([
            'type' => Notice::TYPE_ANNOUNCEMENT,
            'title' => 'Gym schedule announcement',
            'summary' => 'A short official schedule update for GymRAVANA members.',
            'body' => 'The administration team has published an updated operating schedule for the coming week.',
        ], $overrides);
    }

    private function monthlyClientPayload(User $member, array $overrides = []): array
    {
        return array_replace([
            'type' => Notice::TYPE_MONTHLY_CLIENT,
            'title' => 'August client achievement',
            'summary' => 'A consent-controlled community celebration.',
            'body' => 'This member maintained an excellent training routine throughout the month.',
            'member_id' => $member->id,
            'highlight_month' => '2026-08',
            'progress_summary' => 'The member consistently attended planned sessions and completed the agreed monthly goal.',
            'public_statistics' => "Sessions attended: 12\nGoal completion: 100%",
        ], $overrides);
    }
}
