<x-mail::message>
# UAT Testing Requested

You have been assigned as the UAT Tester for project **{{ $project->title }}**.

Please review and test the project, then update the User Acceptance sign-off status.

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Testing Tab
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
