<div>
    <div class="flex flex-row gap-6 h-full justify-between items-center">
        <flux:heading size="xl" level="1">All work packages</flux:heading>
        <flux:button variant="primary" size="sm" class="cursor-pointer" href="{{ route('project.create') }}">Export</flux:button>
    </div>

    <flux:separator variant="subtle" class="mt-6"/>

    <livewire:project-status-table />
</div>
