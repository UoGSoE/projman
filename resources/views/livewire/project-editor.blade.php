<div>
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Edit Work Package</flux:heading>
            <flux:subheading class="flex items-center gap-2">
                <span><b>Title:</b> {{ $project->title }}</span>
                @if($feasibilityForm->approvalStatus !== 'pending')
                    <div>
                        <flux:badge
                            size="sm"
                            :color="$feasibilityForm->approvalStatus === 'approved' ? 'green' : 'red'">
                            {{ ucfirst($feasibilityForm->approvalStatus) }}
                        </flux:badge>
                    </div>
                @endif
            </flux:subheading>
            <flux:subheading class="flex items-center gap-2"><b>Requested By:</b> {{ $project->user->full_name }}
            </flux:subheading>
        </div>

        @if($project->status->canReturnToPreviousStage())
            <flux:modal.trigger name="return-to-previous-stage-modal">
                <flux:button
                    type="button"
                    variant="filled"
                    icon="arrow-uturn-left"
                    size="sm"
                    data-test="return-to-previous-stage-button">
                    Return to {{ $project->status->getPreviousStatus()->label() }}
                </flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <flux:separator variant="subtle" class="mt-6" />

    <flux:tab.group class="mt-6">
        <flux:tabs variant="segmented" wire:model="tab">
            <flux:tab name="ideation">Ideation</flux:tab>
            @admin
                <flux:tab name="feasibility">Feasibility</flux:tab>
                <flux:tab name="scoping">Scoping</flux:tab>
                <flux:tab name="scheduling">Scheduling</flux:tab>
                <flux:tab name="detailed-design">Detailed Design</flux:tab>
                <flux:tab name="development">Development</flux:tab>
                <flux:tab name="build">Build</flux:tab>
                <flux:tab name="testing">Testing</flux:tab>
                <flux:tab name="deployed">Deployed</flux:tab>
            @endadmin
        </flux:tabs>

        {{-- Ideation panel --}}
        <flux:tab.panel name="ideation" class="mt-6 space-y-6">
            @include('livewire.forms.ideation-form')
        </flux:tab.panel>
        @admin
        {{-- Feasibility panel --}}
        <flux:tab.panel name="feasibility" class="mt-6 space-y-6">
            @include('livewire.forms.feasibility-form')
        </flux:tab.panel>

        <flux:tab.panel name="testing" class="mt-6 space-y-6">
            @include('livewire.forms.testing-form')
        </flux:tab.panel>

        <flux:tab.panel name="detailed-design" class="mt-6 space-y-6">
            @include('livewire.forms.detailed-design-form')
        </flux:tab.panel>

        <flux:tab.panel name="scheduling" class="mt-6 space-y-6">
            @include('livewire.forms.scheduling-form')
        </flux:tab.panel>


        <flux:tab.panel name="scoping" class="mt-6 space-y-6">
            @include('livewire.forms.scoping-form')
        </flux:tab.panel>

        <flux:tab.panel name="development" class="mt-6 space-y-6">
            @include('livewire.forms.development-form')
        </flux:tab.panel>

        <flux:tab.panel name="build" class="mt-6 space-y-6">
            @include('livewire.forms.build-form')
        </flux:tab.panel>

        <flux:tab.panel name="deployed" class="mt-6 space-y-6">
            @include('livewire.forms.deployed-form')
        </flux:tab.panel>
        @endadmin
    </flux:tab.group>

    @if($project->status->canReturnToPreviousStage())
        <flux:modal name="return-to-previous-stage-modal" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Return to {{ $project->status->getPreviousStatus()->label() }}?</flux:heading>
                    <flux:subheading class="mt-2">
                        This will move the work package back to the {{ $project->status->getPreviousStatus()->label() }} stage so you can edit or correct the previous panel. The action is recorded in the work package history.
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button
                        wire:click="returnToPreviousStage"
                        variant="primary"
                        data-test="confirm-return-to-previous-stage-button">
                        Return to {{ $project->status->getPreviousStatus()->label() }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
