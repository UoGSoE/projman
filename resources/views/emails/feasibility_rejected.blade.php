<x-mail::message>
# Feasibility Rejected

The feasibility assessment for project **{{ $project->title }}** has been rejected.

**Reason:** {{ $project->feasibility->reject_reason }}

<x-mail::button :url="route('project.show', $project)">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
