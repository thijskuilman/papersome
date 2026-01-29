<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Completion Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage driver used to track which
    | onboarding steps have been manually dismissed by users. The condition-based
    | completion is always checked in real-time.
    |
    | Supported: "cache", "session"
    |
    */
    'storage' => 'cache',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | When using the cache storage driver, these options configure how the
    | completion data is stored.
    |
    */
    'cache' => [
        'prefix' => 'onboarding_',
        'ttl' => 60 * 60 * 24 * 365, // 1 year in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for the onboarding widget appearance.
    |
    */
    'widget' => [
        'hide_when_complete' => true,
        'show_progress_bar' => true,
    ],
];
