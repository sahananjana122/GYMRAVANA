<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\GroupProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_new_public_content_pages_and_legacy_services_page_render(): void
    {
        $this->get(route('about.index'))->assertOk()->assertSee('Our vision');
        $this->get(route('programs.index'))->assertOk()->assertSee('Group energy');
        $this->get(route('group-programs.index'))->assertOk()->assertSee('Yoga Flow');
        $this->get(route('contact.index'))->assertOk()->assertSee('Send a message');
        $this->get(route('services.index'))->assertOk()->assertSee('Choose a path');
    }

    public function test_premium_landing_page_reuses_platform_content_and_real_routes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Your strongest chapter starts here.')
            ->assertSee('Gym Workout')
            ->assertSee('Yoga Flow')
            ->assertSee('Kavindi Perera')
            ->assertSee('Stress Relief')
            ->assertSee('Dr. Nirmala Jayasinghe')
            ->assertSee(route('therapy-finder.index'))
            ->assertSee(route('trainers.index'))
            ->assertSee('/images/landing/hero.jpg')
            ->assertDontSee('ddxfitness.ru');
    }

    public function test_guest_can_submit_a_contact_enquiry(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Public Visitor',
            'email' => 'visitor@example.test',
            'subject' => 'Group classes',
            'message' => 'Please share more information about beginner group classes.',
        ])->assertRedirect(route('contact.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_enquiries', [
            'user_id' => null,
            'email' => 'visitor@example.test',
            'status' => 'new',
        ]);
    }

    public function test_contact_form_rejects_invalid_details(): void
    {
        $this->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
        ])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_landing_contact_form_returns_to_the_contact_section(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Landing Visitor',
            'email' => 'landing@example.test',
            'subject' => 'Personal training',
            'message' => 'Please help me choose a suitable personal training starting point.',
            'source' => 'home',
        ])->assertRedirect(route('home').'#contact')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_enquiries', [
            'email' => 'landing@example.test',
            'subject' => 'Personal training',
            'status' => 'new',
        ]);
    }

    public function test_guest_and_member_group_program_requests_are_stored_correctly(): void
    {
        $program = GroupProgram::where('slug', 'yoga-flow')->firstOrFail();

        $this->post(route('group-programs.register', $program), [
            'name' => 'Guest Participant',
            'email' => 'guest-class@example.test',
            'preferred_session' => 'Tuesday at 18:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $member = User::factory()->create();
        $this->actingAs($member)->post(route('group-programs.register', $program), [
            'name' => $member->name,
            'email' => $member->email,
            'preferred_session' => 'Thursday at 18:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('group_program_registrations', ['group_program_id' => $program->id, 'user_id' => null, 'email' => 'guest-class@example.test']);
        $this->assertDatabaseHas('group_program_registrations', ['group_program_id' => $program->id, 'user_id' => $member->id, 'email' => $member->email]);
    }

    public function test_group_program_rejects_requests_after_capacity_is_reached(): void
    {
        $program = GroupProgram::where('slug', 'guided-meditation')->firstOrFail();
        $program->update(['capacity' => 1]);
        $program->registrations()->create([
            'name' => 'Existing Participant',
            'email' => 'existing@example.test',
            'status' => 'confirmed',
        ]);

        $this->from(route('group-programs.index'))->post(route('group-programs.register', $program), [
            'name' => 'Late Participant',
            'email' => 'late@example.test',
        ])->assertRedirect(route('group-programs.index'))->assertSessionHasErrors('group_program');

        $this->assertDatabaseMissing('group_program_registrations', ['email' => 'late@example.test']);
    }
}
