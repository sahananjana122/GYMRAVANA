<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\MonthlyProgressReview;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WellnessCompletion;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyProgressTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-25 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tracker_uses_existing_member_activity_and_hides_measurements_without_consent(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);
        $this->activity($member);
        $member->bodyMeasurements()->create(['recorded_on' => '2026-08-02', 'weight_kg' => 81, 'waist_cm' => 94]);
        $member->bodyMeasurements()->create(['recorded_on' => '2026-08-20', 'weight_kg' => 79.5, 'waist_cm' => 92]);

        $this->actingAs($trainer->user)
            ->get(route('trainer.tracker.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSeeInOrder(['Workout completions', '1'])
            ->assertSeeInOrder(['Wellness activities', '1'])
            ->assertSeeInOrder(['Activity points', '30'])
            ->assertSee('The member has not enabled measurement-trend sharing.')
            ->assertDontSee('-1.50 kg');
    }

    public function test_member_can_explicitly_share_measurement_trends_with_assigned_trainers(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);
        $member->bodyMeasurements()->create(['recorded_on' => '2026-08-02', 'weight_kg' => 81, 'waist_cm' => 94]);
        $member->bodyMeasurements()->create(['recorded_on' => '2026-08-20', 'weight_kg' => 79.5, 'waist_cm' => 92]);

        $this->actingAs($member)
            ->patch(route('profile.update'), [
                'name' => $member->name,
                'email' => $member->email,
                'share_measurements_with_trainer' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($trainer->user)
            ->get(route('trainer.tracker.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('-1.50 kg')
            ->assertSee('-2.00 cm')
            ->assertDontSee('Raw member notes should stay private');
    }

    public function test_trainer_can_save_and_update_a_private_monthly_review(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);

        $payload = [
            'review_month' => '2026-08',
            'monthly_goals' => 'Complete four consistent training weeks.',
            'goal_completion_percent' => 80,
            'rating' => 4,
            'assessment' => MonthlyProgressReview::ASSESSMENT_ON_TRACK,
            'trainer_notes' => 'Good consistency with controlled technique.',
            'next_month_goals' => 'Add one mobility session each week.',
        ];

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trainer.tracker.index', ['month' => '2026-08', 'member_id' => $member->id]));

        $this->assertDatabaseHas('monthly_progress_reviews', [
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'rating' => 4,
            'goal_completion_percent' => 80,
        ]);
        $this->assertSame('2026-08-01', MonthlyProgressReview::firstOrFail()->review_month->format('Y-m-d'));

        $payload['rating'] = 5;
        $payload['trainer_notes'] = 'Updated review after the final session.';
        $this->actingAs($trainer->user)->put(route('trainer.tracker.update', $member), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, MonthlyProgressReview::count());
        $this->assertSame(5, MonthlyProgressReview::firstOrFail()->rating);
    }

    public function test_tracker_authorization_blocks_unassigned_trainers_members_and_future_reviews(): void
    {
        [$owner, $otherTrainer] = TrainerProfile::approved()->take(2)->get();
        $member = $this->member('Private Tracker Member');
        $unrelated = $this->member('Unrelated Tracker Member');
        $this->completedSession($owner, $member);

        $payload = [
            'review_month' => '2026-08',
            'monthly_goals' => 'Private goal',
            'trainer_notes' => 'Private review',
        ];
        $this->actingAs($otherTrainer->user)->put(route('trainer.tracker.update', $member), $payload)->assertForbidden();
        $this->actingAs($owner->user)
            ->get(route('trainer.tracker.index'))
            ->assertOk()
            ->assertSee($member->name)
            ->assertDontSee($unrelated->name);
        $this->actingAs($member)->get(route('trainer.tracker.index'))->assertForbidden();
        $this->actingAs($owner->user)
            ->put(route('trainer.tracker.update', $member), array_replace($payload, ['review_month' => '2026-09']))
            ->assertSessionHasErrors('review_month');
    }

    public function test_admin_can_inspect_reviews_but_members_cannot_access_admin_oversight(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'review_month' => '2026-08-01',
            'trainer_notes' => 'Admin-visible private operational review.',
            'rating' => 4,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.trainer-work.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee('Admin-visible private operational review.');
        $this->actingAs($member)->get(route('admin.trainer-work.index'))->assertForbidden();
    }

    private function member(string $name = 'Tracker Test Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');
        MemberProfile::create(['user_id' => $member->id, 'status' => 'active']);

        return $member;
    }

    private function completedSession(TrainerProfile $trainer, User $member): TrainerBooking
    {
        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => Carbon::parse('2026-08-10 10:00:00'),
            'confirmed_start_at' => Carbon::parse('2026-08-10 10:00:00'),
            'duration_minutes' => 60,
            'required_arrival_at' => Carbon::parse('2026-08-10 09:45:00'),
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);
    }

    private function activity(User $member): void
    {
        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => WorkoutPlan::firstOrFail()->id,
            'completed_on' => '2026-08-05',
            'points_awarded' => 20,
        ]);
        WellnessCompletion::create([
            'user_id' => $member->id,
            'wellness_activity_id' => WellnessActivity::firstOrFail()->id,
            'completed_on' => '2026-08-06',
            'points_awarded' => 10,
        ]);
    }
}
