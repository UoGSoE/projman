<x-mail::message>
# UAT Testing Requested

You have been assigned as the UAT Tester for work package **{{ $project->title }}**.

Please review and test the work package, then update the User Acceptance sign-off status.

<x-mail::button :url="route('project.edit', ['project' => $project, 'tab' => 'testing'])">
View Testing Tab
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
