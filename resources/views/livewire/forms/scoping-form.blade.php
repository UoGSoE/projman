<form wire:submit="save('scoping')" class="space-y-6">
    {{-- Assessed By --}}
    <flux:select label="Assessed By" wire:model="scopingForm.assessedBy">
        @foreach ($this->availableUsers as $user)
            <flux:select.option value="{{ $user->id }}">
                {{ $user->full_name }}
            </flux:select.option>
        @endforeach
    </flux:select>

    {{-- Effort, In-Scope, Out-of-Scope --}}
    <div class="grid grid-cols-3 gap-4">
        <flux:select label="Estimated Effort Involved" wire:model="scopingForm.estimatedEffort">
            <flux:select.option value="">– Select Effort Scale –</flux:select.option>
            @foreach (\App\Enums\EffortScale::cases() as $scale)
                <flux:select.option value="{{ $scale->value }}">
                    {{ $scale->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea label="In-Scope" rows="4" wire:model="scopingForm.inScope" />

        <flux:textarea label="Out of Scope" rows="4" wire:model="scopingForm.outOfScope" />
    </div>

    {{-- Assumptions --}}
    <flux:textarea label="Assumptions" rows="3" wire:model="scopingForm.assumptions" />

    <flux:pillbox multiple searchable placeholder="Choose skills/competency..."
        label="Skills / Competency required" wire:model.live="scopingForm.skillsRequired">
        @foreach ($availableSkills as $skill)
            <flux:pillbox.option value="{{ $skill->id }}">
                {{ $skill->name }}
            </flux:pillbox.option>
        @endforeach
    </flux:pillbox>

    {{-- Software Development Toggle --}}
    <flux:checkbox wire:model="scopingForm.requiresSoftwareDev" data-test="requires-software-dev-checkbox" label="Requires in-house/custom software development" />

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-2">
        <flux:button type="submit" variant="primary">Update</flux:button>

        <flux:button wire:click="submitScoping" variant="filled" data-test="submit-scoping-button">
            Submit
        </flux:button>

        <flux:button icon:trailing="arrow-right" wire:click="advanceToNextStage()">
            Advance To Next Stage
        </flux:button>
    </div>
</form>
