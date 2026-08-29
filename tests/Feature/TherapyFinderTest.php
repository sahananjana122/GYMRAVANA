<?php

namespace Tests\Feature;

use App\Models\TherapyAppointment;
use App\Models\TherapyCondition;
use App\Models\TherapySpecialist;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapyFinderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_open_the_finder_and_see_active_conditions(): void
    {
        $this->get(route('therapy-finder.index'))
            ->assertOk()
            ->assertSee('What would you like support with?')
            ->assertSee('Back Stiffness')
            ->assertSee('educational wellness guidance');
    }

    public function test_condition_selection_shows_only_its_ranked_treatments(): void
    {
        $this->get(route('therapy-finder.index', ['condition' => 'stress-tension']))
            ->assertOk()
            ->assertSee('Full Body Relaxation')
            ->assertSee('Foot Massage')
            ->assertViewHas('treatments', fn ($treatments) => ! $treatments->contains('slug', 'deep-tissue-massage'));
    }

    public function test_unrelated_treatment_or_specialist_cannot_be_selected_through_the_url(): void
    {
        $this->get(route('therapy-finder.index', [
            'condition' => 'stress-tension',
            'treatment' => 'deep-tissue-massage',
        ]))->assertNotFound();

        $unrelatedSpecialist = $this->unrelatedSpecialist();

        $this->get(route('therapy-finder.index', [
            'condition' => 'back-stiffness',
            'treatment' => 'relaxa-neck-back-shoulder-muscle-pain',
            'specialist' => $unrelatedSpecialist->slug,
        ]))->assertNotFound();
    }

    public function test_guest_can_request_a_valid_therapy_appointment(): void
    {
        [$condition, $treatment, $specialist] = $this->validPathway();

        $response = $this->post(route('therapy-finder.store'), [
            'therapy_condition_id' => $condition->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => 'Visitor One',
            'contact_email' => 'visitor@example.test',
            'preferred_datetime' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Morning is preferred.',
        ]);

        $appointment = TherapyAppointment::firstOrFail();
        $response->assertRedirect(route('therapy-appointments.success', $appointment));
        $this->assertNull($appointment->user_id);
        $this->assertSame('pending', $appointment->status);
        $this->get(route('therapy-appointments.success', $appointment))
            ->assertOk()
            ->assertSee($appointment->appointment_number)
            ->assertSee($specialist->name);
    }

    public function test_signed_in_member_is_linked_to_their_appointment(): void
    {
        [$condition, $treatment, $specialist] = $this->validPathway();
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->post(route('therapy-finder.store'), [
            'therapy_condition_id' => $condition->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => $member->name,
            'contact_phone' => '+94 77 000 0000',
            'preferred_datetime' => now()->addDays(4)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('therapy_appointments', [
            'user_id' => $member->id,
            'contact_phone' => '+94 77 000 0000',
            'status' => 'pending',
        ]);
    }

    public function test_server_rejects_a_specialist_who_does_not_offer_the_treatment(): void
    {
        [$condition, $treatment] = $this->validPathway();
        $unrelatedSpecialist = $this->unrelatedSpecialist();

        $this->from(route('therapy-finder.index', ['condition' => $condition->slug]))
            ->post(route('therapy-finder.store'), [
                'therapy_condition_id' => $condition->id,
                'treatment_id' => $treatment->id,
                'therapy_specialist_id' => $unrelatedSpecialist->id,
                'customer_name' => 'Visitor Two',
                'contact_email' => 'two@example.test',
                'preferred_datetime' => now()->addDays(5)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('therapy_specialist_id');

        $this->assertDatabaseCount('therapy_appointments', 0);
    }

    public function test_admin_can_review_and_update_an_appointment(): void
    {
        [$condition, $treatment, $specialist] = $this->validPathway();
        $appointment = TherapyAppointment::create([
            'appointment_number' => fake()->uuid(),
            'therapy_condition_id' => $condition->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => 'Admin Test Client',
            'contact_email' => 'client@example.test',
            'preferred_datetime' => now()->addWeek(),
            'status' => 'pending',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.therapy-appointments.index'))
            ->assertOk()
            ->assertSee('Admin Test Client')
            ->assertSee($treatment->name);

        $this->actingAs($admin)
            ->patch(route('admin.therapy-appointments.update', $appointment), [
                'status' => 'confirmed',
                'confirmed_start_at' => now()->addWeek()->startOfHour()->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'required_arrival_at' => now()->addWeek()->startOfHour()->subMinutes(15)->format('Y-m-d H:i:s'),
                'preparation_instructions' => 'Wear comfortable clothing.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    /** @return array{TherapyCondition, Treatment, TherapySpecialist} */
    private function validPathway(): array
    {
        $condition = TherapyCondition::where('slug', 'back-stiffness')->firstOrFail();
        $treatment = Treatment::where('slug', 'relaxa-neck-back-shoulder-muscle-pain')->firstOrFail();
        $specialist = $treatment->specialists()->where('slug', 'whkt-nimesh')->firstOrFail();

        return [$condition, $treatment, $specialist];
    }

    private function unrelatedSpecialist(): TherapySpecialist
    {
        return TherapySpecialist::create([
            'name' => 'Unrelated Test Specialist',
            'slug' => 'unrelated-test-specialist',
            'specialization' => 'Testing only',
            'bio' => 'A test-only record with no assigned services.',
            'experience_years' => 0,
            'is_active' => true,
        ]);
    }
}
