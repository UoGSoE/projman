<x-mail::message>
# Deployment Approved - Project Completed

Deployment has been approved for project **{{ $project->title }}**.

All Service Handover approvals have been received and the project status has been set to **Completed**.

<x-mail::button :url="route('project.show', ['project' => $project, 'tab' => 'deployed'])">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
