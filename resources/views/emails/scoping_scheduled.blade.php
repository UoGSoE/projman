<x-mail::message>
# Scoping Scheduled

The scoping phase for project **{{ $project->title }}** has been approved and scheduled for implementation.

<x-mail::button :url="route('project.show', $project)">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
