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

    'notifications' => [
        \App\Events\ProjectCreated::class => [
            'roles' => ['Admin', 'Project Manager'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\ProjectCreatedMail::class,
        ],
        \App\Events\FeasibilityApproved::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\FeasibilityApprovedMail::class,
        ],
        \App\Events\FeasibilityRejected::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => true,
            'mailable' => \App\Mail\FeasibilityRejectedMail::class,
        ],
        \App\Events\ScopingSubmittedToDCGG::class => [
            'roles' => ['Work Package Assessor', 'Scoping Manager'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\ScopingSubmittedMail::class,
        ],
        \App\Events\ScopingScheduled::class => [
            'roles' => ['Work Package Assessor', 'Scheduling Manager'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\ScopingScheduledMail::class,
        ],
        \App\Events\ProjectStageChange::class => [
            'stage_roles' => [
                'ideation' => ['Ideation Manager'],
                'feasibility' => ['Feasibility Manager'],
                'scoping' => ['Scoping Manager'],
                'scheduling' => ['Scheduling Manager'],
                'detailed-design' => ['Detailed Design Manager'],
                'development' => ['Development Manager'],
                'testing' => ['Testing Manager', 'Service Lead'],
                'deployed' => ['Deployment Manager'],
                'completed' => ['Completed Manager', 'Project Manager'],
                'cancelled' => ['Cancelled Manager', 'Project Manager'],
            ],
            'include_project_owner' => true,
            'mailable' => \App\Mail\ProjectStageChangeMail::class,
        ],
    ],
];
