<div>
    <flux:heading size="xl" level="1">Create a new project</flux:heading>

    <flux:separator variant="subtle" class="mt-6"/>

    <form wire:submit="save">
        <flux:input.group>
            <flux:input placeholder="Project Name" wire:model="projectName" autofocus />
            <flux:button icon="plus" variant="primary" type="submit">Create</flux:button>
        </flux:input.group>
    </form>
</div>
