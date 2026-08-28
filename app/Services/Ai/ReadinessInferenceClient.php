<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class ReadinessInferenceClient
{
    public function __construct(private HttpFactory $http) {}

    public function health(): array
    {
        if (! config('ai_inference.enabled', false)) {
            return $this->healthUnavailable('disabled', 'Local AI inference is disabled.');
        }

        $endpoint = $this->endpoint('/health');

        if (! $endpoint) {
            return $this->healthUnavailable('invalid_url', 'The inference URL must use local HTTP loopback.');
        }

        try {
            $response = $this->request()->get($endpoint);
        } catch (Throwable) {
            return $this->healthUnavailable('service_offline', 'The local inference service is not reachable.');
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            return $this->healthUnavailable('invalid_response', 'The local inference service returned an invalid health response.');
        }

        return [
            'service_available' => ($payload['service'] ?? null) === 'available',
            'model_ready' => ($payload['model'] ?? null) === 'ready' && ($payload['ready'] ?? null) === true,
            'model_version' => is_string($payload['model_version'] ?? null) ? $payload['model_version'] : null,
            'reason' => is_string($payload['reason'] ?? null) ? $payload['reason'] : null,
            'error_code' => null,
        ];
    }

    public function predict(array $features): ReadinessInferenceResult
    {
        if (! config('ai_inference.enabled', false)) {
            return ReadinessInferenceResult::unavailable('disabled', 'Local AI inference is disabled.');
        }

        if (! $this->hasExactFeatureContract($features)) {
            return ReadinessInferenceResult::unavailable(
                'feature_contract_mismatch',
                'The generated feature snapshot does not match the approved AI contract.',
            );
        }

        $endpoint = $this->endpoint('/v1/readiness/predict');

        if (! $endpoint) {
            return ReadinessInferenceResult::unavailable(
                'invalid_url',
                'The inference URL must use local HTTP loopback.',
            );
        }

        try {
            $response = $this->request()->post($endpoint, $this->orderedFeatures($features));
        } catch (Throwable) {
            return ReadinessInferenceResult::unavailable(
                'service_offline',
                'The local inference service is not reachable.',
            );
        }

        if ($response->status() === 503) {
            return ReadinessInferenceResult::unavailable(
                'model_unavailable',
                $this->serviceErrorMessage($response, 'No reviewed local model is available.'),
            );
        }

        if (! $response->successful()) {
            return ReadinessInferenceResult::unavailable(
                'service_error',
                'The local inference service rejected the request.',
            );
        }

        return $this->successfulResult($response);
    }

    private function successfulResult(Response $response): ReadinessInferenceResult
    {
        $payload = $response->json();
        $probability = $payload['readiness_probability'] ?? null;
        $threshold = $payload['decision_threshold'] ?? null;
        $modelVersion = $payload['model_version'] ?? null;
        $explanation = is_array($payload['explanation'] ?? null)
            ? $this->validatedExplanation($payload['explanation'])
            : null;

        if (
            ! is_array($payload)
            || ! is_bool($payload['predicted_ready'] ?? null)
            || ! is_numeric($probability)
            || (float) $probability < 0
            || (float) $probability > 1
            || ! is_numeric($threshold)
            || (float) $threshold < 0
            || (float) $threshold > 1
            || ! is_string($modelVersion)
            || trim($modelVersion) === ''
            || strlen($modelVersion) > 100
            || $explanation === null
            || ! is_string($payload['disclaimer'] ?? null)
        ) {
            return ReadinessInferenceResult::unavailable(
                'invalid_response',
                'The local inference service returned an invalid prediction response.',
            );
        }

        return new ReadinessInferenceResult(
            available: true,
            predictedReady: $payload['predicted_ready'],
            probability: (float) $probability,
            decisionThreshold: (float) $threshold,
            modelVersion: $modelVersion,
            explanation: $explanation,
            disclaimer: $payload['disclaimer'],
        );
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->asJson()
            ->connectTimeout(max(1, (int) config('ai_inference.connect_timeout_seconds', 1)))
            ->timeout(max(1, (int) config('ai_inference.timeout_seconds', 3)));
    }

    private function endpoint(string $path): ?string
    {
        $baseUrl = rtrim((string) config('ai_inference.base_url', ''), '/');
        $parts = parse_url($baseUrl);

        if (
            $baseUrl === ''
            || $parts === false
            || ($parts['scheme'] ?? null) !== 'http'
            || ! in_array(strtolower((string) ($parts['host'] ?? '')), ['127.0.0.1', 'localhost', '::1', '[::1]'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            return null;
        }

        return $baseUrl.$path;
    }

    private function hasExactFeatureContract(array $features): bool
    {
        return count($features) === count(ReadinessFeatureService::FEATURES)
            && array_diff(array_keys($features), ReadinessFeatureService::FEATURES) === []
            && array_diff(ReadinessFeatureService::FEATURES, array_keys($features)) === [];
    }

    private function orderedFeatures(array $features): array
    {
        $ordered = [];

        foreach (ReadinessFeatureService::FEATURES as $feature) {
            $ordered[$feature] = $features[$feature];
        }

        return $ordered;
    }

    private function serviceErrorMessage(Response $response, string $fallback): string
    {
        $message = $response->json('detail.message');

        return is_string($message) && trim($message) !== '' ? $message : $fallback;
    }

    private function validatedExplanation(array $items): ?array
    {
        if (count($items) > 5) {
            return null;
        }

        $validated = [];

        foreach ($items as $item) {
            if (
                ! is_array($item)
                || ! is_string($item['feature'] ?? null)
                || ! in_array($item['feature'], ReadinessFeatureService::FEATURES, true)
                || ! is_numeric($item['global_permutation_importance'] ?? null)
            ) {
                return null;
            }

            $validated[] = [
                'feature' => $item['feature'],
                'global_permutation_importance' => (float) $item['global_permutation_importance'],
            ];
        }

        return $validated;
    }

    private function healthUnavailable(string $code, string $reason): array
    {
        return [
            'service_available' => false,
            'model_ready' => false,
            'model_version' => null,
            'reason' => $reason,
            'error_code' => $code,
        ];
    }
}
