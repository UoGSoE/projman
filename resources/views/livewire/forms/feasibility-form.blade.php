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
        <div class="space-y-2">
            <flux:select label="Is there an existing UoG solution that meets the need?"
                wire:model="feasibilityForm.existingSolutionStatus">
                <flux:select.option value="">Select...</flux:select.option>
                <flux:select.option value="yes">Yes</flux:select.option>
                <flux:select.option value="no">No</flux:select.option>
                <flux:select.option value="yes_not_practical">Yes - not practical</flux:select.option>
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="feasibilityForm.existingSolutionNotes" />
        </div>

        <div class="space-y-2">
            <flux:select label="Is there an off-the-shelf solution available?"
                wire:model="feasibilityForm.offTheShelfSolutionStatus">
                <flux:select.option value="">Select...</flux:select.option>
                <flux:select.option value="yes">Yes</flux:select.option>
                <flux:select.option value="no">No</flux:select.option>
                <flux:select.option value="yes_not_practical">Yes - not practical</flux:select.option>
            </flux:select>
            <flux:textarea rows="3" placeholder="Notes..." wire:model="feasibilityForm.offTheShelfSolutionNotes" />
        </div>
    </div>

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3 items-center">
        @if($errors->isEmpty() && $feasibilityForm->approvalStatus === 'pending' && $project->feasibility->isReadyForApproval() && $project->feasibility->hasProperSolutionAssessment())
            <flux:button
                wire:click="approveFeasibility"
                type="button"
                variant="primary"
                color="emerald"
                :disabled="$feasibilityForm->existingSolutionStatus === 'yes' || $feasibilityForm->offTheShelfSolutionStatus === 'yes'"
                data-test="approve-feasibility-button">
                Approve
            </flux:button>
            <flux:modal.trigger name="reject-feasibility-modal">
                <flux:button type="button" variant="danger" data-test="reject-feasibility-button">Reject</flux:button>
            </flux:modal.trigger>
        @endif

        <flux:button wire:click="saveAndAdvance('feasibility')" variant="primary" icon:trailing="arrow-right">
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
