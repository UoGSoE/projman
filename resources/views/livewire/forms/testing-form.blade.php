<form wire:submit="save('testing')" class="space-y-6">
    {{-- Test Lead / Service Function --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Test Lead" wire:model="testingForm.testLead">
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="Service / Function" wire:model="testingForm.serviceFunction" />
    </div>

    {{-- Functional & Non-Functional Testing --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <flux:textarea rows="6" label="Functional Testing"
                wire:model="testingForm.functionalTests" placeholder="FR1:&#10;FR2:&#10;FR3:" />
        </div>

        <div>
            <flux:textarea rows="6" label="Non-Functional Testing"
                wire:model="testingForm.nonFunctionalTests" placeholder="NFR1:&#10;NFR2:&#10;NFR3:" />
        </div>
    </div>

    {{-- Test Repository Link --}}
    <flux:input label="Test Repository (Approach/Plan/Scripts)" wire:model="testingForm.testRepository"
        placeholder="https://…" />

    {{-- Sign-off matrix --}}
    <div class="grid grid-cols-5 gap-4">
        <flux:select label="Testing Sign Off" wire:model="testingForm.testingSignOff">
            @foreach ($testingForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="User Acceptance" wire:model="testingForm.userAcceptance">
            @foreach ($testingForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Test Lead" wire:model="testingForm.testingLeadSignOff">
            @foreach ($testingForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Service Delivery" wire:model="testingForm.serviceDeliverySignOff">
            @foreach ($testingForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
                    {{ $label }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Service Resilience" wire:model="testingForm.serviceResilienceSignOff">
            @foreach ($testingForm->availableApprovalStates as $id => $label)
                <flux:select.option value="{{ $id }}">
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
