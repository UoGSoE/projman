<x-mail::message>
# Service Acceptance Submitted

Service Acceptance has been submitted for project **{{ $project->title }}**.

All required deployment fields have been completed and the project is ready for Service Handover approvals.

<x-mail::button :url="route('project.show', ['project' => $project, 'tab' => 'deployed'])">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
