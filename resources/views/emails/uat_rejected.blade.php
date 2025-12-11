<x-mail::message>
# UAT Rejected

The UAT testing for work package **{{ $project->title }}** has been rejected.

Please review the User Acceptance notes for details and address any issues identified during testing.

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
