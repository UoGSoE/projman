<form wire:submit="save('deployed')" class="space-y-6">
    {{-- Service Info --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Deployment Lead" wire:model.live="deployedForm.deploymentLeadId">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:input label="Service / Function" value="{{ $this->deployedServiceFunction }}" disabled />


    </div>

    {{-- Live Testing --}}
    <flux:separator />
    <flux:heading size="lg">Live Testing</flux:heading>

    <div class="grid grid-cols-2 gap-4">
        <flux:textarea rows="6" label="Functional Testing"
            wire:model="deployedForm.functionalTests" placeholder="FR1:&#10;FR2:&#10;FR3:" />

        <flux:textarea rows="6" label="Non-Functional Testing"
            wire:model="deployedForm.nonFunctionalTests" placeholder="NFR1:&#10;NFR2:&#10;NFR3:" />
    </div>

    {{-- BAU / Operational --}}
    <flux:separator />
    <flux:input label="BAU / Operational Wiki" wire:model="deployedForm.bauOperationalWiki" placeholder="https://..." />

    {{-- Service Handover --}}
    <flux:separator />
    <flux:heading size="lg">Service Handover</flux:heading>

    <div class="grid grid-cols-3 gap-4">
        {{-- Service Resilience --}}
        <div class="space-y-2">
            <flux:select label="Service Resilience" wire:model="deployedForm.serviceResilienceApproval">
                @foreach ($deployedForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="deployedForm.serviceResilienceNotes" />
        </div>

        {{-- Service Operations --}}
        <div class="space-y-2">
            <flux:select label="Service Operations" wire:model="deployedForm.serviceOperationsApproval">
                @foreach ($deployedForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="deployedForm.serviceOperationsNotes" />
        </div>

        {{-- Service Delivery --}}
        <div class="space-y-2">
            <flux:select label="Service Delivery" wire:model="deployedForm.serviceDeliveryApproval">
                @foreach ($deployedForm->availableApprovalStates as $id => $label)
                    <flux:select.option value="{{ $id }}">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="deployedForm.serviceDeliveryNotes" />
        </div>
    </div>

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3 items-center">
        <flux:button type="submit" variant="primary">Update</flux:button>

        @if($project->deployed->isReadyForServiceAcceptance() && $project->deployed->needsServiceAcceptance())
            <flux:button wire:click="acceptDeploymentService" data-test="service-acceptance-button">
                Service Acceptance
            </flux:button>
        @endif

        @if($project->deployed->hasServiceAcceptance() && $project->deployed->isReadyForApproval() && $project->deployed->needsDeploymentApproval())
            <flux:button wire:click="approveDeployment" variant="filled" data-test="approve-deployment-button">
                Approved (Complete Work Package)
            </flux:button>
        @endif

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
