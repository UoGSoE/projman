<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl">{{ $project->title }}</flux:heading>
        <flux:subheading>Work Package Reference #{{ $project->id }}</flux:subheading>
    </div>

    {{-- Row 1: Champion & School/Group --}}
    <div class="grid grid-cols-2 gap-6">
        <div>
            <flux:heading size="sm" class="mb-2">Champion</flux:heading>
            <flux:text>{{ $project->ideation?->school_group ?? 'Not specified' }}</flux:text>
        </div>
        <div>
            <flux:heading size="sm" class="mb-2">Raised By</flux:heading>
            <flux:text>{{ $project->user->full_name }}</flux:text>
        </div>
    </div>

    {{-- Section 1 (teal): Objective & Business Case --}}
    <div class="p-6 bg-teal-50 dark:bg-teal-950 border border-teal-200 dark:border-teal-800 rounded-lg">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <flux:heading size="sm" class="mb-2">Objective</flux:heading>
                <flux:text>{{ $project->ideation?->objective ?? 'Not specified' }}</flux:text>
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">Business Case</flux:heading>
                <flux:text>{{ $project->ideation?->business_case ?? 'Not specified' }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Section 2 (orange): Benefits, In-Scope, Out of Scope --}}
    <div class="p-6 bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="grid grid-cols-3 gap-6">
            <div>
                <flux:heading size="sm" class="mb-2">Benefits</flux:heading>
                <flux:text>{{ $project->ideation?->benefits ?? 'Not specified' }}</flux:text>
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">In-Scope</flux:heading>
                <flux:text>{{ $project->scoping?->in_scope ?? 'Not specified' }}</flux:text>
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">Out of Scope</flux:heading>
                <flux:text>{{ $project->scoping?->out_of_scope ?? 'Not specified' }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Section 3 (purple): Recommendation --}}
    <div class="p-6 bg-purple-50 dark:bg-purple-950 border border-purple-200 dark:border-purple-800 rounded-lg">
        <flux:heading size="sm" class="mb-2">Recommendation</flux:heading>
        <flux:text>{{ $project->feasibility?->recommendation ?? 'Pending feasibility assessment' }}</flux:text>
    </div>

    {{-- Section 4 (pink): Priority, Effort, Projected Start Date --}}
    <div class="p-6 bg-pink-50 dark:bg-pink-950 border border-pink-200 dark:border-pink-800 rounded-lg">
        <div class="grid grid-cols-3 gap-6">
            <div>
                <flux:heading size="sm" class="mb-2">Priority</flux:heading>
                <flux:text>{{ $project->scheduling?->priority?->label() ?? 'Not set' }}</flux:text>
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">Estimated Effort</flux:heading>
                <flux:text>{{ $project->scoping?->estimated_effort?->label() ?? 'Not assessed' }}</flux:text>
            </div>
            <div>
                <flux:heading size="sm" class="mb-2">Projected Start Date</flux:heading>
                <flux:text>
                    {{ $project->scheduling?->estimated_start_date?->format('d/m/Y') ?? 'Not scheduled' }}
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Technical Details --}}
    @if($project->scheduling?->assignedUser || $project->scheduling?->estimated_completion_date)
        <div class="p-6 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg">
            <flux:heading size="sm" class="mb-4">Technical Details</flux:heading>
            <div class="grid grid-cols-2 gap-6">
                @if($project->scheduling?->assignedUser)
                    <div>
                        <flux:heading size="sm" class="mb-2">Technical Owner</flux:heading>
                        <flux:text>{{ $project->scheduling->assignedUser->full_name }}</flux:text>
                    </div>
                @endif
                @if($project->scheduling?->estimated_completion_date)
                    <div>
                        <flux:heading size="sm" class="mb-2">Estimated Completion</flux:heading>
                        <flux:text>{{ $project->scheduling->estimated_completion_date->format('d/m/Y') }}</flux:text>
                    </div>
                @endif>
            </div>
        </div>
    @endif

    {{-- Back Button --}}
    <div class="mt-8">
        <flux:button href="{{ route('portfolio.backlog') }}" wire:navigate icon="arrow-left">
            Back to Backlog
        </flux:button>
    </div>
</div>
