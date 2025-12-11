<x-mail::message>
# Feasibility Approved

The feasibility assessment for work package **{{ $project->title }}** has been approved.

<x-mail::button :url="route('project.show', $project)">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
