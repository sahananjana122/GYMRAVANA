<?php

namespace Tests\Feature;

use App\Models\MonthlyProgressReview;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\Ai\DataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AiDataReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-26 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_sees_an_honest_blocked_state_when_no_labels_exist(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai-readiness.index'))
            ->assertOk()
            ->assertSee('AI Data Readiness')
            ->assertSee('Training blocked')
            ->assertSee('Ground-truth collection pipeline')
            ->assertSee('Create genuine trainer-member relationships first')
            ->assertSee('This screen never creates bookings')
            ->assertSee('No trainer-recorded readiness labels exist yet.')
            ->assertSee('No readiness-label changes have been recorded yet.')
            ->assertSee('php artisan gymravana:export-readiness-data');
    }

    public function test_ai_readiness_route_is_restricted_to_administrators(): void
    {
        $this->get(route('admin.ai-readiness.index'))
            ->assertRedirect(route('login'));

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.ai-readiness.index'))
            ->assertForbidden();
    }

    public function test_service_requires_total_class_and_distinct_member_gates(): void
    {
        config()->set('ai_readiness', [
            'minimum_rows' => 4,
            'minimum_rows_per_class' => 2,
            'minimum_member_groups' => 3,
            'minimum_member_groups_per_class' => 2,
        ]);
        $trainer = TrainerProfile::approved()->firstOrFail();
        $members = User::factory()->count(4)->create();

        foreach ($members as $member) {
            $member->assignRole('member');
        }

        $this->label($trainer, $members[0], true, '2026-06-01');
        $this->label($trainer, $members[1], true, '2026-07-01');
        $this->label($trainer, $members[2], false, '2026-08-01');

        $blocked = app(DataReadinessService::class)->summary();

        $this->assertFalse($blocked['training_allowed']);
        $this->assertSame(3, $blocked['counts']['total_rows']);
        $this->assertSame(1, $blocked['counts']['not_ready_rows']);
        $this->assertSame(1, $blocked['counts']['not_ready_member_groups']);
        $this->assertFalse($blocked['checks'][2]['met']);
        $this->assertFalse($blocked['checks'][5]['met']);

        $this->label($trainer, $members[3], false, '2026-08-01');
        $ready = app(DataReadinessService::class)->summary();

        $this->assertTrue($ready['training_allowed']);
        $this->assertSame(4, $ready['counts']['total_rows']);
        $this->assertSame(2, $ready['counts']['ready_rows']);
        $this->assertSame(2, $ready['counts']['not_ready_rows']);
        $this->assertSame(4, $ready['counts']['member_groups']);
        $this->assertSame(1, $ready['counts']['trainers']);
        $this->assertSame(3, $ready['counts']['observation_months']);
        $this->assertTrue(collect($ready['checks'])->every('met'));
    }

    public function test_laravel_thresholds_match_notebook_two(): void
    {
        $notebook = file_get_contents(base_path('ai/notebooks/02_model_training_and_evaluation.ipynb'));

        $this->assertIsString($notebook);
        $this->assertSame(30, config('ai_readiness.minimum_rows'));
        $this->assertSame(10, config('ai_readiness.minimum_rows_per_class'));
        $this->assertSame(10, config('ai_readiness.minimum_member_groups'));
        $this->assertSame(5, config('ai_readiness.minimum_member_groups_per_class'));
        $this->assertStringContainsString('MIN_ROWS = 30', $notebook);
        $this->assertStringContainsString('MIN_ROWS_PER_CLASS = 10', $notebook);
        $this->assertStringContainsString('MIN_MEMBER_GROUPS = 10', $notebook);
        $this->assertStringContainsString('MIN_GROUPS_PER_CLASS = 5', $notebook);
    }

    public function test_collection_pipeline_does_not_treat_accounts_or_pending_requests_as_evidence(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = User::factory()->create();
        $member->assignRole('member');
        $this->booking($trainer, $member, TrainerBooking::STATUS_PENDING);

        $pipeline = app(DataReadinessService::class)->collectionPipeline();

        $this->assertSame(1, $pipeline['counts']['member_accounts']);
        $this->assertSame(1, $pipeline['counts']['pending_booking_requests']);
        $this->assertSame(0, $pipeline['counts']['valid_relationships']);
        $this->assertSame(0, $pipeline['counts']['assigned_members']);
        $this->assertSame(0, $pipeline['counts']['current_month_assessed_relationships']);
        $this->assertSame('relationships', $pipeline['next_action']['stage']);
    }

    public function test_collection_pipeline_counts_distinct_valid_pairs_and_current_month_labels(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = User::factory()->create();
        $member->assignRole('member');
        $this->booking($trainer, $member, TrainerBooking::STATUS_ACCEPTED);
        $this->booking($trainer, $member, TrainerBooking::STATUS_COMPLETED);

        $awaitingAssessment = app(DataReadinessService::class)->collectionPipeline();

        $this->assertSame(1, $awaitingAssessment['counts']['valid_relationships']);
        $this->assertSame(1, $awaitingAssessment['counts']['assigned_members']);
        $this->assertSame(1, $awaitingAssessment['counts']['current_month_needs_assessment']);
        $this->assertSame(0, $awaitingAssessment['assessment_percent']);
        $this->assertSame('assessments', $awaitingAssessment['next_action']['stage']);

        $this->label($trainer, $member, true, '2026-08-01');
        $assessed = app(DataReadinessService::class)->collectionPipeline();

        $this->assertSame(1, $assessed['counts']['valid_relationships']);
        $this->assertSame(1, $assessed['counts']['current_month_assessed_relationships']);
        $this->assertSame(0, $assessed['counts']['current_month_needs_assessment']);
        $this->assertSame(100, $assessed['assessment_percent']);
        $this->assertSame(1, $assessed['counts']['genuine_labels']);
        $this->assertSame('continue_collection', $assessed['next_action']['stage']);
    }

    public function test_label_without_valid_booking_is_not_reported_as_relationship_coverage(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = User::factory()->create();
        $member->assignRole('member');
        $this->label($trainer, $member, false, '2026-08-01');

        $pipeline = app(DataReadinessService::class)->collectionPipeline();

        $this->assertSame(1, $pipeline['counts']['genuine_labels']);
        $this->assertSame(0, $pipeline['counts']['valid_relationships']);
        $this->assertSame(0, $pipeline['counts']['current_month_assessed_relationships']);
        $this->assertSame('relationships', $pipeline['next_action']['stage']);
    }

    private function label(TrainerProfile $trainer, User $member, bool $ready, string $month): void
    {
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'review_month' => $month,
            'ready_for_progression' => $ready,
            'readiness_rationale' => $ready
                ? 'Consistent progression evidence observed by the trainer.'
                : 'The member needs more consistent training evidence.',
            'readiness_assessed_at' => now(),
        ]);
    }

    private function booking(TrainerProfile $trainer, User $member, string $status): TrainerBooking
    {
        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => now()->addDay(),
            'status' => $status,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
