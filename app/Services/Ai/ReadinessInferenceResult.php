<?php

namespace App\Services\Ai;

final readonly class ReadinessInferenceResult
{
    public function __construct(
        public bool $available,
        public ?bool $predictedReady = null,
        public ?float $probability = null,
        public ?float $decisionThreshold = null,
        public ?string $modelVersion = null,
        public array $explanation = [],
        public ?string $disclaimer = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function unavailable(string $code, string $message): self
    {
        return new self(
            available: false,
            errorCode: $code,
            errorMessage: $message,
        );
    }
}
