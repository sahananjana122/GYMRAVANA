<?php

namespace Tests\Feature;

use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainerSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_trainer_agenda_and_calendar_only_show_their_own_bookings(): void
    {
        [$firstTrainer, $secondTrainer] = TrainerProfile::approved()->with('user')->take(2)->get();
        $ownMember = $this->member('Own Schedule Member');
        $otherMember = $this->member('Other Schedule Member');
        $ownBooking = $this->booking($firstTrainer, $ownMember);
        $this->booking($secondTrainer, $otherMember);

        $this->actingAs($firstTrainer->user)
            ->get(route('trainer.bookings.index'))
            ->assertOk()
            ->assertSee($ownMember->name)
            ->assertDontSee($otherMember->name)
            ->assertSee('Sessions & booking requests', false);

        $this->actingAs($firstTrainer->user)
            ->get(route('trainer.bookings.index', ['view' => 'calendar', 'month' => $ownBooking->requested_datetime->format('Y-m')]))
            ->assertOk()
            ->assertSee('Calendar');
    }

    public function test_trainer_cannot_manage_another_trainers_booking_and_member_cannot_access_schedules(): void
    {
        [$owner, $otherTrainer] = TrainerProfile::approved()->with('user')->take(2)->get();
        $member = $this->member();
        $booking = $this->booking($owner, $member);

        $this->actingAs($otherTrainer->user)
            ->patch(route('trainer.bookings.update', $booking), ['status' => TrainerBooking::STATUS_DECLINED])
            ->assertForbidden();

        $this->actingAs($member)->get(route('trainer.bookings.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.bookings.index'))->assertForbidden();
        $this->assertSame(TrainerBooking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_accepting_a_booking_requires_complete_schedule_details(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $booking = $this->booking($trainer, $this->member());

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $booking), ['status' => TrainerBooking::STATUS_ACCEPTED])
            ->assertSessionHasErrors(['confirmed_start_at', 'duration_minutes', 'required_arrival_at']);

        $this->assertSame(TrainerBooking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_trainer_can_confirm_session_and_member_sees_all_arrival_details(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $booking = $this->booking($trainer, $member);
        $start = now()->addDays(3)->startOfHour();

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $booking), $this->schedulePayload($start, [
                'duration_minutes' => 75,
                'preparation_instructions' => 'Bring water and wear comfortable training shoes.',
                'trainer_message' => 'Your assessment session is confirmed.',
            ]))
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(TrainerBooking::STATUS_ACCEPTED, $booking->status);
        $this->assertTrue($booking->confirmed_start_at->equalTo($start));
        $this->assertSame(75, $booking->duration_minutes);
        $this->assertSame($trainer->user_id, $booking->scheduled_by);
        $this->assertNotNull($booking->confirmed_at);

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee($start->format('d M Y, H:i'))
            ->assertSee('Please arrive by '.$start->copy()->subMinutes(15)->format('H:i'))
            ->assertSee('Bring water and wear comfortable training shoes.')
            ->assertSee('Your assessment session is confirmed.');
    }

    public function test_arrival_after_start_and_overlapping_sessions_are_rejected(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $first = $this->booking($trainer, $this->member('First Session Member'));
        $second = $this->booking($trainer, $this->member('Second Session Member'));
        $start = now()->addDays(4)->startOfHour();

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $first), $this->schedulePayload($start, [
                'required_arrival_at' => $start->copy()->addMinutes(5)->format('Y-m-d H:i:s'),
            ]))
            ->assertSessionHasErrors('required_arrival_at');

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $first), $this->schedulePayload($start, ['duration_minutes' => 90]))
            ->assertSessionHasNoErrors();

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $second), $this->schedulePayload($start->copy()->addMinutes(30)))
            ->assertSessionHasErrors('confirmed_start_at');

        $this->assertSame(TrainerBooking::STATUS_PENDING, $second->fresh()->status);
    }

    public function test_member_cannot_be_accepted_with_two_trainers_at_overlapping_times(): void
    {
        [$firstTrainer, $secondTrainer] = TrainerProfile::approved()->with('user')->take(2)->get();
        $member = $this->member();
        $first = $this->booking($firstTrainer, $member);
        $second = $this->booking($secondTrainer, $member);
        $start = now()->addDays(5)->startOfHour();

        $this->actingAs($firstTrainer->user)
            ->patch(route('trainer.bookings.update', $first), $this->schedulePayload($start))
            ->assertSessionHasNoErrors();

        $this->actingAs($secondTrainer->user)
            ->patch(route('trainer.bookings.update', $second), $this->schedulePayload($start->copy()->addMinutes(15)))
            ->assertSessionHasErrors('confirmed_start_at');
    }

    public function test_future_session_cannot_be_completed_but_past_session_can(): void
    {
        $now = Carbon::parse('2026-09-01 09:00:00');
        Carbon::setTestNow($now);
        $trainer = TrainerProfile::approved()->firstOrFail();
        $booking = $this->booking($trainer, $this->member(), $now->copy()->addDay());
        $start = $now->copy()->addDay()->startOfHour();

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $booking), $this->schedulePayload($start))
            ->assertSessionHasNoErrors();

        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $booking), $this->schedulePayload($start, ['status' => TrainerBooking::STATUS_COMPLETED]))
            ->assertSessionHasErrors('confirmed_start_at');

        Carbon::setTestNow($now->copy()->addDays(2));
        $this->actingAs($trainer->user)
            ->patch(route('trainer.bookings.update', $booking), $this->schedulePayload($start, ['status' => TrainerBooking::STATUS_COMPLETED]))
            ->assertSessionHasNoErrors();

        $this->assertSame(TrainerBooking::STATUS_COMPLETED, $booking->fresh()->status);
    }

    public function test_admin_can_schedule_any_booking_and_filter_the_booking_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member('Admin Scheduled Member');
        $booking = $this->booking($trainer, $member);
        $start = now()->addWeek()->startOfHour();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.update', $booking), $this->schedulePayload($start))
            ->assertSessionHasNoErrors();

        $this->assertSame($admin->id, $booking->fresh()->scheduled_by);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index', [
                'status' => TrainerBooking::STATUS_ACCEPTED,
                'trainer_profile_id' => $trainer->id,
                'date' => $start->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee($start->format('d M Y, H:i'));
    }

    private function member(string $name = 'Scheduling Test Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');

        return $member;
    }

    private function booking(TrainerProfile $trainer, User $member, ?Carbon $requestedTime = null): TrainerBooking
    {
        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => $requestedTime ?? now()->addDays(2)->startOfHour(),
            'status' => TrainerBooking::STATUS_PENDING,
            'notes' => 'Please focus on a safe beginner session.',
        ]);
    }

    private function schedulePayload(Carbon $start, array $overrides = []): array
    {
        return array_replace([
            'status' => TrainerBooking::STATUS_ACCEPTED,
            'confirmed_start_at' => $start->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'required_arrival_at' => $start->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
            'preparation_instructions' => 'Bring a water bottle.',
            'trainer_message' => 'See you at the gym.',
        ], $overrides);
    }
}
