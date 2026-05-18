<div>
    <flux:heading size="xl" level="1">Profile</flux:heading>
    <flux:separator variant="subtle" class="mt-6" />

    <div class="flex flex-col gap-6 pt-6">
        <div class="flex-1 mb-4">
            <flux:heading size="lg" class="mb-4">Availability for Change</flux:heading>
            <flux:radio.group wire:model.live="availabilityForChange" variant="segmented" class="w-full">
                @foreach ($availabilityOptions as $option)
                    <flux:radio :value="$option->value" :label="$option->label()" />
                @endforeach
            </flux:radio.group>
        </div>

        <div class="flex-1">
            <flux:heading size="lg" class="mb-4">My Skills</flux:heading>
            @include('partials.user-skills-grid', ['skills' => $skills])
        </div>
    </div>
</div>
