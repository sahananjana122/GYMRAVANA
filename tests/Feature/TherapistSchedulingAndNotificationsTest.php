<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\TherapyAppointment;
use App\Models\TherapySpecialist;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Notifications\SessionScheduleNotification;
use App\Services\SessionNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TherapistSchedulingAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_and_link_a_therapist_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $specialist = TherapySpecialist::whereNull('user_id')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.therapists.store'), [
                'therapy_specialist_id' => $specialist->id,
                'name' => 'Demo Therapist',
                'email' => 'therapist@example.test',
                'password' => 'Temporary123!',
                'password_confirmation' => 'Temporary123!',
            ])
            ->assertSessionHasNoErrors();

        $therapist = User::where('email', 'therapist@example.test')->firstOrFail();
        $this->assertTrue($therapist->hasRole('therapist'));
        $this->assertSame($therapist->id, $specialist->fresh()->user_id);
        $this->assertDatabaseHas('users', ['email' => 'therapist@example.test']);
    }

    public function test_creating_a_new_therapist_account_also_publishes_their_profile(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $photo = UploadedFile::fake()->createWithContent(
            'public-therapist.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nXQAAAAASUVORK5CYII='),
        );

        $this->actingAs($admin)
            ->post(route('admin.therapists.store'), [
                'name' => 'Public Therapist',
                'email' => 'public.therapist@example.test',
                'specialization' => 'Sports recovery therapist',
                'experience_years' => 9,
                'qualifications' => "Sports Massage Certificate\nRecovery Coaching",
                'bio' => 'Provides practical recovery support for active members.',
                'photo' => $photo,
                'password' => 'Temporary123!',
                'password_confirmation' => 'Temporary123!',
            ])
            ->assertSessionHasNoErrors();

        $therapist = User::where('email', 'public.therapist@example.test')->firstOrFail();
        $specialist = TherapySpecialist::where('user_id', $therapist->id)->firstOrFail();

        $this->assertTrue($therapist->hasRole('therapist'));
        $this->assertTrue($specialist->is_active);
        $this->assertSame('Sports recovery therapist', $specialist->specialization);
        Storage::disk('public')->assertExists($specialist->photo_path);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Public Therapist')
            ->assertSee('Sports recovery therapist')
            ->assertSee(Storage::url($specialist->photo_path), false);
    }

    public function test_linked_therapist_can_confirm_only_their_own_appointment_and_member_is_notified(): void
    {
        Notification::fake();
        [$therapist, $specialist] = $this->therapist();
        $member = $this->member();
        $appointment = $this->appointment($specialist, $member);
        $start = now()->addDays(3)->startOfHour();

        $this->actingAs($therapist)
            ->patch(route('therapist.appointments.update', $appointment), $this->schedulePayload($start))
            ->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame(TherapyAppointment::STATUS_CONFIRMED, $appointment->status);
        $this->assertSame($therapist->id, $appointment->scheduled_by);
        Notification::assertSentTo($member, SessionScheduleNotification::class, fn ($notification) => $notification->event === 'confirmation');

        [$otherTherapist, $otherSpecialist] = $this->therapist($this->additionalSpecialist());
        $otherAppointment = $this->appointment($otherSpecialist, $member);

        $this->actingAs($therapist)
            ->patch(route('therapist.appointments.update', $otherAppointment), $this->schedulePayload($start->copy()->addDay()))
            ->assertForbidden();

        $this->assertSame(TherapyAppointment::STATUS_PENDING, $otherAppointment->fresh()->status);
        $this->assertNotSame($therapist->id, $otherTherapist->id);
    }

    public function test_therapy_schedule_rejects_overlapping_therapist_or_client_appointments(): void
    {
        [$therapist, $specialist] = $this->therapist();
        $first = $this->appointment($specialist, $this->member('First Client'));
        $second = $this->appointment($specialist, $this->member('Second Client'));
        $start = now()->addDays(4)->startOfHour();

        $this->actingAs($therapist)
            ->patch(route('therapist.appointments.update', $first), $this->schedulePayload($start, ['duration_minutes' => 90]))
            ->assertSessionHasNoErrors();

        $this->actingAs($therapist)
            ->patch(route('therapist.appointments.update', $second), $this->schedulePayload($start->copy()->addMinutes(30)))
            ->assertSessionHasErrors('confirmed_start_at');
    }

    public function test_manual_therapy_reminder_is_rate_limited_and_recorded(): void
    {
        Notification::fake();
        [$therapist, $specialist] = $this->therapist();
        $member = $this->member();
        $appointment = $this->appointment($specialist, $member);
        $start = now()->addDays(2)->startOfHour();

        $this->actingAs($therapist)
            ->patch(route('therapist.appointments.update', $appointment), $this->schedulePayload($start))
            ->assertSessionHasNoErrors();

        Notification::fake();
        $this->actingAs($therapist)
            ->post(route('therapist.appointments.remind', $appointment))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $appointment->fresh()->reminder_count);
        Notification::assertSentTo($member, SessionScheduleNotification::class, fn ($notification) => $notification->event === 'reminder');

        $this->actingAs($therapist)
            ->post(route('therapist.appointments.remind', $appointment))
            ->assertSessionHasErrors('reminder');
    }

    public function test_guest_therapy_confirmation_uses_on_demand_email_notification(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $specialist = TherapySpecialist::firstOrFail();
        $appointment = $this->appointment($specialist, null, 'guest@example.test');

        $this->actingAs($admin)
            ->patch(route('admin.therapy-appointments.update', $appointment), $this->schedulePayload(now()->addDays(5)->startOfHour()))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            SessionScheduleNotification::class,
            fn ($notification, $channels, $notifiable) => $notification->event === 'confirmation'
                && in_array('mail', $channels, true)
                && array_key_exists('guest@example.test', $notifiable->routes['mail']),
        );
    }

    public function test_trainer_confirmation_and_manual_reminder_use_the_same_notification_service(): void
    {
        Notification::fake();
        $trainerProfile = TrainerProfile::approved()->with('user')->firstOrFail();
        $member = $this->member();
        MemberProfile::create(['user_id' => $member->id, 'phone' => '0771234567', 'status' => 'active']);
        $booking = TrainerBooking::create([
            'trainer_profile_id' => $trainerProfile->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => now()->addDays(2),
            'status' => TrainerBooking::STATUS_PENDING,
        ]);
        $start = now()->addDays(3)->startOfHour();

        $this->actingAs($trainerProfile->user)
            ->patch(route('trainer.bookings.update', $booking), [
                'status' => TrainerBooking::STATUS_ACCEPTED,
                'confirmed_start_at' => $start->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'required_arrival_at' => $start->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($member, SessionScheduleNotification::class, fn ($notification) => $notification->event === 'confirmation');
        $this->assertStringStartsWith('https://wa.me/94771234567?text=', app(SessionNotificationService::class)->trainerWhatsAppUrl($booking->fresh()));

        Notification::fake();
        $this->actingAs($trainerProfile->user)
            ->post(route('trainer.bookings.remind', $booking))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $booking->fresh()->reminder_count);
    }

    public function test_user_can_mark_only_their_own_database_notification_as_read(): void
    {
        $member = $this->member();
        $otherMember = $this->member('Other Member');
        $notification = $this->databaseNotification($member);
        $otherNotification = $this->databaseNotification($otherMember);

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Test session update');

        $this->actingAs($member)
            ->patch(route('notifications.read', $notification))
            ->assertSessionHasNoErrors();
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($member)
            ->patch(route('notifications.read', $otherNotification))
            ->assertForbidden();
    }

    /** @return array{User, TherapySpecialist} */
    private function therapist(?TherapySpecialist $specialist = null): array
    {
        $specialist ??= TherapySpecialist::whereNull('user_id')->firstOrFail();
        $therapist = User::factory()->create();
        $therapist->assignRole('therapist');
        $specialist->update(['user_id' => $therapist->id]);

        return [$therapist, $specialist->fresh()];
    }

    private function additionalSpecialist(): TherapySpecialist
    {
        $specialist = TherapySpecialist::create([
            'name' => 'Authorization Test Therapist',
            'slug' => 'authorization-test-therapist',
            'specialization' => 'Testing only',
            'bio' => 'A test-only provider used to verify appointment ownership rules.',
            'experience_years' => 0,
            'is_active' => true,
        ]);
        $specialist->treatments()->attach(
            TherapySpecialist::where('slug', 'whkt-nimesh')->firstOrFail()->treatments()->firstOrFail()->id,
        );

        return $specialist;
    }

    private function member(string $name = 'Therapy Client'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');

        return $member;
    }

    private function appointment(TherapySpecialist $specialist, ?User $member, ?string $email = null): TherapyAppointment
    {
        $treatment = $specialist->treatments()->firstOrFail();

        return TherapyAppointment::create([
            'appointment_number' => (string) Str::uuid(),
            'user_id' => $member?->id,
            'therapy_condition_id' => $treatment->conditions()->first()?->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => $member?->name ?? 'Guest Client',
            'contact_email' => $email ?? $member?->email,
            'contact_phone' => '+94 77 000 0000',
            'preferred_datetime' => now()->addDays(2),
            'status' => TherapyAppointment::STATUS_PENDING,
        ]);
    }

    private function schedulePayload(Carbon $start, array $overrides = []): array
    {
        return array_replace([
            'status' => TherapyAppointment::STATUS_CONFIRMED,
            'confirmed_start_at' => $start->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'required_arrival_at' => $start->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
            'preparation_instructions' => 'Wear comfortable clothing.',
            'specialist_message' => 'Please contact us if you need to reschedule.',
        ], $overrides);
    }

    private function databaseNotification(User $user): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => SessionScheduleNotification::class,
            'data' => [
                'title' => 'Test session update',
                'summary' => 'A test notification.',
                'date_time' => '01 Sep 2026, 10:00',
                'url' => route('member.dashboard'),
            ],
        ]);
    }
}
