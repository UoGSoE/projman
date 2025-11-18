<x-mail::message>
# Scoping Submitted to DCGG

The scoping phase for project **{{ $project->title }}** has been submitted to the Digital Change Governance Group for review.

<x-mail::button :url="route('project.show', $project)">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
