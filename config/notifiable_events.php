<?php

return [
    [
        'label' => 'Project Created',
        'class' => \App\Events\ProjectCreated::class,
        'mailable' => \App\Mail\ProjectCreatedMail::class,
    ],
    [
        'label' => 'Project Stage Changed',
        'class' => \App\Events\ProjectStageChange::class,
        'mailable' => \App\Mail\ProjectStageChangeMail::class,
    ],
    [
        'label' => 'Feasibility Approved',
        'class' => \App\Events\FeasibilityApproved::class,
        'mailable' => \App\Mail\FeasibilityApprovedMail::class,
    ],
    [
        'label' => 'Feasibility Rejected',
        'class' => \App\Events\FeasibilityRejected::class,
        'mailable' => \App\Mail\FeasibilityRejectedMail::class,
    ],
];
