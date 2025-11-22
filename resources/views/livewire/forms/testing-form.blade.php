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

    {{-- UAT Tester / Department/Office --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="UAT Tester" wire:model="testingForm.uatTesterId">
            <flux:select.option value="">Select UAT tester...</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="Department / Office" wire:model="testingForm.departmentOffice" />
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

    {{-- Sign-off matrix with notes --}}
    <div class="grid grid-cols-5 gap-4">
        {{-- Testing Sign Off --}}
        <div class="space-y-2">
            <flux:select label="Testing Sign Off" wire:model="testingForm.testingSignOff">
                @foreach ($testingForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="testingForm.testingSignOffNotes" />
        </div>

        {{-- User Acceptance --}}
        <div class="space-y-2">
            <flux:select label="User Acceptance" wire:model="testingForm.userAcceptance">
                @foreach ($testingForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="testingForm.userAcceptanceNotes" />
        </div>

        {{-- Test Lead Sign Off --}}
        <div class="space-y-2">
            <flux:select label="Test Lead" wire:model="testingForm.testingLeadSignOff">
                @foreach ($testingForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="testingForm.testingLeadSignOffNotes" />
        </div>

        {{-- Service Delivery --}}
        <div class="space-y-2">
            <flux:select label="Service Delivery" wire:model="testingForm.serviceDeliverySignOff">
                @foreach ($testingForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="testingForm.serviceDeliverySignOffNotes" />
        </div>

        {{-- Service Resilience --}}
        <div class="space-y-2">
            <flux:select label="Service Resilience" wire:model="testingForm.serviceResilienceSignOff">
                @foreach ($testingForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="testingForm.serviceResilienceSignOffNotes" />
        </div>
    </div>

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3">
        <flux:button type="submit" variant="primary">Update</flux:button>

        @if (!empty($testingForm->uatTesterId) && empty($project->testing->uat_requested_at))
            <flux:button wire:click="requestUAT">Request UAT</flux:button>
        @endif

        @if ($testingForm->userAcceptance === 'approved' && empty($project->testing->service_acceptance_requested_at))
            <flux:button wire:click="requestServiceAcceptance">Request Service Acceptance</flux:button>
        @endif

        @if ($project->testing->isReadyForSubmit())
            <flux:button wire:click="submitTesting" icon:trailing="arrow-right">Submit</flux:button>
        @endif
    </div>
</form>
