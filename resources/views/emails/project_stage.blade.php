<x-mail::message>
# Work Package Update

Work Package {{ $project->title }} has been updated to the {{ ucfirst($project->status->value) }} stage and you are listed
as a contact for evaluating that stage.

<x-mail::button :url="route('project.show', $project)">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
