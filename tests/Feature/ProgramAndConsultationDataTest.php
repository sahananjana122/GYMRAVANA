<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\GroupProgram;
use App\Models\GroupProgramRegistration;
use App\Models\TherapyAppointment;
use App\Models\TherapyCategory;
use App\Models\TherapyCondition;
use App\Models\TherapySpecialist;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgramAndConsultationDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_stage_one_tables_and_trainer_filter_fields_exist(): void
    {
        foreach ([
            'group_programs', 'group_program_registrations', 'treatments', 'therapy_conditions',
            'condition_treatment', 'therapy_specialists', 'specialist_treatment',
            'therapy_appointments', 'contact_enquiries',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected the {$table} table to exist.");
        }

        $this->assertTrue(Schema::hasColumns('trainer_profiles', ['gender', 'experience_years']));
    }

    public function test_seeded_condition_returns_ranked_treatments_and_specialists(): void
    {
        $condition = TherapyCondition::where('slug', 'back-stiffness')->firstOrFail();
        $recommended = $condition->treatments()->with('specialists')->get();

        $this->assertGreaterThanOrEqual(2, $recommended->count());
        $this->assertSame(1, $recommended->first()->pivot->priority);
        $this->assertTrue($recommended->contains(fn (Treatment $treatment) => $treatment->specialists->isNotEmpty()));
        $this->assertDatabaseHas('therapy_specialists', ['name' => 'W.H.K.T Nimesh', 'slug' => 'whkt-nimesh', 'is_active' => true]);
        $this->assertSame(6, TherapySpecialist::where('slug', 'whkt-nimesh')->firstOrFail()->treatments()->count());
    }

    public function test_real_therapy_catalogue_replaces_placeholder_services(): void
    {
        $expectedServices = [
            'Cupping Therapy',
            'Deep Tissue Massage',
            'Foot Massage',
            'Full Body Relaxation',
            'Relaxa for Neck, Back, Shoulder & Muscle Pain',
            'Trigger Point Release',
        ];

        $this->assertSame($expectedServices, Treatment::where('is_active', true)->orderBy('name')->pluck('name')->all());
        $this->assertSame($expectedServices, TherapyCategory::where('is_active', true)->orderBy('name')->pluck('name')->all());

        $nimesh = TherapySpecialist::where('slug', 'whkt-nimesh')->where('is_active', true)->firstOrFail();
        $this->assertSame($expectedServices, $nimesh->treatments()->where('is_active', true)->orderBy('name')->pluck('name')->all());
    }

    public function test_group_program_can_store_a_member_or_guest_registration(): void
    {
        $program = GroupProgram::where('slug', 'fat-burning-yoga-classes')->firstOrFail();
        $member = User::factory()->create();

        $registration = $program->registrations()->create([
            'user_id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'preferred_session' => 'Monday morning at 8:00 AM',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(GroupProgramRegistration::class, $registration);
        $this->assertTrue($registration->user->is($member));
        $this->assertTrue($registration->groupProgram->is($program));
    }

    public function test_therapy_appointment_connects_the_complete_wizard_selection(): void
    {
        $condition = TherapyCondition::where('slug', 'stress-tension')->firstOrFail();
        $treatment = $condition->treatments()->firstOrFail();
        $specialist = $treatment->specialists()->firstOrFail();

        $appointment = TherapyAppointment::create([
            'appointment_number' => (string) Str::uuid(),
            'therapy_condition_id' => $condition->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => 'Test Visitor',
            'contact_email' => 'visitor@example.test',
            'preferred_datetime' => now()->addWeek(),
            'status' => 'pending',
        ]);

        $this->assertTrue($appointment->condition->is($condition));
        $this->assertTrue($appointment->treatment->is($treatment));
        $this->assertTrue($appointment->specialist->is($specialist));
    }

    public function test_contact_enquiry_can_belong_to_a_signed_in_user_or_guest(): void
    {
        $guestEnquiry = ContactEnquiry::create([
            'name' => 'Guest Visitor',
            'email' => 'guest@example.test',
            'subject' => 'Programme question',
            'message' => 'Please share more information about the weekly schedule.',
            'status' => 'new',
        ]);

        $this->assertNull($guestEnquiry->user);
        $this->assertDatabaseHas('contact_enquiries', ['email' => 'guest@example.test', 'status' => 'new']);
        $this->assertSame(1, TherapySpecialist::where('is_active', true)->count());
    }
}
