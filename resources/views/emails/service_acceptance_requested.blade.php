<x-mail::message>
# Service Acceptance Requested

Service acceptance has been requested for project **{{ $project->title }}**.

Please review the project and update the appropriate sign-off status (Service Delivery or Service Resilience).

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Testing Tab
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
