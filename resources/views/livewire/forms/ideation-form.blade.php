<form wire:submit="save('ideation')" class="space-y-6">
    {{-- Top row: Name & Schools/Group --}}
    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
        <flux:input label="Name" value="{{ $project->user->full_name }}" disabled />
        <flux:input label="Schools / Group" wire:model="ideationForm.schoolGroup" />
    </div>

    {{-- Objective & Business Case --}}
    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
        <flux:textarea label="Objective" rows="4" wire:model="ideationForm.objective" />
        <flux:textarea label="Business Case" rows="4" wire:model="ideationForm.businessCase" />
    </div>

    {{-- Benefits & Deadline/Initiative --}}
    <div class="grid md:grid-cols-2 gap-4">
        <flux:textarea label="Benefits Expected" rows="4" wire:model="ideationForm.benefits" />

        <div class="space-y-4">
            <flux:input label="Deadline / Key Milestone" type="date"
                wire:model="ideationForm.deadline" />

            <flux:select label="Strategic Initiative" wire:model="ideationForm.initiative">
                @foreach ($ideationForm->availableStrategicInitiatives as $key => $label)
                    <flux:select.option value="{{ $key }}">
                        {{ $key }} - {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:separator />

    <div class="flex items-center gap-2">
        <flux:button wire:click="saveAndAdvance('ideation')" variant="primary" icon:trailing="arrow-right">
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
