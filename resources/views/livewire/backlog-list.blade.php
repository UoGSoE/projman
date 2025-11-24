<div>
    <flux:heading size="lg">Project Backlog</flux:heading>

    <div class="flex flex-col md:flex-row gap-6 mt-6">
        <flux:input
            type="text"
            wire:model.live="search"
            placeholder="Search projects..."
        />

        <flux:select wire:model.live="statusFilter" placeholder="Filter by status...">
            <flux:select.option value="all">All Statuses</flux:select.option>
            @foreach ($projectStatuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:separator variant="subtle" class="mt-6" />

    @if ($projects->isEmpty())
        <flux:text class="mt-6">No projects found matching your criteria.</flux:text>
    @else
        <flux:table :paginate="$projects" class="mt-6">
            <flux:table.columns>
                <flux:table.column>Ref #</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Deliverable</flux:table.column>
                <flux:table.column>Raised By</flux:table.column>
                <flux:table.column>Effort</flux:table.column>
                <flux:table.column>Technical Owner</flux:table.column>
                <flux:table.column>Delivery Date</flux:table.column>
                <flux:table.column>Champion</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($projects as $project)
                    <flux:table.row :key="$project->id">
                        <flux:table.cell>
                            <flux:link :href="route('portfolio.change-on-a-page', $project)" wire:navigate>
                                {{ $project->id }}
                            </flux:link>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$project->status->colour()">
                                {{ $project->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>{{ $project->title }}</flux:table.cell>

                        <flux:table.cell>{{ $project->user->full_name }}</flux:table.cell>

                        <flux:table.cell>{{ $project->scoping?->estimated_effort?->label() ?? 'Not Set' }}</flux:table.cell>

                        <flux:table.cell>{{ $project->scheduling?->assignedUser?->full_name ?? 'Unassigned' }}</flux:table.cell>

                        <flux:table.cell>
                            {{ $project->scheduling?->estimated_completion_date?->format('d/m/Y') ?? 'TBD' }}
                        </flux:table.cell>

                        <flux:table.cell>{{ $project->ideation?->school_group ?? 'N/A' }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
