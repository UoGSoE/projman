@props(['noteable', 'formPrefix', 'addNoteMethod'])

<div>
    <div class="flex items-center gap-2">
        <flux:heading size="lg">Progress Notes</flux:heading>
        <flux:modal.trigger name="add-note-{{ $formPrefix }}">
            <flux:button icon="plus" size="sm" variant="ghost" class="cursor-pointer" />
        </flux:modal.trigger>
    </div>

    <flux:modal name="add-note-{{ $formPrefix }}" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Progress Note</flux:heading>
                <flux:text class="mt-2">Record a progress update for this stage.</flux:text>
            </div>

            <flux:textarea
                wire:model="{{ $formPrefix }}.newNote"
                label="Note"
                rows="4"
                placeholder="Enter your progress note..."
            />

            <div class="flex">
                <flux:spacer />
                <flux:button wire:click="{{ $addNoteMethod }}" variant="primary">Add Note</flux:button>
            </div>
        </div>
    </flux:modal>

    <div class="mt-4 space-y-3">
        @forelse ($noteable->notes as $note)
            <flux:callout
                wire:key="note-{{ $note->id }}"
                variant="secondary"
                icon="{{ $note->user_id === auth()->id() ? 'check' : 'user' }}"
            >
                <flux:callout.heading>{{ $note->user_name }} · {{ $note->created_at->format('d/m/Y H:i') }}</flux:callout.heading>
                <flux:callout.text>{{ $note->body }}</flux:callout.text>
            </flux:callout>
        @empty
            <flux:text class="text-zinc-500">No notes yet.</flux:text>
        @endforelse
    </div>
</div>
