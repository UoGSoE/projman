<x-mail::message>
# UAT Accepted

The UAT testing for work package **{{ $project->title }}** has been accepted.

The work package is now ready to proceed with Service Acceptance.

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
