<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transparent application requirements
    |--------------------------------------------------------------------------
    |
    | These are ordinary policy rules, not machine-learning decisions. Members
    | may request a human review once every non-AI application requirement is
    | met. Final approval also considers a genuine local-model result when one
    | exists, while preserving an explicitly documented human override path.
    |
    */
    'minimum_level' => 6,
    'minimum_completed_challenges' => 1,
    'minimum_active_days' => 30,
    'minimum_longest_streak' => 7,
    'trainer_assessment_valid_days' => 120,
    'prediction_valid_days' => 90,
];
