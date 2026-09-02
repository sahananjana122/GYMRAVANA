<?php

namespace Tests\Feature;

use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerDirectoryAndBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_directory_can_search_by_name_and_filter_by_specialty(): void
    {
        $this->get(route('trainers.index', ['search' => 'Pathum']))
            ->assertOk()
            ->assertSee('Pathum Weerakkodi')
            ->assertDontSee('N.T.D. Mendis');

        $this->get(route('trainers.index', ['specialty' => 'Strength, conditioning and yoga']))
            ->assertOk()
            ->assertSee('N.T.D. Mendis')
            ->assertDontSee('Pathum Weerakkodi');
    }

    public function test_directory_rejects_an_unknown_gender_filter(): void
    {
        $this->get(route('trainers.index', ['gender' => 'unknown']))
            ->assertSessionHasErrors('gender');
    }

    public function test_trainer_detail_displays_experience_certifications_and_programs(): void
    {
        $trainer = TrainerProfile::where('slug', 'ntd-mendis')->firstOrFail();

        $this->get(route('trainers.show', $trainer))
            ->assertOk()
            ->assertSee('12 years')
            ->assertSee('National Diploma in Sports Strength and Conditioning')
            ->assertSee('Book personal training');
    }

    public function test_seeded_trainer_photo_is_visible_in_the_trainer_dashboard_identity(): void
    {
        $trainer = TrainerProfile::where('slug', 'ntd-mendis')->firstOrFail();

        $this->actingAs($trainer->user)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee(asset($trainer->photo_path), false);
    }

    public function test_booking_requires_a_supported_program_type(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->post(route('trainers.book.store', $trainer), [
            'program_type' => 'Unsupported session',
            'requested_datetime' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('program_type');

        $this->assertSame(0, TrainerBooking::where('user_id', $member->id)->count());
    }

    public function test_member_booking_stores_program_type_and_rejects_an_exact_duplicate(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = User::factory()->create();
        $member->assignRole('member');
        $requestedTime = now()->addDays(4)->startOfHour()->format('Y-m-d H:i:s');
        $payload = [
            'program_type' => 'Fitness assessment',
            'requested_datetime' => $requestedTime,
            'notes' => 'I would like a beginner assessment.',
        ];

        $this->actingAs($member)->post(route('trainers.book.store', $trainer), $payload)
            ->assertRedirect(route('member.dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trainer_bookings', [
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Fitness assessment',
            'status' => 'pending',
        ]);

        $this->from(route('trainers.book', $trainer))->post(route('trainers.book.store', $trainer), $payload)
            ->assertRedirect(route('trainers.book', $trainer))
            ->assertSessionHasErrors('requested_datetime');

        $this->assertSame(1, TrainerBooking::where('user_id', $member->id)->count());
    }

    public function test_trainer_can_update_directory_filter_fields(): void
    {
        $profile = TrainerProfile::approved()->firstOrFail();

        $this->actingAs($profile->user)->patch(route('trainer.profile.update'), [
            'specialty' => 'Strength and recovery',
            'gender' => 'female',
            'experience_years' => 11,
            'bio' => $profile->bio,
            'certifications' => $profile->certifications,
            'availability' => $profile->availability,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trainer_profiles', [
            'id' => $profile->id,
            'specialty' => 'Strength and recovery',
            'gender' => 'female',
            'experience_years' => 11,
        ]);
    }
}
