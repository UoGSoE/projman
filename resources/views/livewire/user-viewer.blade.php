<div class="space-y-8">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <flux:heading size="xl" level="1" class="flex items-center gap-2">
                {{ $user->full_name }}
                @if ($user->isAdmin())
                    <flux:badge size="sm" variant="solid" color="green" icon="shield-check">Administrator</flux:badge>
                @endif
            </flux:heading>
            <div class="mt-2 space-y-2">
                <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                    <a href="mailto:{{ $user->email }}" class="hover:underline">{{ $user->email }}</a>
                </div>
                @if ($user->roles->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($user->roles as $role)
                            <flux:badge size="sm" variant="outline" inset="top bottom">
                                {{ $role->name }}
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <flux:separator variant="subtle" />

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card>
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Basic details</flux:heading>
                    <flux:text variant="subtle" class="mt-1 text-sm">Overview of account and contact information.</flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Full name</flux:text>
                        <flux:text class="text-base font-medium">{{ $user->full_name }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Email</flux:text>
                        <flux:text class="text-base font-medium">
                            <a href="mailto:{{ $user->email }}" class="hover:underline">{{ $user->email }}</a>
                        </flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Username</flux:text>
                        <flux:text class="text-base font-medium">{{ $user->username }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Account created</flux:text>
                        <flux:text class="text-base font-medium">
                            {{ optional($user->created_at)->format('d M Y') ?? '—' }}
                        </flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Roles assigned</flux:text>
                        <flux:text class="text-base font-medium">{{ $user->roles->count() }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Skills recorded</flux:text>
                        <flux:text class="text-base font-medium">{{ $user->skills->count() }}</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Skills</flux:heading>
                <flux:text variant="subtle" class="mt-1 text-sm">Expertise recorded against this profile.</flux:text>
            </div>

            @if ($user->skills->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($user->skills as $skill)
                        <flux:badge size="sm" variant="subtle" class="py-1 px-3" :key="'skill-' . $skill->id">
                            {{ $skill->name }}
                        </flux:badge>
                    @endforeach
                </div>
            @else
                <flux:callout variant="secondary" icon="sparkles">
                    <flux:callout.heading>No skills recorded</flux:callout.heading>
                    <flux:callout.text>Add skills from the Skills Manager to start matching this user to projects.</flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>

        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Requested projects</flux:heading>
                <flux:text variant="subtle" class="mt-1 text-sm">Projects submitted by this user.</flux:text>
            </div>

            @if ($requestedProjects->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Project</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="text-right">Requested</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($requestedProjects as $project)
                            <flux:table.row :key="'requested-' . $project->id">
                                <flux:table.cell>
                                    <a href="{{ route('project.show', $project) }}" class="font-medium hover:underline">
                                        {{ $project->title }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" variant="pill" color="{{ $project->status->colour() }}">
                                        {{ ucfirst(str_replace('-', ' ', $project->status->value)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    {{ optional($project->created_at)->format('d M Y') ?? '—' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout variant="secondary" icon="inbox">
                    <flux:callout.heading>No project requests</flux:callout.heading>
                    <flux:callout.text>This user has not requested any projects yet.</flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    </div>

    @if ($user->skills->isNotEmpty())
        @php($assignments = $this->displayedItAssignments)
        @php($allAssignments = $this->allItAssignments)

        <flux:card class="space-y-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <flux:heading size="lg">IT project assignments</flux:heading>
                    <flux:text variant="subtle" class="mt-1 text-sm">Projects where this user appears in scheduling IT staff.</flux:text>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <flux:field variant="inline" class="sm:justify-end">
                        <flux:switch label="Include completed projects" wire:model.live="showAllAssignments" />
                    </flux:field>
                    <flux:badge size="sm" variant="outline" icon="users">
                        {{ $assignments->count() }} {{ $showAllAssignments ? 'total' : 'active' }}
                    </flux:badge>
                </div>
            </div>

            @if ($assignments->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Project</flux:table.column>
                        <flux:table.column>Requested by</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="text-right">Deadline</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($assignments as $assignment)
                            <flux:table.row :key="'assignment-' . $assignment->id">
                                <flux:table.cell>
                                    <flux:link :href="route('project.show', $assignment)">
                                        {{ $assignment->title }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($assignment->user)
                                        <flux:link :href="route('user.show', $assignment->user)" variant="strong">
                                            {{ $assignment->user->full_name }}
                                        </flux:link>
                                    @else
                                        <flux:text variant="subtle" class="text-sm">Owner not set</flux:text>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" variant="pill" color="{{ $assignment->status->colour() }}">
                                        {{ ucfirst(str_replace('-', ' ', $assignment->status->value)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    {{ optional($assignment->deadline)?->format('d/m/Y') ?? '—' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout variant="secondary" icon="hand-raised">
                    <flux:callout.heading>No {{ $showAllAssignments ? 'IT assignments recorded' : 'current IT assignments' }}</flux:callout.heading>
                    <flux:callout.text>
                        @if ($allAssignments->isEmpty())
                            This user has skills but is not assigned to any project scheduling records yet.
                        @else
                            All assignments for this user are marked as completed or cancelled.
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    @endif
</div>
