<?php

namespace Tests\Feature;

use App\Models\GamificationMission;
use App\Models\MasterGateApplication;
use App\Models\MemberMission;
use App\Models\MonthlyProgressReview;
use App\Models\ProgressionReadinessPrediction;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MasterGateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-26 12:00:00');
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_member_sees_explainable_requirements_and_an_honest_missing_ai_state(): void
    {
        $member = $this->member();

        $this->actingAs($member)
            ->get(route('member.master-gate.index'))
            ->assertOk()
            ->assertSee('Master Gate')
            ->assertSee('Why you are or are not eligible')
            ->assertSee('Local AI readiness result')
            ->assertSee('Not evaluated')
            ->assertSee('No prediction exists because a genuine model has not yet been exported and integrated.')
            ->assertSee('The request form will become available')
            ->assertDontSee('Submit review request');
    }

    public function test_unqualified_member_cannot_submit_an_application(): void
    {
        $member = $this->member();

        $this->actingAs($member)
            ->post(route('member.master-gate.applications.store'), [
                'member_statement' => 'I would like advanced guidance.',
            ])
            ->assertSessionHasErrors('master_gate');

        $this->assertDatabaseCount('master_gate_applications', 0);
    }

    public function test_qualified_member_can_apply_once_and_only_withdraw_their_own_pending_request(): void
    {
        $member = $this->qualifiedMember();

        $this->actingAs($member)
            ->post(route('member.master-gate.applications.store'), [
                'member_statement' => 'I want a structured advanced progression review.',
            ])
            ->assertRedirect(route('member.master-gate.index'));

        $application = MasterGateApplication::firstOrFail();
        $this->assertSame(MasterGateApplication::STATUS_PENDING, $application->status);
        $this->assertNull($application->progression_readiness_prediction_id);
        $this->assertSame(6, count($application->eligibility_snapshot['criteria']));
        $this->assertTrue(collect($application->eligibility_snapshot['criteria'])->firstWhere('key', 'trainer_assessment')['met']);
        $this->assertFalse(collect($application->eligibility_snapshot['criteria'])->firstWhere('key', 'ai_readiness')['met']);

        $this->post(route('member.master-gate.applications.store'))
            ->assertSessionHasErrors('master_gate');
        $this->assertDatabaseCount('master_gate_applications', 1);

        $otherMember = $this->member();
        $this->actingAs($otherMember)
            ->patch(route('member.master-gate.applications.withdraw', $application))
            ->assertForbidden();

        $this->actingAs($member)
            ->patch(route('member.master-gate.applications.withdraw', $application))
            ->assertRedirect(route('member.master-gate.index'));
        $this->assertSame(MasterGateApplication::STATUS_WITHDRAWN, $application->fresh()->status);
    }

    public function test_admin_approval_without_ai_requires_an_explicit_human_override_reason(): void
    {
        $member = $this->qualifiedMember();
        $application = $this->applicationFor($member);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.master-gate.applications.decide', $application), [
                'decision' => MasterGateApplication::STATUS_APPROVED,
                'review_notes' => 'Reviewed the transparent training record.',
            ])
            ->assertSessionHasErrors('override_reason');

        $this->assertSame(MasterGateApplication::STATUS_PENDING, $application->fresh()->status);

        $this->patch(route('admin.master-gate.applications.decide', $application), [
            'decision' => MasterGateApplication::STATUS_APPROVED,
            'review_notes' => 'Reviewed the transparent training record and current trainer assessment.',
            'override_reason' => 'The local model is not yet trained; human evidence was reviewed for this academic demonstration.',
        ])->assertRedirect(route('admin.master-gate.index'));

        $application->refresh();
        $this->assertSame(MasterGateApplication::STATUS_APPROVED, $application->status);
        $this->assertTrue($application->is_override);
        $this->assertSame($admin->id, $application->reviewed_by);

        $this->actingAs($member)
            ->get(route('member.master-gate.index'))
            ->assertOk()
            ->assertSee('Master Gate access approved')
            ->assertSee('Human override');
    }

    public function test_current_ready_prediction_supports_but_does_not_make_the_final_decision(): void
    {
        $member = $this->qualifiedMember();
        $prediction = ProgressionReadinessPrediction::create([
            'user_id' => $member->id,
            'model_version' => 'academic-readiness-v1',
            'predicted_ready' => true,
            'readiness_probability' => 0.81234,
            'feature_snapshot' => ['active_days' => 30, 'workout_completions' => 30],
            'explanation' => ['summary' => 'Consistent recorded activity.'],
            'predicted_at' => now(),
        ]);
        $application = $this->applicationFor($member);
        $admin = $this->admin();

        $this->assertSame($prediction->id, $application->progression_readiness_prediction_id);
        $this->assertSame(MasterGateApplication::STATUS_PENDING, $application->status);

        $this->actingAs($admin)
            ->patch(route('admin.master-gate.applications.decide', $application), [
                'decision' => MasterGateApplication::STATUS_APPROVED,
                'review_notes' => 'All current criteria were reviewed by a human administrator.',
            ])
            ->assertRedirect(route('admin.master-gate.index'));

        $application->refresh();
        $this->assertSame(MasterGateApplication::STATUS_APPROVED, $application->status);
        $this->assertFalse($application->is_override);
        $this->assertSame($prediction->id, $application->progression_readiness_prediction_id);
    }

    public function test_admin_can_render_review_queue_and_revoke_a_previous_approval(): void
    {
        $member = $this->qualifiedMember();
        $application = $this->applicationFor($member);
        $admin = $this->admin();
        $application->update([
            'status' => MasterGateApplication::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'review_notes' => 'Initial approval.',
            'is_override' => true,
            'override_reason' => 'Initial documented override.',
            'decided_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-gate.index'))
            ->assertOk()
            ->assertSee('Master Gate Reviews')
            ->assertSee($member->name)
            ->assertSee('Revoke approval');

        $this->patch(route('admin.master-gate.applications.decide', $application), [
            'decision' => MasterGateApplication::STATUS_REVOKED,
            'review_notes' => 'Approval revoked after a documented human review.',
        ])->assertRedirect(route('admin.master-gate.index'));

        $this->assertSame(MasterGateApplication::STATUS_REVOKED, $application->fresh()->status);
    }

    public function test_master_gate_routes_are_role_protected(): void
    {
        $this->get(route('member.master-gate.index'))->assertRedirect(route('login'));

        $trainer = User::role('trainer')->firstOrFail();
        $this->actingAs($trainer)->get(route('member.master-gate.index'))->assertForbidden();
        $this->get(route('admin.master-gate.index'))->assertForbidden();

        $member = $this->member();
        $this->actingAs($member)->get(route('admin.master-gate.index'))->assertForbidden();
    }

    private function qualifiedMember(): User
    {
        $member = $this->member();
        $workout = WorkoutPlan::firstOrFail();

        foreach (range(0, 29) as $daysAgo) {
            WorkoutCompletion::create([
                'user_id' => $member->id,
                'workout_plan_id' => $workout->id,
                'completed_on' => today()->subDays($daysAgo),
                'points_awarded' => 20,
            ]);
        }

        $challenge = GamificationMission::where('kind', GamificationMission::KIND_CHALLENGE)->firstOrFail();
        MemberMission::create([
            'gamification_mission_id' => $challenge->id,
            'user_id' => $member->id,
            'joined_at' => now()->subMonth(),
            'progress_value' => $challenge->target_value,
            'completed_at' => now()->subDay(),
            'reward_xp_awarded' => $challenge->reward_xp,
        ]);

        $trainer = TrainerProfile::approved()->firstOrFail();
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'review_month' => today()->startOfMonth(),
            'ready_for_progression' => true,
            'readiness_rationale' => 'Consistent completion and progression behavior observed by the trainer.',
            'readiness_assessed_at' => now(),
        ]);

        return $member;
    }

    private function applicationFor(User $member): MasterGateApplication
    {
        $this->actingAs($member)
            ->post(route('member.master-gate.applications.store'), [
                'member_statement' => 'Please review my current progression record.',
            ])
            ->assertRedirect(route('member.master-gate.index'));

        return $member->masterGateApplications()->firstOrFail();
    }

    private function member(): User
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        return $member;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
