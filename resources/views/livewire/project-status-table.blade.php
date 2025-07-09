<div>
    @if ($projects->isEmpty())
        <div class="flex flex-col h-full mt-6 space-y-6">
            <flux:text>You don't have any projects yet. Start a new project to get underway.</flux:text>
        </div>
    @else
        @if (!$userId)
            <div class="flex flex-col md:flex-row gap-6 h-full mt-6">
                <flux:input type="text" wire:model="search" placeholder="Search..." />
                <flux:select variant="combobox" wire:model="schoolGroup" placeholder="School/group...">
                    <flux:select.option>All</flux:select.option>
                    <flux:select.option>Engineering</flux:select.option>
                    <flux:select.option>Chemistry</flux:select.option>
                    <flux:select.option>Another</flux:select.option>
                    <flux:select.option>Something</flux:select.option>
                </flux:select>
                <flux:select variant="combobox" wire:model="status" placeholder="Status...">
                    <flux:select.option>All</flux:select.option>
                    <flux:select.option>Ideation</flux:select.option>
                    <flux:select.option>Feasibility</flux:select.option>
                    <flux:select.option>Development</flux:select.option>
                    <flux:select.option>Testing</flux:select.option>
                    <flux:select.option>Deployed</flux:select.option>
                </flux:select>
            </div>

            <flux:separator variant="subtle" class="mt-6" />
        @endif
        <flux:table :paginate="$projects">
            <flux:table.columns>
                <flux:table.column>Project</flux:table.column>
                @if (! $userId)
                    <flux:table.column sortable :sorted="$sortBy === 'user'" :direction="$sortDirection"
                        wire:click="sort('user')">User</flux:table.column>
                @endif
                <flux:table.column sortable :sorted="$sortBy === 'updated_at'" :direction="$sortDirection"
                    wire:click="sort('updated_at')">Updated</flux:table.column>
                <flux:table.column>
                    Stages
                </flux:table.column>
                <flux:table.column>
                    Actions
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($projects as $project)
                    <flux:table.row key="project-row-{{ $project->id }}" class="hover:bg-zinc-200 dark:hover:bg-zinc-700">
                        <flux:table.cell class="flex items-center gap-3">
                            <flux:badge size="sm" :color="$project->status->colour()" inset="top bottom">
                                {{ $project->status }}
                            </flux:badge>
                            <a href="{{ route('project.show', $project) }}" class="hover:underline cursor-pointer">{{ $project->title }}</a>
                        </flux:table.cell>

                        @if (! $userId)
                            <flux:table.cell class="whitespace-nowrap">{{ $project->user->full_name }}</flux:table.cell>
                        @endif

                        <flux:table.cell variant="strong">{{ $project->updated_at->diffForHumans() }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$project->ideation->hasBeenEdited() ? 'green' : 'zinc'" icon="check-circle" title="Ideation"></flux:badge>
                            <flux:badge :color="$project->feasibility->hasBeenEdited() ? 'green' : 'zinc'" icon="check-circle" title="Feasibility"></flux:badge>
                            <flux:badge :color="$project->development->hasBeenEdited() ? 'green' : 'zinc'" icon="pause-circle" title="Development"></flux:badge>
                            <flux:badge :color="$project->testing->hasBeenEdited() ? 'green' : 'zinc'" icon="pause-circle" title="Testing"></flux:badge>
                            <flux:badge :color="$project->deployed->hasBeenEdited() ? 'green' : 'zinc'" icon="pause-circle" title="Deployed"></flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="magnifying-glass" href="{{ route('project.show', $project) }}" wire:navigate>View</flux:menu.item>
                                    <flux:menu.item icon="pencil">Edit</flux:menu.item>
                                    <flux:menu.item icon="at-symbol">Request Progress Update</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger">Remove</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endempty
</div>
