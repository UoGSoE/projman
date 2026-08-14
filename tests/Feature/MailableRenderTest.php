<?php

use App\Mail\DeploymentApprovedMail;
use App\Mail\DeploymentServiceAcceptedMail;
use App\Mail\FeasibilityApprovedMail;
use App\Mail\FeasibilityRejectedMail;
use App\Mail\ProjectCreatedMail;
use App\Mail\ProjectStageChangeMail;
use App\Mail\SchedulingScheduledMail;
use App\Mail\SchedulingSubmittedMail;
use App\Mail\ScopingScheduledMail;
use App\Mail\ScopingSubmittedMail;
use App\Mail\ServiceAcceptanceRequestedMail;
use App\Mail\UATAcceptedMail;
use App\Mail\UATRejectedMail;
use App\Mail\UATRequestedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Mail is faked globally in the base TestCase, so nothing else proves these
// markdown templates actually compile - a broken blade would otherwise stay
// green across the whole suite. render() bypasses the mailer, fake or not.
// The heading pins each mailable to its own template, so a copy-pasted
// mailable pointing at the wrong markdown view fails loudly.
it('renders every mailable without error', function (string $mailableClass, string $expectedHeading) {
    $project = $this->createProject();

    $rendered = (new $mailableClass($project))->render();

    expect($rendered)->toContain($project->title)->toContain($expectedHeading);
})->with([
    [DeploymentApprovedMail::class, 'Deployment Approved - Work Package Completed'],
    [DeploymentServiceAcceptedMail::class, 'Service Acceptance Submitted'],
    [FeasibilityApprovedMail::class, 'Feasibility Approved'],
    [FeasibilityRejectedMail::class, 'Feasibility Rejected'],
    [ProjectCreatedMail::class, 'New Work Package'],
    [ProjectStageChangeMail::class, 'Work Package Update'],
    [SchedulingScheduledMail::class, 'Scheduling Approved and Scheduled'],
    [SchedulingSubmittedMail::class, 'Scheduling Submitted to DCGG'],
    [ScopingScheduledMail::class, 'Scoping Scheduled'],
    [ScopingSubmittedMail::class, 'Scoping Submitted to DCGG'],
    [ServiceAcceptanceRequestedMail::class, 'Service Acceptance Requested'],
    [UATAcceptedMail::class, 'UAT Accepted'],
    [UATRejectedMail::class, 'UAT Rejected'],
    [UATRequestedMail::class, 'UAT Testing Requested'],
]);
