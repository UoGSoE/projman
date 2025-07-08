<div>
    <div class="flex justify-between items-center">
        <flux:heading size="xl" level="1">Good {{ $partOfDay }}, {{ auth()->user()->first_name }}</flux:heading>

        <flux:button variant="primary" size="sm" class="cursor-pointer" href="{{ route('project.create') }}" wire:navigate>Start a new project</flux:button>
    </div>

        <flux:separator variant="subtle" class="mt-6"/>

        <livewire:project-status-table :userId="auth()->user()->id" />

        <flux:separator variant="subtle" />

</div>
