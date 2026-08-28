<?php

namespace App\Services\Ai;

use App\Models\ProgressionReadinessPrediction;

final readonly class ReadinessPredictionOutcome
{
    public function __construct(
        public bool $succeeded,
        public bool $created,
        public ?ProgressionReadinessPrediction $prediction,
        public string $message,
        public ?string $errorCode = null,
    ) {}

    public static function unavailable(string $code, string $message): self
    {
        return new self(
            succeeded: false,
            created: false,
            prediction: null,
            message: $message,
            errorCode: $code,
        );
    }
}
