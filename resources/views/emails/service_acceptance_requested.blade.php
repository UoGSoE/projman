<x-mail::message>
# Service Acceptance Requested

Service acceptance has been requested for work package **{{ $project->title }}**.

Please review the work package and update the appropriate sign-off status (Service Delivery or Service Resilience).

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Testing Tab
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
