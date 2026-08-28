<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local progression-readiness inference boundary
    |--------------------------------------------------------------------------
    |
    | Keep this disabled until Notebook 03 has produced a reviewed artifact
    | package. The client rejects non-loopback URLs so private fitness features
    | cannot be sent to a hosted or accidentally exposed external service.
    |
    */
    'enabled' => (bool) env('AI_INFERENCE_ENABLED', false),
    'base_url' => env('AI_INFERENCE_URL', 'http://127.0.0.1:8001'),
    'connect_timeout_seconds' => (int) env('AI_INFERENCE_CONNECT_TIMEOUT', 1),
    'timeout_seconds' => (int) env('AI_INFERENCE_TIMEOUT', 3),
];
