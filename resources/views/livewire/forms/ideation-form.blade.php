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

    <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
        <flux:button type="submit" variant="primary">
            Update
        </flux:button>
        <flux:button class="w-1/4" icon:trailing="arrow-right" wire:click="advanceToNextStage()">
            Submit
        </flux:button>
    </div>
</form>
