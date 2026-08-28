<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transparent XP rules
    |--------------------------------------------------------------------------
    |
    | Existing workout and wellness points convert to XP at a 1:1 rate.
    | These extra rewards are deliberately fixed application rules, not AI.
    |
    */
    'xp_per_level' => 100,
    'completed_trainer_session_xp' => 25,
    'completed_monthly_goal_xp' => 30,
    'streak_milestone_days' => 7,
    'streak_milestone_xp' => 20,

    'ranks' => [
        [
            'name' => 'Initiate',
            'minimum_level' => 1,
            'description' => 'Starting a consistent GymRAVANA routine.',
        ],
        [
            'name' => 'Foundation',
            'minimum_level' => 2,
            'description' => 'Building repeatable training and wellness habits.',
        ],
        [
            'name' => 'Challenger',
            'minimum_level' => 4,
            'description' => 'Showing sustained participation across activities.',
        ],
        [
            'name' => 'Vanguard',
            'minimum_level' => 6,
            'description' => 'Maintaining strong long-term consistency.',
        ],
        [
            'name' => 'Elite',
            'minimum_level' => 9,
            'description' => 'The highest automatic rank in the current system.',
        ],
    ],
];
