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
        'stages' => [
            'ideation' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],
            'feasibility' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'scoping' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'scheduling' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'detailed_design' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'development' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'testing' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'deployed' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ],

            'completed' => [
                'admin@example.com',
                'admin2@example.com',
                'admin3@example.com',
            ]

        ],
    ],
];
