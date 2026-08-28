<?php

namespace Tests\Feature;

use App\Services\Ai\ReadinessFeatureService;
use App\Services\Ai\ReadinessInferenceClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiInferenceClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('ai_inference.base_url', 'http://127.0.0.1:8001');
        config()->set('ai_inference.connect_timeout_seconds', 1);
        config()->set('ai_inference.timeout_seconds', 1);
    }

    public function test_inference_is_disabled_by_default_and_sends_nothing(): void
    {
        config()->set('ai_inference.enabled', false);

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertFalse($result->available);
        $this->assertSame('disabled', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_client_rejects_non_loopback_urls_before_sending_private_features(): void
    {
        config()->set('ai_inference.enabled', true);
        config()->set('ai_inference.base_url', 'https://example.com/inference');

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertFalse($result->available);
        $this->assertSame('invalid_url', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_client_rejects_extra_or_missing_feature_fields_without_an_http_request(): void
    {
        config()->set('ai_inference.enabled', true);
        $features = $this->features();
        unset($features['active_days']);
        $features['member_name'] = 'Private name';

        $result = app(ReadinessInferenceClient::class)->predict($features);

        $this->assertFalse($result->available);
        $this->assertSame('feature_contract_mismatch', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_offline_service_fails_safely(): void
    {
        config()->set('ai_inference.enabled', true);
        Http::fake(['*' => Http::failedConnection('Connection refused')]);

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertFalse($result->available);
        $this->assertSame('service_offline', $result->errorCode);
        $this->assertNull($result->predictedReady);
    }

    public function test_model_unavailable_response_remains_not_evaluated(): void
    {
        config()->set('ai_inference.enabled', true);
        Http::fake([
            'http://127.0.0.1:8001/v1/readiness/predict' => Http::response([
                'detail' => [
                    'code' => 'model_unavailable',
                    'message' => 'A reviewed model artifact is missing.',
                ],
            ], 503),
        ]);

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertFalse($result->available);
        $this->assertSame('model_unavailable', $result->errorCode);
        $this->assertSame('A reviewed model artifact is missing.', $result->errorMessage);
        $this->assertNull($result->probability);
    }

    public function test_valid_local_response_is_returned_as_a_typed_result_without_identity_data(): void
    {
        config()->set('ai_inference.enabled', true);
        Http::fake([
            'http://127.0.0.1:8001/v1/readiness/predict' => Http::response([
                'model_version' => 'logistic_regression-abc123',
                'predicted_ready' => true,
                'readiness_probability' => 0.81234,
                'decision_threshold' => 0.5,
                'explanation' => [
                    ['feature' => 'consistency_rate', 'global_permutation_importance' => 0.21],
                ],
                'disclaimer' => 'Advisory non-medical model output.',
            ]),
        ]);

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertTrue($result->available);
        $this->assertTrue($result->predictedReady);
        $this->assertSame(0.81234, $result->probability);
        $this->assertSame('logistic_regression-abc123', $result->modelVersion);
        $this->assertNull($result->errorCode);
        Http::assertSent(function (Request $request): bool {
            $this->assertSame(ReadinessFeatureService::FEATURES, array_keys($request->data()));
            $this->assertArrayNotHasKey('user_id', $request->data());
            $this->assertArrayNotHasKey('member_name', $request->data());
            $this->assertArrayNotHasKey('readiness_rationale', $request->data());

            return $request->url() === 'http://127.0.0.1:8001/v1/readiness/predict';
        });
    }

    public function test_malformed_success_response_fails_safely(): void
    {
        config()->set('ai_inference.enabled', true);
        Http::fake([
            'http://127.0.0.1:8001/v1/readiness/predict' => Http::response([
                'predicted_ready' => true,
                'readiness_probability' => 4.2,
            ]),
        ]);

        $result = app(ReadinessInferenceClient::class)->predict($this->features());

        $this->assertFalse($result->available);
        $this->assertSame('invalid_response', $result->errorCode);
        $this->assertNull($result->predictedReady);
    }

    public function test_health_distinguishes_running_service_from_missing_model(): void
    {
        config()->set('ai_inference.enabled', true);
        Http::fake([
            'http://127.0.0.1:8001/health' => Http::response([
                'service' => 'available',
                'model' => 'unavailable',
                'ready' => false,
                'reason' => 'Notebook 03 artifacts are missing.',
                'model_version' => null,
            ]),
        ]);

        $health = app(ReadinessInferenceClient::class)->health();

        $this->assertTrue($health['service_available']);
        $this->assertFalse($health['model_ready']);
        $this->assertSame('Notebook 03 artifacts are missing.', $health['reason']);
    }

    private function features(): array
    {
        return [
            'workout_completions' => 12,
            'wellness_completions' => 5,
            'trainer_sessions_scheduled' => 4,
            'trainer_sessions_completed' => 3,
            'attendance_rate' => 0.75,
            'cancelled_or_declined_sessions' => 1,
            'active_days' => 14,
            'consistency_rate' => 0.45,
            'activity_points' => 290,
            'previous_goal_completion' => 80,
            'previous_rating' => 4,
            'workout_change' => 2,
            'consistency_change' => 0.08,
            'previous_assessment' => 'on_track',
        ];
    }
}
