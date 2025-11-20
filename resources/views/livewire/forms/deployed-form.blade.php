<form wire:submit="save('deployed')" class="space-y-6">
    {{-- Deployment Details --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Deployed By" wire:model="deployedForm.deployedBy">
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="Deployment Date" type="date" wire:model="deployedForm.deploymentDate" />
    </div>

    {{-- Environment Details --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Environment" wire:model="deployedForm.environment">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($deployedForm->availableEnvironments as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Status" wire:model="deployedForm.status">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($deployedForm->availableStatuses as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Version / URL --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:input label="Version" wire:model="deployedForm.version" />
        <flux:input label="Production URL" wire:model="deployedForm.productionUrl"
            placeholder="https://…" />
    </div>

    {{-- Sign-off matrix --}}
    <div class="grid grid-cols-5 gap-4">
        <flux:select label="Deployment Sign Off" wire:model="deployedForm.deploymentSignOff">
            @foreach ($deployedForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Operations Sign Off" wire:model="deployedForm.operationsSignOff">
            @foreach ($deployedForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="User Acceptance" wire:model="deployedForm.userAcceptanceSignOff">
            @foreach ($deployedForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Service Delivery" wire:model="deployedForm.serviceDeliverySignOff">
            @foreach ($deployedForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Change Advisory" wire:model="deployedForm.changeAdvisorySignOff">
            @foreach ($deployedForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:separator />

    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
        <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
    </div>
</form>
