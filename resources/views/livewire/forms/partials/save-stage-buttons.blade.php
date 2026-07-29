<flux:button.group>
    <flux:button type="submit" data-test="save-{{ $stage->value }}-button">Save</flux:button>
    <flux:dropdown align="end">
        <flux:button icon="chevron-down" aria-label="More save options" />
        <flux:menu>
            <flux:menu.item wire:click="saveAndMakeStageCurrent('{{ $stage->value }}')" data-test="save-and-make-current-{{ $stage->value }}-button">
                Save and make this stage current
            </flux:menu.item>
            <flux:menu.item wire:click="saveAndMakeNextStageCurrent('{{ $stage->value }}')" data-test="save-and-make-next-current-{{ $stage->value }}-button">
                Save and make {{ $stage->getNextStatus($project)->label() }} current
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:button.group>
