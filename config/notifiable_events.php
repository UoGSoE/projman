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
];
