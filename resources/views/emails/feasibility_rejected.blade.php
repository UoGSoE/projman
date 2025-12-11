<x-mail::message>
# Feasibility Rejected

The feasibility assessment for work package **{{ $project->title }}** has been rejected.

**Reason:** {{ $project->feasibility->reject_reason }}

<x-mail::button :url="route('project.show', $project)">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
