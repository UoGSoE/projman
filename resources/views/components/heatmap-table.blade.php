{{-- Heatmap Table Component --}}
{{-- Expected props: $buckets (array of date buckets), $staff (collection), $component (Livewire component instance) --}}

<div class="overflow-x-auto" data-test="heatmap-grid">
    <div class="min-w-max">
        <div class="grid gap-2" style="grid-template-columns: 16rem repeat({{ count($buckets) }}, minmax(2.75rem, 1fr));">
            <div class="px-3 py-2">
                <flux:text class="uppercase text-xs tracking-wide text-zinc-500">Staff</flux:text>
            </div>

            @foreach ($buckets as $bucket)
                <div class="px-3 py-2 text-center">
                    <flux:text class="text-sm font-medium">{{ $bucket['label'] }}</flux:text>
                    <flux:text variant="subtle" class="text-xs">{{ $bucket['sublabel'] }}</flux:text>
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
                        title="{{ $entry['user']->forenames }} {{ $entry['user']->surname }} — {{ $buckets[$index]['label'] }} {{ $buckets[$index]['sublabel'] }} ({{ $busyness->label() }})"
                        aria-label="{{ $entry['user']->forenames }} {{ $entry['user']->surname }}: {{ $buckets[$index]['label'] }} {{ $buckets[$index]['sublabel'] }} {{ $busyness->label() }}"
                    ></div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
