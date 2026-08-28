<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\MonthlyProgressReview;
use App\Models\ReadinessLabelRevision;
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
            ->assertSeeInOrder(['Workout & mind XP', '30'], false)
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
            'ready_for_progression' => true,
            'readiness_rationale' => 'Attendance and technique are consistently strong.',
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
            'ready_for_progression' => true,
        ]);
        $this->assertSame('2026-08-01', MonthlyProgressReview::firstOrFail()->review_month->format('Y-m-d'));
        $this->assertSame('2026-08-25 12:00:00', MonthlyProgressReview::firstOrFail()->readiness_assessed_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('readiness_label_revisions', 1);
        $createdRevision = ReadinessLabelRevision::firstOrFail();
        $this->assertSame(ReadinessLabelRevision::CREATED, $createdRevision->change_type);
        $this->assertNull($createdRevision->previous_label);
        $this->assertTrue($createdRevision->new_label);
        $this->assertSame($trainer->user_id, $createdRevision->changed_by);

        $payload['rating'] = 5;
        $payload['ready_for_progression'] = false;
        $payload['readiness_rationale'] = 'Consistency is strong, but the current progression goal is not complete.';
        $payload['trainer_notes'] = 'Updated review after the final session.';
        Carbon::setTestNow('2026-08-25 13:00:00');
        $this->actingAs($trainer->user)->put(route('trainer.tracker.update', $member), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, MonthlyProgressReview::count());
        $this->assertSame(5, MonthlyProgressReview::firstOrFail()->rating);
        $this->assertFalse(MonthlyProgressReview::firstOrFail()->ready_for_progression);
        $this->assertSame('2026-08-25 13:00:00', MonthlyProgressReview::firstOrFail()->readiness_assessed_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('readiness_label_revisions', 2);
        $updatedRevision = ReadinessLabelRevision::latest('id')->firstOrFail();
        $this->assertSame(ReadinessLabelRevision::UPDATED, $updatedRevision->change_type);
        $this->assertTrue($updatedRevision->previous_label);
        $this->assertFalse($updatedRevision->new_label);

        Carbon::setTestNow('2026-08-25 14:00:00');
        $this->actingAs($trainer->user)->put(route('trainer.tracker.update', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('readiness_label_revisions', 2);
        $this->assertSame('2026-08-25 13:00:00', MonthlyProgressReview::firstOrFail()->readiness_assessed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-08-25 15:00:00');
        $payload['readiness_rationale'] = 'The decision remains unchanged, with newly recorded behavioral evidence.';
        $this->actingAs($trainer->user)->put(route('trainer.tracker.update', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('readiness_label_revisions', 3);
        $rationaleRevision = ReadinessLabelRevision::latest('id')->firstOrFail();
        $this->assertSame(ReadinessLabelRevision::UPDATED, $rationaleRevision->change_type);
        $this->assertFalse($rationaleRevision->previous_label);
        $this->assertFalse($rationaleRevision->new_label);
        $this->assertSame('2026-08-25 15:00:00', MonthlyProgressReview::firstOrFail()->readiness_assessed_at->format('Y-m-d H:i:s'));
    }

    public function test_clearing_a_readiness_label_preserves_an_admin_audit_entry(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member('Cleared Label Member');
        $this->completedSession($trainer, $member);

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => true,
                'readiness_rationale' => 'The recorded training behavior supports progression.',
            ])
            ->assertSessionHasNoErrors();
        Carbon::setTestNow('2026-08-26 09:00:00');
        $this->put(route('trainer.tracker.update', $member), [
            'review_month' => '2026-08',
            'ready_for_progression' => '',
            'readiness_rationale' => '',
        ])->assertSessionHasNoErrors();

        $review = MonthlyProgressReview::firstOrFail();
        $this->assertNull($review->ready_for_progression);
        $this->assertNull($review->readiness_rationale);
        $this->assertNull($review->readiness_assessed_at);
        $this->assertDatabaseCount('readiness_label_revisions', 2);
        $clearedRevision = ReadinessLabelRevision::latest('id')->firstOrFail();
        $this->assertSame(ReadinessLabelRevision::CLEARED, $clearedRevision->change_type);
        $this->assertTrue($clearedRevision->previous_label);
        $this->assertNull($clearedRevision->new_label);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.ai-readiness.index'))
            ->assertOk()
            ->assertSee('Label revision history')
            ->assertSee('Cleared Label Member')
            ->assertSee('Not assessed')
            ->assertDontSee('The recorded training behavior supports progression.');
    }

    public function test_progression_readiness_label_requires_a_human_rationale(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => true,
            ])
            ->assertSessionHasErrors('readiness_rationale');

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => true,
                'readiness_rationale' => 'Too short',
            ])
            ->assertSessionHasErrors('readiness_rationale');

        $this->assertDatabaseCount('monthly_progress_reviews', 0);

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'monthly_goals' => 'Collect more observations before assessing readiness.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(MonthlyProgressReview::firstOrFail()->ready_for_progression);
    }

    public function test_tracker_filters_assessed_members_from_members_still_needing_a_label(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $assessedMember = $this->member('Assessed Tracker Member');
        $pendingMember = $this->member('Pending Tracker Member');
        $this->completedSession($trainer, $assessedMember);
        $this->completedSession($trainer, $pendingMember);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $assessedMember->id,
            'review_month' => '2026-08-01',
            'ready_for_progression' => true,
            'readiness_rationale' => 'Consistent attendance and goal completion.',
            'readiness_assessed_at' => now(),
        ]);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $pendingMember->id,
            'review_month' => '2026-08-01',
            'monthly_goals' => 'A general review without a readiness decision.',
        ]);

        $pendingResponse = $this->actingAs($trainer->user)
            ->get(route('trainer.tracker.index', ['month' => '2026-08', 'readiness' => 'pending']))
            ->assertOk()
            ->assertSee('1 of 2 assigned members')
            ->assertSee('1 member still needs assessment')
            ->assertSee('data-tracker-member-id="'.$pendingMember->id.'"', false)
            ->assertDontSee('data-tracker-member-id="'.$assessedMember->id.'"', false);

        $this->assertStringContainsString('value="'.$assessedMember->id.'"', $pendingResponse->getContent());

        $this->get(route('trainer.tracker.index', ['month' => '2026-08', 'readiness' => 'assessed']))
            ->assertOk()
            ->assertSee('data-tracker-member-id="'.$assessedMember->id.'"', false)
            ->assertDontSee('data-tracker-member-id="'.$pendingMember->id.'"', false);
    }

    public function test_trainer_tracker_counts_readiness_labels_separately_from_general_reviews(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $assessedMember = $this->member('Dashboard Assessed Member');
        $pendingMember = $this->member('Dashboard Pending Member');
        $this->completedSession($trainer, $assessedMember);
        $this->completedSession($trainer, $pendingMember);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $assessedMember->id,
            'review_month' => '2026-08-01',
            'ready_for_progression' => false,
            'readiness_rationale' => 'More consistent activity is needed before progression.',
            'readiness_assessed_at' => now(),
        ]);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $pendingMember->id,
            'review_month' => '2026-08-01',
            'monthly_goals' => 'This review is intentionally not a readiness label.',
        ]);

        $this->actingAs($trainer->user)
            ->get(route('trainer.tracker.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('1 of 2 assigned members')
            ->assertSee('1 member still needs assessment')
            ->assertSee(route('trainer.tracker.index', ['month' => '2026-08', 'readiness' => 'pending']));
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
            'ready_for_progression' => true,
            'readiness_rationale' => 'Consistent attendance and completed monthly goals.',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.trainer-work.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee('Admin-visible private operational review.')
            ->assertSee('Progression label: Ready')
            ->assertSee('Consistent attendance and completed monthly goals.');
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
