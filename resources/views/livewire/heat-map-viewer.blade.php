<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Staff Heatmap</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-500">Upcoming ten working days of staff capacity at a glance.</flux:text>
    </div>

    <flux:separator variant="subtle" />

    <div class="overflow-x-auto">
        <div class="min-w-max">
            <div class="grid gap-2" style="grid-template-columns: 16rem repeat({{ count($days) }}, minmax(2.75rem, 1fr));">
                <div class="px-3 py-2">
                    <flux:text class="uppercase text-xs tracking-wide text-zinc-500">Staff</flux:text>
                </div>

                @foreach ($days as $day)
                    <div class="px-3 py-2 text-center">
                        <flux:text class="text-sm font-medium">{{ $day->format('D') }}</flux:text>
                        <flux:text variant="subtle" class="text-xs">{{ $day->format('d M') }}</flux:text>
                    </div>
                @endforeach

                @foreach ($staff as $user)
                    <div class="px-3 py-2">
                        <flux:text class="font-medium">{{ $user->forenames }} {{ $user->surname }}</flux:text>
                    </div>

                    @foreach ($days as $index => $day)
                        @php
                            $busyness = $this->busynessForDay($user, $index);
                        @endphp

                        <div
                            class="h-10 rounded-md border border-white/10 shadow-sm transition-colors {{ $busyness->color() }}"
                            title="{{ $user->forenames }} {{ $user->surname }} — {{ $day->format('D j M') }} ({{ $busyness->label() }})"
                            aria-label="{{ $user->forenames }} {{ $user->surname }}: {{ $day->format('D j M') }} {{ $busyness->label() }}"
                        ></div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <div>
        <flux:heading size="lg">Active Projects</flux:heading>
        <flux:separator variant="subtle" class="mt-4" />

        @if ($activeProjects->isEmpty())
            <flux:callout icon="inbox" variant="secondary" class="mt-4">
                <flux:callout.heading>No active projects</flux:callout.heading>
                <flux:callout.text>Assigning a new project or re-opening an existing one will surface here automatically.</flux:callout.text>
            </flux:callout>
        @else
            <flux:card class="mt-4 space-y-4">
                @foreach ($activeProjects as $project)
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <flux:text class="font-medium">{{ $project->title }}</flux:text>
                            <flux:text variant="subtle" class="text-sm">
                                Owned by {{ $project->user?->forenames }} {{ $project->user?->surname }}
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($project->deadline)
                                <flux:badge size="sm" icon="calendar-days" variant="solid" color="amber">
                                    Due {{ $project->deadline->format('d M Y') }}
                                </flux:badge>
                            @endif
                            <flux:badge size="sm" variant="pill" color="{{ $project->status->colour() }}">
                                {{ ucfirst(str_replace('-', ' ', $project->status->value)) }}
                            </flux:badge>
                        </div>
                    </div>

                    @unless($loop->last)
                        <flux:separator variant="subtle" />
                    @endunless
                @endforeach
            </flux:card>
        @endif
    </div>
</div>
