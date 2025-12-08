<div>
    <flux:heading size="lg">Progress Notes</flux:heading>

    <div class="flex gap-2 mt-4">
        <flux:textarea
            wire:model="newNote"
            rows="2"
            placeholder="Add a progress note..."
            class="flex-1"
        />
        <flux:button wire:click="addNote" variant="primary">Add Note</flux:button>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($noteable->notes as $note)
            <flux:callout wire:key="note-{{ $note->id }}" variant="secondary">
                <flux:callout.heading>{{ $note->user_name }} · {{ $note->created_at->format('d/m/Y H:i') }}</flux:callout.heading>
                <flux:callout.text>{{ $note->body }}</flux:callout.text>
            </flux:callout>
        @empty
            <flux:text class="text-zinc-500">No notes yet.</flux:text>
        @endforelse
    </div>
</div>
