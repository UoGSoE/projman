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

    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
        <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
        <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">Advance
            To Next Stage
        </flux:button>
    </div>
</form>
