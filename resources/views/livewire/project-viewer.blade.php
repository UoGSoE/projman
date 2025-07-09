<div>
    <div class="flex flex-col md:flex-row gap-4 justify-between">
        <flux:heading size="xl" level="1">Project Details</flux:heading>
        <flux:button icon="pencil" variant="primary" href="{{ route('project.edit', $project) }}">Edit</flux:button>
    </div>

    <div class="flex flex-col md:grid grid-cols-2 gap-4">
        <flux:callout icon="user" class="mt-6">
            <flux:callout.heading>{{ $project->user->full_name }} ({{ $project->user->email }})</flux:callout.heading>
        </flux:callout>


        <flux:callout icon="clock" class="mt-6">
            <flux:callout.heading>{{ $project->title }}</flux:callout.heading>

            <flux:callout.text>
                {{ $project->ideation?->objective }}
            </flux:callout.text>
        </flux:callout>


    </div>


    <flux:separator variant="subtle" class="mt-6" />

    <flux:heading>Project History</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Description</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($project->history()->orderBy('created_at', 'desc')->get() as $history)
                <flux:table.row>
                    <flux:table.cell>{{ $history->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $history->user->full_name }}</flux:table.cell>
                    <flux:table.cell>{{ $history->description }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>


</div>
