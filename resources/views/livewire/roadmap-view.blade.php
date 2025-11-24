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

    @if($monthColumns->isEmpty())
        <flux:callout icon="calendar" variant="secondary">
            No scheduled projects yet. Projects need start and end dates to appear on the roadmap.
        </flux:callout>
    @else
        {{-- Timeline Grid --}}
        <div class="overflow-x-auto">
            <div class="inline-grid min-w-full"
                 style="grid-template-columns: 200px repeat({{ $monthColumns->count() }}, minmax(120px, 1fr));">

                {{-- Month Headers --}}
                <div class="sticky left-0 bg-white dark:bg-zinc-900 border-b-2 border-zinc-200 dark:border-zinc-700 p-3 font-semibold z-10">
                    Service Function
                </div>
                @foreach($monthColumns as $month)
                    <div class="border-b-2 border-zinc-200 dark:border-zinc-700 p-3 text-center text-sm font-medium">
                        {{ $month['label'] }}
                    </div>
                @endforeach

                {{-- Project Rows (One per Service Function) --}}
                @foreach($roadmapData as $row)
                    {{-- Function Label --}}
                    <div class="sticky left-0 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 p-3 font-medium z-10">
                        {{ $row['serviceFunction'] }}
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $row['projectCount'] }})</span>
                    </div>

                    {{-- Timeline Area --}}
                    <div class="relative border-b border-zinc-200 dark:border-zinc-700 pl-2"
                         style="grid-column: 2 / -1; min-height: {{ $row['rowHeight'] }}px;">
                        @foreach($row['projects'] as $projectData)
                            <div class="absolute rounded px-3 py-2 text-xs shadow-sm cursor-pointer hover:shadow-md transition-shadow {{ $projectData['colorClasses'] }}"
                                 style="top: {{ $projectData['top'] }}px; left: {{ $projectData['left'] }}%; width: {{ $projectData['width'] }}%;">
                                <flux:link :href="route('portfolio.change-on-a-page', $projectData['project'])"
                                           wire:navigate
                                           class="text-white hover:underline font-medium">
                                    #{{ $projectData['project']->id }} - {{ Str::limit($projectData['project']->title, 30) }}
                                </flux:link>
                                <div class="text-[10px] opacity-80 mt-1">
                                    {{ $projectData['project']->scheduling->estimated_start_date->format('M j') }}
                                    →
                                    {{ $projectData['project']->scheduling->estimated_completion_date->format('M j') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
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
