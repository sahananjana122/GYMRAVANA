<?php

namespace Tests\Feature;

use App\Models\MonthlyProgressReview;
use App\Models\ProgressionReadinessPrediction;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\Ai\ReadinessFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPredictionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-27 10:00:00');
        Http::preventStrayRequests();
        config()->set('ai_inference.enabled', true);
        config()->set('ai_inference.base_url', 'http://127.0.0.1:8001');
        config()->set('ai_inference.connect_timeout_seconds', 1);
        config()->set('ai_inference.timeout_seconds', 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_prediction_action_is_restricted_to_administrators(): void
    {
        $member = $this->member();

        $this->post(route('admin.ai-readiness.members.predict', $member))
            ->assertRedirect(route('login'));

        $this->actingAs($member)
            ->post(route('admin.ai-readiness.members.predict', $member))
            ->assertForbidden();

        $this->assertDatabaseCount('progression_readiness_predictions', 0);
        Http::assertNothingSent();
    }

    public function test_admin_page_shows_service_state_and_prediction_candidates(): void
    {
        $member = $this->member(['name' => 'Prediction Candidate']);
        $this->assessment($member);
        Http::fake([
            'http://127.0.0.1:8001/health' => Http::response([
                'service' => 'available',
                'model' => 'ready',
                'ready' => true,
                'reason' => null,
                'model_version' => 'logistic_regression-reviewed123',
            ]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.ai-readiness.index'))
            ->assertOk()
            ->assertSee('Local prediction service')
            ->assertSee('Model ready')
            ->assertSee('Prediction Candidate')
            ->assertSee('Generate prediction')
            ->assertSee('supporting evidence only');
    }

    public function test_missing_trainer_assessment_stores_nothing_and_sends_nothing(): void
    {
        $member = $this->member();

        $this->actingAs($this->admin())
            ->post(route('admin.ai-readiness.members.predict', $member))
            ->assertRedirect(route('admin.ai-readiness.index'))
            ->assertSessionHasErrors('prediction');

        $this->assertDatabaseCount('progression_readiness_predictions', 0);
        Http::assertNothingSent();
    }

    public function test_missing_reviewed_model_stores_no_prediction(): void
    {
        $member = $this->member();
        $this->assessment($member);
        Http::fake([
            'http://127.0.0.1:8001/v1/readiness/predict' => Http::response([
                'detail' => [
                    'code' => 'model_unavailable',
                    'message' => 'Notebook 03 artifacts are missing.',
                ],
            ], 503),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.ai-readiness.members.predict', $member))
            ->assertRedirect(route('admin.ai-readiness.index'))
            ->assertSessionHasErrors('prediction');

        $this->assertDatabaseCount('progression_readiness_predictions', 0);
    }

    public function test_valid_response_creates_one_private_auditable_prediction_and_reuses_an_identical_result(): void
    {
        $member = $this->member([
            'name' => 'Private Prediction Member',
            'email' => 'private-prediction@example.test',
        ]);
        $review = $this->assessment($member);
        Http::fake([
            'http://127.0.0.1:8001/v1/readiness/predict' => Http::response($this->successfulResponse()),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.ai-readiness.members.predict', $member))
            ->assertRedirect(route('admin.ai-readiness.index'))
            ->assertSessionHas('status', 'A new advisory readiness prediction was recorded for human review.');

        $prediction = ProgressionReadinessPrediction::firstOrFail();
        $this->assertSame($member->id, $prediction->user_id);
        $this->assertSame($review->id, $prediction->monthly_progress_review_id);
        $this->assertSame('2026-08-01', $prediction->observation_month->toDateString());
        $this->assertSame('logistic_regression-reviewed123', $prediction->model_version);
        $this->assertTrue($prediction->predicted_ready);
        $this->assertSame('0.81234', $prediction->readiness_probability);
        $this->assertSame(64, strlen($prediction->input_fingerprint));
        $this->assertSame(ReadinessFeatureService::FEATURES, array_keys($prediction->feature_snapshot));
        $this->assertArrayNotHasKey('user_id', $prediction->feature_snapshot);
        $this->assertArrayNotHasKey('name', $prediction->feature_snapshot);
        $this->assertArrayNotHasKey('email', $prediction->feature_snapshot);
        $this->assertArrayNotHasKey('readiness_rationale', $prediction->feature_snapshot);
        $this->assertSame('global_permutation_importance', $prediction->explanation['method']);
        $this->assertSame('consistency_rate', $prediction->explanation['factors'][0]['feature']);

        $this->actingAs($admin)
            ->post(route('admin.ai-readiness.members.predict', $member))
            ->assertSessionHas('status', 'The identical reviewed model result was already recorded; no duplicate was created.');

        $this->assertDatabaseCount('progression_readiness_predictions', 1);
        Http::assertSentCount(2);
    }

    public function test_non_member_target_is_rejected_without_contacting_the_service(): void
    {
        $trainer = TrainerProfile::approved()->with('user')->firstOrFail()->user;

        $this->actingAs($this->admin())
            ->post(route('admin.ai-readiness.members.predict', $trainer))
            ->assertSessionHasErrors('prediction');

        $this->assertDatabaseCount('progression_readiness_predictions', 0);
        Http::assertNothingSent();
    }

    private function successfulResponse(): array
    {
        return [
            'model_version' => 'logistic_regression-reviewed123',
            'predicted_ready' => true,
            'readiness_probability' => 0.81234,
            'decision_threshold' => 0.5,
            'explanation' => [
                ['feature' => 'consistency_rate', 'global_permutation_importance' => 0.21],
            ],
            'disclaimer' => 'Advisory non-medical model output.',
        ];
    }

    private function assessment(User $member): MonthlyProgressReview
    {
        return MonthlyProgressReview::create([
            'trainer_profile_id' => TrainerProfile::approved()->firstOrFail()->id,
            'user_id' => $member->id,
            'review_month' => today()->startOfMonth(),
            'ready_for_progression' => true,
            'readiness_rationale' => 'Consistent training behavior supports a progression review.',
            'readiness_assessed_at' => now(),
        ]);
    }

    private function member(array $attributes = []): User
    {
        $member = User::factory()->create($attributes);
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
