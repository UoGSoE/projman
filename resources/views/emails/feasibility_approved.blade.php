<x-mail::message>
# Feasibility Approved

The feasibility assessment for project **{{ $project->title }}** has been approved.

<x-mail::button :url="route('project.show', $project)">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
