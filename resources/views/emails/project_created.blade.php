<x-mail::message>
# New Work Package

{{ $project->title }} has been requested by {{ $project->user->name }}.

<x-mail::button :url="route('project.show', $project->id)">
    Click here to view the work package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
