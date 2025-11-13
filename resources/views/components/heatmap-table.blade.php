{{-- Heatmap Table Component --}}
{{-- Expected props: $days (array of Carbon dates), $staff (collection), $component (Livewire component instance) --}}

<div class="overflow-x-auto" data-test="heatmap-grid">
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

            @foreach ($staff as $entry)
                <div class="px-3 py-2">
                    <flux:link :href="route('user.show', $entry['user'])" class="font-medium hover:underline">
                        {{ $entry['user']->forenames }} {{ $entry['user']->surname }}
                    </flux:link>
                </div>

                @foreach ($entry['busyness'] as $index => $busyness)
                    <div
                        class="h-10 rounded-md border border-white/10 shadow-sm transition-colors {{ $busyness->color() }}"
                        title="{{ $entry['user']->forenames }} {{ $entry['user']->surname }} — {{ $days[$index]->format('D j M') }} ({{ $busyness->label() }})"
                        aria-label="{{ $entry['user']->forenames }} {{ $entry['user']->surname }}: {{ $days[$index]->format('D j M') }} {{ $busyness->label() }}"
                    ></div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
