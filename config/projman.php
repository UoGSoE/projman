<?php

return [
    'subforms' => [
        \App\Models\Ideation::class,
        \App\Models\Feasibility::class,
        \App\Models\Testing::class,
        \App\Models\Deployed::class,
        \App\Models\Scoping::class,
        \App\Models\Scheduling::class,
        \App\Models\Development::class,
        \App\Models\DetailedDesign::class,
    ],
    'mail' => [
        'project_created' => [
            'admin@example.com',
            'admin2@example.com',
            'admin3@example.com',
        ],
    ],
];
