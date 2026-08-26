<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\MembershipTier;
use App\Models\TherapySpecialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardNavigationPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_member_dashboard_uses_the_eight_required_navigation_destinations(): void
    {
        $member = $this->member();
        $response = $this->actingAs($member)->get(route('member.dashboard'))->assertOk();

        preg_match('/<nav[^>]+data-dashboard-primary-navigation[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertSame(8, substr_count($matches[1], '<a'));

        foreach ([
            route('home'),
            route('member.dashboard'),
            route('trainers.index'),
            route('member.progress.index'),
            route('member.library.index'),
            route('notifications.index'),
            route('member.meal-plan.index'),
            route('member.schedules.index'),
        ] as $destination) {
            $this->assertStringContainsString('href="'.$destination.'"', $matches[1]);
        }

        $response->assertSee('Welcome to My Gym')
            ->assertSee('Before')
            ->assertSee('After')
            ->assertSee('Joined 14 March 2026');
    }

    public function test_dedicated_member_pages_reuse_existing_private_data_services(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get(route('member.progress.index'))->assertOk()->assertSee('Monthly Tracking Sheet');
        $this->get(route('member.library.index'))->assertOk()->assertSee('Library & Movies', false);
        $this->get(route('member.meal-plan.index'))->assertOk()->assertSee('No active plan assigned');
        $this->get(route('member.schedules.index'))->assertOk()->assertSee('Trainer sessions')->assertSee('Therapy appointments');
    }

    public function test_member_progress_photos_are_validated_stored_and_rendered_without_cross_user_parameters(): void
    {
        Storage::fake('public');
        $member = $this->member();
        $onePixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($member)->patch(route('member.progress-photos.update'), [
            'before_photo' => UploadedFile::fake()->createWithContent('before.png', $onePixelPng),
            'after_photo' => UploadedFile::fake()->createWithContent('after.png', $onePixelPng),
        ])->assertRedirect();

        $profile = $member->memberProfile->fresh();
        Storage::disk('public')->assertExists($profile->before_photo_path);
        Storage::disk('public')->assertExists($profile->after_photo_path);
        $this->assertStringStartsWith('members/'.$member->id.'/progress/', $profile->before_photo_path);

        $this->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(Storage::url($profile->before_photo_path), false)
            ->assertSee(Storage::url($profile->after_photo_path), false);

        $this->patch(route('member.progress-photos.update'), [
            'before_photo' => UploadedFile::fake()->create('not-an-image.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('before_photo');
    }

    public function test_photo_and_member_portal_routes_remain_server_protected(): void
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $this->actingAs($trainer)->get(route('member.progress.index'))->assertForbidden();
        $this->patch(route('member.progress-photos.update'))->assertForbidden();

        auth()->logout();
        $this->get(route('member.schedules.index'))->assertRedirect(route('login'));
    }

    public function test_public_body_and_mind_choices_link_to_existing_catalogue_routes(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Body')
            ->assertSee('Mind')
            ->assertSee('href="'.route('programs.index').'"', false)
            ->assertSee('href="'.route('services.category', 'mind').'"', false);
    }

    public function test_admin_trainer_and_therapist_dashboards_share_the_navigation_and_identity_system(): void
    {
        $admin = User::factory()->create(['name' => 'Dashboard Administrator']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Administrator')
            ->assertSee('Administrator')
            ->assertSee('Admin command centre');

        $trainer = User::role('trainer')->whereHas('trainerProfile')->firstOrFail();
        $this->actingAs($trainer)->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee($trainer->name)
            ->assertSee('Personal Trainer')
            ->assertSee('Trainer dashboard');

        $therapist = User::factory()->create(['name' => 'Dashboard Therapist']);
        $therapist->assignRole('therapist');
        TherapySpecialist::firstOrFail()->update(['user_id' => $therapist->id]);
        $this->actingAs($therapist)->get(route('therapist.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Therapist')
            ->assertSee('Therapy Specialist')
            ->assertSee('Therapy dashboard');
    }

    private function member(): User
    {
        $member = User::factory()->create(['name' => 'Navigation Test Member']);
        $member->assignRole('member');
        MemberProfile::create([
            'user_id' => $member->id,
            'membership_tier_id' => MembershipTier::firstOrFail()->id,
            'joined_at' => '2026-03-14',
            'status' => 'active',
        ]);

        return $member;
    }
}
