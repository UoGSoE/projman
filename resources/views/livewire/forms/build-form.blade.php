<form wire:submit="save('build')" class="space-y-6">
    <flux:textarea
        label="Build Requirements"
        rows="6"
        wire:model="buildForm.buildRequirements"
        placeholder="Describe the build requirements for this project..."
    />

    <flux:separator />

    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
        <flux:button type="submit" variant="primary" class="w-full">Update</flux:button>
        <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">Advance
            To Next Stage
        </flux:button>
    </div>
</form>

<flux:separator class="my-6" />

<livewire:notes-list :noteable="$project->build" />
