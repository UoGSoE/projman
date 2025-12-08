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

<flux:heading size="lg">Progress Notes</flux:heading>

<div class="flex gap-2 mt-4">
    <flux:textarea
        wire:model="buildForm.newNote"
        rows="2"
        placeholder="Add a progress note..."
        class="flex-1"
    />
    <flux:button wire:click="addBuildNote" variant="primary">Add Note</flux:button>
</div>

<flux:table class="mt-4">
    <flux:table.columns>
        <flux:table.column>Date</flux:table.column>
        <flux:table.column>User</flux:table.column>
        <flux:table.column>Note</flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @forelse ($project->build->notes as $note)
            <flux:table.row wire:key="build-note-{{ $note->id }}">
                <flux:table.cell>{{ $note->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                <flux:table.cell>{{ $note->user_name }}</flux:table.cell>
                <flux:table.cell>{{ $note->body }}</flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="3">No notes yet.</flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
