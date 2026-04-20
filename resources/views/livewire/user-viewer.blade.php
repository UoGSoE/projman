<div class="space-y-8">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">{{ $user->full_name }}</flux:heading>
            <div class="flex flex-wrap items-center gap-2">
                <flux:badge size="sm" :color="$user->typeColour()" inset="top bottom">
                    {{ $user->typeLabel() }}
                </flux:badge>
                @foreach ($roles as $role)
                    <flux:badge size="sm" inset="top bottom" wire:key="role-badge-{{ $role->id }}">
                        {{ $role->name }}
                    </flux:badge>
                @endforeach
            </div>
            <flux:link href="mailto:{{ $user->email }}">{{ $user->email }}</flux:link>
        </div>
    </div>

    <flux:separator variant="subtle" />

    <div class="grid gap-6">
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
                            <flux:link href="mailto:{{ $user->email }}">{{ $user->email }}</flux:link>
                        </flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Username</flux:text>
                        <flux:text class="text-base font-medium">{{ $user->username }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Account created</flux:text>
                        <flux:text class="text-base font-medium">
                            {{ $user->created_at->format('d M Y') }}
                        </flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Roles assigned</flux:text>
                        <flux:text class="text-base font-medium">{{ $roles->count() }}</flux:text>
                    </div>

                    <div class="space-y-1">
                        <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">Skills recorded</flux:text>
                        <flux:text class="text-base font-medium">{{ $skills->count() }}</flux:text>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Skills</flux:heading>
            <flux:text variant="subtle" class="mt-1 text-sm">Expertise recorded against this profile.</flux:text>
        </div>
        @include('partials.user-skills-grid', ['skills' => $skills])
    </flux:card>

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Requested work packages</flux:heading>
            <flux:text variant="subtle" class="mt-1 text-sm">Work packages submitted by this user.</flux:text>
        </div>

        @if ($requestedProjects->isNotEmpty())
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Work Package</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Requested</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($requestedProjects as $project)
                        <flux:table.row :key="'requested-' . $project->id">
                            <flux:table.cell>
                                <flux:link :href="route('project.show', $project)">
                                    {{ $project->title }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" variant="pill" color="{{ $project->status->colour() }}">
                                    {{ $project->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                {{ $project->created_at->format('d M Y') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:callout variant="secondary" icon="inbox">
                <flux:callout.heading>No work package requests</flux:callout.heading>
                <flux:callout.text>This user has not requested any work packages yet.</flux:callout.text>
            </flux:callout>
        @endif
    </flux:card>

    @if ($skills->isNotEmpty())
        <flux:card class="space-y-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <flux:heading size="lg">IT work package assignments</flux:heading>
                    <flux:text variant="subtle" class="mt-1 text-sm">Work packages where this user appears in scheduling IT staff.</flux:text>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <flux:field variant="inline" class="sm:justify-end">
                        <flux:switch label="Include completed work packages" wire:model.live="showAllAssignments" />
                    </flux:field>
                    <flux:badge size="sm" variant="outline" icon="users">
                        {{ $itAssignments->count() }} assignments
                    </flux:badge>
                </div>
            </div>

            <div wire:loading.class="opacity-60 transition-opacity" wire:target="showAllAssignments">
            @if ($itAssignments->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Work Package</flux:table.column>
                        <flux:table.column>Requested by</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Deadline</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($itAssignments as $assignment)
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
                                        {{ $assignment->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    {{ $assignment->deadline?->format('d/m/Y') ?? '—' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout variant="secondary" icon="hand-raised">
                    <flux:callout.heading>No IT assignments to show</flux:callout.heading>
                    <flux:callout.text>This user isn't currently scheduled on any IT work packages.</flux:callout.text>
                </flux:callout>
            @endif
            </div>
        </flux:card>
    @endif
</div>
