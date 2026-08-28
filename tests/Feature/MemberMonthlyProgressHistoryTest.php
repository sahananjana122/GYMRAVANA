<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\MonthlyProgressReview;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemberMonthlyProgressHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-26 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_member_can_view_only_their_selected_month_progress_history(): void
    {
        $member = $this->member('Progress History Member');
        $otherMember = $this->member('Other Progress Member');

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => WorkoutPlan::firstOrFail()->id,
            'completed_on' => '2026-07-12',
            'points_awarded' => 37,
        ]);
        $member->bodyMeasurements()->create([
            'recorded_on' => '2026-07-02',
            'weight_kg' => 80,
            'waist_cm' => 92,
        ]);
        $member->bodyMeasurements()->create([
            'recorded_on' => '2026-07-28',
            'weight_kg' => 78.5,
            'waist_cm' => 90,
        ]);
        $otherMember->bodyMeasurements()->create([
            'recorded_on' => '2026-07-15',
            'weight_kg' => 345,
        ]);

        $response = $this->actingAs($member)
            ->get(route('member.progress.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('July 2026')
            ->assertSee('Historical progress records are now functional.')
            ->assertSee('80.00 kg')
            ->assertSee('78.50 kg')
            ->assertDontSee('345.00 kg');

        $response->assertViewHas('monthlyProgress', function (array $progress): bool {
            return $progress['key'] === '2026-07'
                && $progress['workouts'] === 1
                && $progress['wellness'] === 0
                && $progress['points'] === 37
                && $progress['active_days'] === 1
                && $progress['days_considered'] === 31
                && $progress['consistency_percent'] === 3
                && $progress['measurements']->count() === 2
                && $progress['weight_change'] === -1.5
                && $progress['waist_change'] === -2.0
                && $progress['previous_month'] === '2026-06'
                && $progress['next_month'] === '2026-08';
        });
    }

    public function test_private_trainer_review_notes_are_not_exposed_on_the_member_progress_page(): void
    {
        $member = $this->member();
        $trainer = TrainerProfile::approved()->firstOrFail();

        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'review_month' => '2026-08-01',
            'monthly_goals' => 'Trainer-only monthly goal.',
            'trainer_notes' => 'Private trainer assessment must remain hidden.',
            'ready_for_progression' => true,
            'readiness_rationale' => 'Private progression rationale must remain hidden.',
        ]);

        $this->actingAs($member)
            ->get(route('member.progress.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertDontSee('Trainer-only monthly goal.')
            ->assertDontSee('Private trainer assessment must remain hidden.')
            ->assertDontSee('Private progression rationale must remain hidden.');
    }

    public function test_future_months_and_unauthorized_roles_cannot_access_member_progress(): void
    {
        $member = $this->member();
        $trainer = TrainerProfile::approved()->firstOrFail()->user;

        $this->actingAs($member)
            ->get(route('member.progress.index', ['month' => '2026-09']))
            ->assertStatus(422);

        $this->actingAs($trainer)
            ->get(route('member.progress.index', ['month' => '2026-08']))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('member.progress.index'))->assertRedirect(route('login'));
    }

    private function member(string $name = 'Monthly Progress Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');
        MemberProfile::create([
            'user_id' => $member->id,
            'joined_at' => '2026-03-14',
            'status' => 'active',
        ]);

        return $member;
    }
}
