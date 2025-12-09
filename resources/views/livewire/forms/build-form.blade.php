<form wire:submit="save('build')" class="space-y-6">
    <flux:textarea
        label="Build Requirements"
        rows="6"
        wire:model="buildForm.buildRequirements"
        placeholder="Describe the build requirements for this project..."
    />

    <flux:separator />

    <div class="flex items-center gap-2">
        <flux:button wire:click="saveAndAdvance('build')" variant="primary" icon:trailing="arrow-right">
            Advance to Next Stage
        </flux:button>
        <flux:button
            wire:click="advanceToNextStage()"
            variant="filled"
            icon="forward"
            size="sm"
            class="!bg-amber-500 hover:!bg-amber-600 cursor-pointer"
            title="Skip stage without saving (developers only)"
        />
    </div>
</form>

<flux:separator class="my-6" />

<x-notes-list
    :noteable="$project->build"
    formPrefix="buildForm"
    addNoteMethod="addBuildNote"
/>
