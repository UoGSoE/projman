<form wire:submit="save('detailed-design')" class="space-y-6">
    {{-- Designed by / Service Function --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Designed by" wire:model="detailedDesignForm.designedBy">
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="Service / Function" wire:model="detailedDesignForm.serviceFunction" />
    </div>

    {{-- Functional & Non-Functional Requirements --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea label="Functional Requirements" rows="8"
            wire:model="detailedDesignForm.functionalRequirements" />
        <flux:textarea label="Non-Functional Requirements" rows="8"
            wire:model="detailedDesignForm.nonFunctionalRequirements" />
    </div>

    {{-- HLD Link --}}
    <flux:input label="HLD Design" wire:model="detailedDesignForm.hldDesignLink"
        placeholder="https://…" />

    {{-- Approvals --}}
    <div class="grid grid-cols-5 gap-4">
        <flux:input label="Approvals" value="Approvals" disabled />
        <flux:select label="Delivery" wire:model="detailedDesignForm.approvalDelivery">
            @foreach ($detailedDesignForm->availableApprovalStates as $label)
                <flux:select.option value="{{ $label }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Operations" wire:model="detailedDesignForm.approvalOperations">
            @foreach ($detailedDesignForm->availableApprovalStates as $label)
                <flux:select.option value="{{ $label }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Resilience" wire:model="detailedDesignForm.approvalResilience">
            @foreach ($detailedDesignForm->availableApprovalStates as $label)
                <flux:select.option value="{{ $label }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Change Board" wire:model="detailedDesignForm.approvalChangeBoard">
            @foreach ($detailedDesignForm->availableApprovalStates as $label)
                <flux:select.option value="{{ $label }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:separator />

    <div class="flex items-center gap-2">
        <flux:button wire:click="saveAndAdvance('detailed-design')" variant="primary" icon:trailing="arrow-right">
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
