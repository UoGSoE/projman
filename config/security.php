<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Input Sanitization
    |--------------------------------------------------------------------------
    |
    | Configure how user inputs are sanitized across the application.
    |
    */
    'input_sanitization' => [
        'max_search_length' => 100,
        'max_text_length' => 2048,
        'max_short_text_length' => 255,
        'allowed_html_tags' => [], // No HTML tags allowed by default
        'dangerous_patterns' => [
            'javascript:',
            'vbscript:',
            'data:',
            'onload=',
            'onerror=',
            'onclick=',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various endpoints.
    |
    */
    'rate_limiting' => [
        'login_attempts' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'api_requests' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings.
    |
    */
    'session' => [
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'lax',
        'lifetime' => env('SESSION_LIFETIME', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    |
    | Configure CSRF protection settings.
    |
    */
    'csrf' => [
        'enabled' => true,
        'token_lifetime' => 60,
        'exclude_paths' => [
            // Add any paths that should be excluded from CSRF protection
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | XSS Protection
    |--------------------------------------------------------------------------
    |
    | Configure XSS protection headers and settings.
    |
    */
    'xss_protection' => [
        'enabled' => true,
        'mode' => 'block',
        'report_uri' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy headers.
    |
    */
    'csp' => [
        'enabled' => true,
        'default_src' => ["'self'"],
        'script_src' => ["'self'", "'unsafe-inline'"],
        'style_src' => ["'self'", "'unsafe-inline'"],
        'img_src' => ["'self'", 'data:', 'https:'],
        'connect_src' => ["'self'"],
        'font_src' => ["'self'"],
        'object_src' => ["'none'"],
        'media_src' => ["'self'"],
        'frame_src' => ["'none'"],
    ],
];
