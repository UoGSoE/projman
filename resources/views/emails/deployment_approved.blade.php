<x-mail::message>
# Deployment Approved - Work Package Completed

Deployment has been approved for work package **{{ $project->title }}**.

All Service Handover approvals have been received and the work package status has been set to **Completed**.

<x-mail::button :url="route('project.show', ['project' => $project, 'tab' => 'deployed'])">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
