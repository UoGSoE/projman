<div>
    <flux:heading size="xl" level="1">Profile</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex flex-col gap-6 pt-6">
        <div class="flex-1 mb-4">
            <flux:heading size="lg" class="mb-4">My Busy-ness</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:radio.group wire:model.live="busynessWeek1" label="Week 1" variant="segmented" class="w-full">
                        @foreach ($busynessOptions as $option)
                            @if ($option->value != 0)
                                <flux:radio :value="$option->value" :label="$option->label()" />
                            @endif
                        @endforeach
                    </flux:radio.group>
                </div>

                <div>
                    <flux:radio.group wire:model.live="busynessWeek2" label="Week 2" variant="segmented" class="w-full">
                        @foreach ($busynessOptions as $option)
                            @if ($option->value != 0)
                                <flux:radio :value="$option->value" :label="$option->label()" />
                            @endif
                        @endforeach
                    </flux:radio.group>
                </div>
            </div>

        </div>

        <div class="flex-1">
            <flux:heading size="lg" class="mb-4">My Skills</flux:heading>
            @include('partials.user-skills-grid', ['skills' => $skills])
        </div>
    </div>
</div>
