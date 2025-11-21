<?php

return [
    'subforms' => [
        \App\Models\Ideation::class,
        \App\Models\Feasibility::class,
        \App\Models\Testing::class,
        \App\Models\Deployed::class,
        \App\Models\Build::class,
        \App\Models\Scoping::class,
        \App\Models\Scheduling::class,
        \App\Models\Development::class,
        \App\Models\DetailedDesign::class,
    ],

    // DCGG (Digital Change Governance Group) email address
    'dcgg_email' => env('PROJMAN_DCGG_EMAIL', 'dcgg@example.ac.uk'),

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
        \App\Events\ScopingSubmitted::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\ScopingSubmittedMail::class,
        ],
        //        \App\Events\SchedulingSubmittedToDCGG::class => [
        //            'roles' => ['Work Package Assessor'],
        //            'include_project_owner' => false,
        //            'include_dcgg_email' => true,
        //            'mailable' => \App\Mail\SchedulingSubmittedMail::class,
        //        ],
        \App\Events\SchedulingScheduled::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => \App\Mail\SchedulingScheduledMail::class,
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
                'build' => ['Build Manager'],
                'completed' => ['Completed Manager', 'Project Manager'],
                'cancelled' => ['Cancelled Manager', 'Project Manager'],
            ],
            'include_project_owner' => true,
            'mailable' => \App\Mail\ProjectStageChangeMail::class,
        ],
    ],
];
