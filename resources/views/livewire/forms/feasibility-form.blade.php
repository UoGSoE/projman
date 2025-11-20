<form wire:submit="save('feasibility')" class="space-y-6">

    {{-- Assessed By / Date Assessed --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:select label="Assessed By" wire:model="feasibilityForm.assessedBy">
            <flux:select.option value="">Select</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="Date Assessed" type="date" wire:model="feasibilityForm.dateAssessed" />
    </div>

    {{-- Credence & Cost/Benefit --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea label="Technical Credence" rows="4"
            wire:model="feasibilityForm.technicalCredence" />
        <flux:textarea label="Cost / Benefit Case" rows="4"
            wire:model="feasibilityForm.costBenefitCase" />
    </div>

    {{-- Dependencies & Deadlines / Alternative --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea label="Dependencies / Prerequisites" rows="4"
            wire:model="feasibilityForm.dependenciesPrerequisites" />

        <div class="space-y-4">
            <flux:radio.group wire:model="feasibilityForm.deadlinesAchievable"
                label="Deadlines Achievable?">
                <flux:radio value="yes" label="Yes" />
                <flux:radio value="no" label="No" />
            </flux:radio.group>

            <flux:textarea label="Alternative Proposal" rows="2"
                wire:model="feasibilityForm.alternativeProposal" />
        </div>
    </div>

    {{-- Existing & Off-the-Shelf Solutions --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea label="Is there an existing UoG solution that meets the need?" rows="4"
            wire:model="feasibilityForm.existingSolution" />
        <flux:textarea label="Is there an off-the-shelf solution available?" rows="4"
            wire:model="feasibilityForm.offTheShelfSolution" />
    </div>

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
        <flux:button type="submit" variant="primary">Update</flux:button>

        <div class="flex flex-col md:flex-row justify-end gap-4">
            @if($feasibilityForm->approvalStatus === 'pending' && $project->feasibility->isReadyForApproval())
                <flux:button
                    wire:click="approveFeasibility"
                    type="button"
                    variant="primary"
                    color="emerald"
                    :disabled="!empty($feasibilityForm->existingSolution)"
                    data-test="approve-feasibility-button">
                    Approve
                </flux:button>
                <flux:modal.trigger name="reject-feasibility-modal">
                    <flux:button type="button" variant="danger" data-test="reject-feasibility-button">Reject</flux:button>
                </flux:modal.trigger>
            @endif

            <flux:button icon:trailing="arrow-right" type="button" wire:click="advanceToNextStage()">
                Advance To Next Stage
            </flux:button>
        </div>
    </div>
</form>

{{-- Reject Modal --}}
<flux:modal name="reject-feasibility-modal" variant="flyout" class="md:w-96">
    <form wire:submit="rejectFeasibility" class="space-y-6">
        <div>
            <flux:heading size="lg">Reject Feasibility</flux:heading>
            <flux:subheading class="mt-2">Please provide a reason for rejecting this feasibility assessment.</flux:subheading>
        </div>

        <flux:textarea
            label="Reason for Rejection"
            wire:model="feasibilityForm.rejectReason"
            rows="6"
            required />

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">Confirm Rejection</flux:button>
        </div>
    </form>
</flux:modal>
