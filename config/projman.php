<?php

use App\Events\DeploymentApproved;
use App\Events\DeploymentServiceAccepted;
use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Events\SchedulingScheduled;
use App\Events\ScopingSubmitted;
use App\Events\ServiceAcceptanceRequested;
use App\Events\UATAccepted;
use App\Events\UATRejected;
use App\Mail\DeploymentApprovedMail;
use App\Mail\DeploymentServiceAcceptedMail;
use App\Mail\FeasibilityApprovedMail;
use App\Mail\FeasibilityRejectedMail;
use App\Mail\ProjectCreatedMail;
use App\Mail\ProjectStageChangeMail;
use App\Mail\SchedulingScheduledMail;
use App\Mail\ScopingSubmittedMail;
use App\Mail\ServiceAcceptanceRequestedMail;
use App\Mail\UATAcceptedMail;
use App\Mail\UATRejectedMail;
use App\Models\Build;
use App\Models\Deployed;
use App\Models\DetailedDesign;
use App\Models\Development;
use App\Models\Feasibility;
use App\Models\Ideation;
use App\Models\Scheduling;
use App\Models\Scoping;
use App\Models\Testing;

return [
    'subforms' => [
        Ideation::class,
        Feasibility::class,
        Testing::class,
        Deployed::class,
        Build::class,
        Scoping::class,
        Scheduling::class,
        Development::class,
        DetailedDesign::class,
    ],

    // DCGG (Digital Change Governance Group) email address
    'dcgg_email' => env('PROJMAN_DCGG_EMAIL', 'dcgg@example.ac.uk'),

    'notifications' => [
        ProjectCreated::class => [
            'roles' => ['Admin', 'Project Manager'],
            'include_project_owner' => false,
            'mailable' => ProjectCreatedMail::class,
        ],
        FeasibilityApproved::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => FeasibilityApprovedMail::class,
        ],
        FeasibilityRejected::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => true,
            'mailable' => FeasibilityRejectedMail::class,
        ],
        ScopingSubmitted::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => ScopingSubmittedMail::class,
        ],
        //        \App\Events\SchedulingSubmittedToDCGG::class => [
        //            'roles' => ['Work Package Assessor'],
        //            'include_project_owner' => false,
        //            'include_dcgg_email' => true,
        //            'mailable' => \App\Mail\SchedulingSubmittedMail::class,
        //        ],
        SchedulingScheduled::class => [
            'roles' => ['Work Package Assessor'],
            'include_project_owner' => false,
            'mailable' => SchedulingScheduledMail::class,
        ],
        UATAccepted::class => [
            'roles' => [],
            'include_project_owner' => true,
            'mailable' => UATAcceptedMail::class,
        ],
        UATRejected::class => [
            'roles' => [],
            'include_project_owner' => true,
            'mailable' => UATRejectedMail::class,
        ],
        ServiceAcceptanceRequested::class => [
            'roles' => ['Service Lead'],
            'include_project_owner' => false,
            'mailable' => ServiceAcceptanceRequestedMail::class,
        ],
        DeploymentServiceAccepted::class => [
            'roles' => ['Service Lead'],
            'include_project_owner' => false,
            'mailable' => DeploymentServiceAcceptedMail::class,
        ],
        DeploymentApproved::class => [
            'roles' => ['Service Lead'],
            'include_project_owner' => true,
            'mailable' => DeploymentApprovedMail::class,
        ],
        ProjectStageChange::class => [
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
            'mailable' => ProjectStageChangeMail::class,
        ],
    ],
];
