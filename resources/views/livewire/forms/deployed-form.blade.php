<form wire:submit="save('deployed')" class="space-y-6">
    {{-- Service Info --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <flux:text class="block mb-2 font-medium">Service / Function</flux:text>
            <flux:text>{{ $deployedForm->serviceFunction }}</flux:text>
        </div>

        <flux:select label="Deployment Lead" wire:model="deployedForm.deploymentLeadId">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:input label="System" wire:model="deployedForm.system" />

    {{-- Live Functional Testing --}}
    <flux:separator />
    <flux:heading size="lg">Live Functional Testing</flux:heading>

    <flux:textarea label="FR1" wire:model="deployedForm.fr1" rows="3" />
    <flux:textarea label="FR2" wire:model="deployedForm.fr2" rows="3" />
    <flux:textarea label="FR3" wire:model="deployedForm.fr3" rows="3" />

    {{-- Live Non-Functional Testing --}}
    <flux:separator />
    <flux:heading size="lg">Live Non-Functional Testing</flux:heading>

    <flux:textarea label="NFR1" wire:model="deployedForm.nfr1" rows="3" />
    <flux:textarea label="NFR2" wire:model="deployedForm.nfr2" rows="3" />
    <flux:textarea label="NFR3" wire:model="deployedForm.nfr3" rows="3" />

    {{-- BAU / Operational --}}
    <flux:separator />
    <flux:input label="BAU / Operational Wiki" wire:model="deployedForm.bauOperationalWiki" placeholder="https://..." />

    {{-- Service Handover --}}
    <flux:separator />
    <flux:heading size="lg">Service Handover</flux:heading>

    {{-- Service Resilience --}}
    <flux:select label="Service Resilience" wire:model="deployedForm.serviceResilienceApproval">
        @foreach ($deployedForm->availableApprovalStates as $id => $label)
            <flux:select.option value="{{ $id }}">
                {{ $label }}
            </flux:select.option>
        @endforeach
    </flux:select>
    <flux:textarea label="Service Resilience Notes" wire:model="deployedForm.serviceResilienceNotes" rows="2" />

    {{-- Service Operations --}}
    <flux:select label="Service Operations" wire:model="deployedForm.serviceOperationsApproval">
        @foreach ($deployedForm->availableApprovalStates as $id => $label)
            <flux:select.option value="{{ $id }}">
                {{ $label }}
            </flux:select.option>
        @endforeach
    </flux:select>
    <flux:textarea label="Service Operations Notes" wire:model="deployedForm.serviceOperationsNotes" rows="2" />

    {{-- Service Delivery --}}
    <flux:select label="Service Delivery" wire:model="deployedForm.serviceDeliveryApproval">
        @foreach ($deployedForm->availableApprovalStates as $id => $label)
            <flux:select.option value="{{ $id }}">
                {{ $label }}
            </flux:select.option>
        @endforeach
    </flux:select>
    <flux:textarea label="Service Delivery Notes" wire:model="deployedForm.serviceDeliveryNotes" rows="2" />

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex gap-2">
        <flux:button type="submit" variant="primary">Update</flux:button>

        @if($project->deployed->isReadyForServiceAcceptance() && $project->deployed->needsServiceAcceptance())
            <flux:button wire:click="acceptDeploymentService" data-test="service-acceptance-button">
                Service Acceptance
            </flux:button>
        @endif

        @if($project->deployed->hasServiceAcceptance() && $project->deployed->isReadyForApproval() && $project->deployed->needsDeploymentApproval())
            <flux:button wire:click="approveDeployment" variant="filled" data-test="approve-deployment-button">
                Approved (Complete Project)
            </flux:button>
        @endif
    </div>
</form>
