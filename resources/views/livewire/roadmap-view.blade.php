<div class="space-y-8">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">Project Roadmap</flux:heading>
        <flux:subheading>Timeline view of all projects grouped by service function</flux:subheading>
    </div>

    <flux:separator variant="subtle" />

    {{-- BRAG Legend --}}
    <div class="flex gap-4 items-center text-sm">
        <span class="font-semibold">Status:</span>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded bg-green-600"></span>
            <span>On Track</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded bg-amber-500"></span>
            <span>At Risk</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded bg-red-600"></span>
            <span>Overdue</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded bg-zinc-900"></span>
            <span>Completed</span>
        </div>
    </div>

    @if($totalWeeks === 0)
        <flux:callout icon="calendar" variant="secondary">
            No scheduled projects yet. Projects need start and end dates to appear on the roadmap.
        </flux:callout>
    @else
        {{-- Timeline Grid --}}
        <div class="overflow-x-auto">
            <div class="grid"
                 style="grid-template-columns: 180px repeat({{ $totalWeeks }}, minmax(20px, 1fr)); min-width: max-content;">

                {{-- Month Headers --}}
                <div class="sticky left-0 z-20 bg-white dark:bg-zinc-900 border-b-2 border-zinc-200 dark:border-zinc-700 p-2 font-semibold text-sm">
                    Service Function
                </div>
                @foreach($monthSpans as $month)
                    <div class="border-b-2 border-l border-zinc-200 dark:border-zinc-700 p-2 text-center text-sm font-medium bg-zinc-50 dark:bg-zinc-800"
                         style="grid-column: span {{ $month['span'] }};">
                        {{ $month['label'] }}
                    </div>
                @endforeach

                {{-- Service Function Rows --}}
                @foreach($roadmapData as $row)
                    {{-- Service function label spans all its lanes --}}
                    <div class="sticky left-0 z-10 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 p-2 font-medium text-sm"
                         style="grid-row: span {{ count($row['lanes']) }};">
                        {{ $row['serviceFunction'] }}
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $row['projectCount'] }})</span>
                    </div>

                    {{-- Each lane is a row --}}
                    @foreach($row['lanes'] as $laneIndex => $lane)
                        {{-- Lane container spanning all week columns --}}
                        <div class="relative border-b border-zinc-100 dark:border-zinc-800 h-9"
                             style="grid-column: 2 / -1;">
                            {{-- Projects in this lane --}}
                            <div class="absolute inset-0 grid"
                                 style="grid-template-columns: repeat({{ $totalWeeks }}, minmax(20px, 1fr));">
                                @foreach($lane as $slot)
                                    <a href="{{ route('portfolio.change-on-a-page', $slot['project']) }}"
                                       wire:navigate
                                       class="flex items-center rounded mx-0.5 my-0.5 px-2 text-xs shadow-sm cursor-pointer hover:shadow-md transition-shadow overflow-hidden {{ $slot['colorClasses'] }}"
                                       style="grid-column: {{ $slot['startWeek'] + 1 }} / span {{ $slot['span'] }};"
                                       title="#{{ $slot['project']->id }} - {{ $slot['project']->title }} ({{ $slot['project']->scheduling->estimated_start_date->format('M j') }} → {{ $slot['project']->scheduling->estimated_completion_date->format('M j') }})">
                                        <span class="truncate">#{{ $slot['project']->id }} {{ Str::limit($slot['project']->title, 30) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- Unscheduled Projects Section --}}
    @if($unscheduledProjects->isNotEmpty())
        <div class="mt-8">
            <flux:heading size="lg">Unscheduled Projects</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Projects without start and end dates assigned
            </flux:text>

            <div class="mt-4 grid gap-2">
                @foreach($unscheduledProjects as $project)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:link :href="route('portfolio.change-on-a-page', $project)"
                                           wire:navigate
                                           class="font-medium hover:underline">
                                    #{{ $project->id }} - {{ $project->title }}
                                </flux:link>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $project->user->service_function?->label() ?? 'No service function' }}
                                </flux:text>
                            </div>
                            <flux:badge :color="$project->status->colour()">
                                {{ $project->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <flux:separator variant="subtle" class="mt-12" />

    {{-- Portfolio Status Summary (Stubbed) --}}
    <div>
        <flux:heading size="lg">Portfolio Health</flux:heading>
        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            Overall portfolio status indicators
        </flux:text>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card>
                <flux:heading size="sm">Delivery</flux:heading>
                <flux:badge color="green" class="mt-2">On Track</flux:badge>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                    Portfolio delivery status placeholder - awaiting stakeholder definition
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">Budget</flux:heading>
                <flux:badge color="amber" class="mt-2">At Risk</flux:badge>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                    Portfolio budget status placeholder - awaiting stakeholder definition
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">Resource</flux:heading>
                <flux:badge color="green" class="mt-2">On Track</flux:badge>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                    Portfolio resource status placeholder - awaiting stakeholder definition
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">Dependencies</flux:heading>
                <flux:badge color="green" class="mt-2">On Track</flux:badge>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
                    Portfolio dependencies placeholder - awaiting stakeholder definition
                </flux:text>
            </flux:card>
        </div>
    </div>
</div>
