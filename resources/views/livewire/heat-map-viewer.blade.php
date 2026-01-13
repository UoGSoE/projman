<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Staff Heatmap</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500">
                @switch($viewMode)
                    @case('weeks')
                        Upcoming ten weeks of staff capacity at a glance.
                        @break
                    @case('months')
                        Upcoming ten months of staff capacity at a glance.
                        @break
                    @default
                        Upcoming ten working days of staff capacity at a glance.
                @endswitch
            </flux:text>
        </div>

        <div class="flex flex-col items-end gap-4">
            <div class="flex flex-col items-end gap-2">
                <flux:radio.group wire:model.live="viewMode" variant="segmented" size="sm">
                    <flux:radio value="days" label="Days" />
                    <flux:radio value="weeks" label="Weeks" />
                    <flux:radio value="months" label="Months" />
                </flux:radio.group>
                <flux:text variant="subtle" class="text-xs">
                    @if ($viewMode === 'days')
                        Showing manually reported busyness from staff profiles.
                    @else
                        Showing busyness calculated from project assignments.
                    @endif
                </flux:text>
            </div>

            <flux:pillbox wire:model.live="nameFilter" multiple searchable placeholder="Filter by staff..." class="min-w-64">
                @foreach ($allStaff as $staffMember)
                    <flux:pillbox.option :value="$staffMember->id">
                        {{ $staffMember->surname }}, {{ $staffMember->forenames }}
                    </flux:pillbox.option>
                @endforeach
            </flux:pillbox>

        </div>
    </div>

    <flux:separator variant="subtle" />

    @include('components.heatmap-table', [
        'buckets' => $buckets,
        'staff' => $staff,
        'component' => $this,
    ])

    <div>
        <flux:heading size="lg">Active Work Packages</flux:heading>
        <flux:separator variant="subtle" class="mt-4" />

        @if ($activeProjects->isEmpty())
            <flux:callout icon="inbox" variant="secondary" class="mt-4">
                <flux:callout.heading>No active work packages</flux:callout.heading>
            </flux:callout>
        @else
            <flux:card class="mt-4 space-y-4">
                @foreach ($activeProjects as $project)
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <flux:text class="font-medium">{{ $project->title }}</flux:text>
                                <flux:text variant="subtle" class="text-sm">
                                    @if ($project->user)
                                        Owned by
                                        <flux:link :href="route('user.show', $project->user)" class="hover:underline">
                                            {{ $project->user->forenames }} {{ $project->user->surname }}
                                        </flux:link>
                                    @else
                                        Owner not set
                                    @endif
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-3">
                                <flux:badge size="sm" variant="pill" color="{{ $project->status->colour() }}">
                                    {{ ucfirst(str_replace('-', ' ', $project->status->value)) }}
                                </flux:badge>
                                @if ($project->deadline)
                                    <flux:badge size="sm" icon="calendar-days" variant="solid" color="amber">
                                        {{ $project->deadline->format('d M Y') }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>

                        @if ($project->team_members->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($project->team_members as $member)
                                    <flux:link :href="route('user.show', $member)" class="inline-flex">
                                        @if ($project->assigned_user_id === $member->id)
                                            <flux:badge size="sm" icon="user" variant="subtle" color="sky">
                                                {{ $member->forenames }} {{ $member->surname }}
                                            </flux:badge>
                                        @else
                                            <flux:badge size="sm" icon="user" variant="subtle">
                                                {{ $member->forenames }} {{ $member->surname }}
                                            </flux:badge>
                                        @endif
                                    </flux:link>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @unless($loop->last)
                        <flux:separator variant="subtle" />
                    @endunless
                @endforeach
            </flux:card>
        @endif
    </div>
</div>
