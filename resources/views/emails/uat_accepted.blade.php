<x-mail::message>
# UAT Accepted

The UAT testing for project **{{ $project->title }}** has been accepted.

The project is now ready to proceed with Service Acceptance.

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
