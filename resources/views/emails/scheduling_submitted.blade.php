<x-mail::message>
# Scheduling Submitted to DCGG

The scheduling phase for work package **{{ $project->title }}** has been submitted to the Digital Change Governance Group for approval.

**Work Package Details:**
- Assigned To: {{ $project->scheduling->assignedUser->full_name ?? 'Not assigned' }}
- Estimated Start: {{ $project->scheduling->estimated_start_date?->format('d/m/Y') ?? 'Not set' }}
- Estimated Completion: {{ $project->scheduling->estimated_completion_date?->format('d/m/Y') ?? 'Not set' }}

<x-mail::button :url="route('project.show', $project)">
View Work Package
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
