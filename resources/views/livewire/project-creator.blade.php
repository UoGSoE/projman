<div>
    <flux:heading size="xl" level="1">Create a new project</flux:heading>

    <flux:separator variant="subtle" class="mt-6"/>

    <flux:tab.group>
        <flux:tabs variant="segmented" wire:model="tab">
            <flux:tab name="ideation">Ideation</flux:tab>
            <flux:tab name="feasibility">Feasibility</flux:tab>
            <flux:tab name="scoping">Scoping</flux:tab>
            <flux:tab name="scheduling">Scheduling</flux:tab>
            <flux:tab name="detailed-design">Detailed Design</flux:tab>
            <flux:tab name="development">Development</flux:tab>
            <flux:tab name="testing">Testing</flux:tab>
            <flux:tab name="deployed">Deployed</flux:tab>
        </flux:tabs>

        {{-- Ideation panel --}}
        <flux:tab.panel name="ideation" class="mt-6 space-y-6">
            <form wire:submit="save('ideation')" class="space-y-6">
                {{-- Top row: Name & Schools/Group --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:input label="Name" wire:model="ideationForm.name" />
                    <flux:input label="Schools / Group" wire:model="ideationForm.schoolGroup" />
                </div>

                {{-- Deliverable Title --}}
                <flux:input
                    label="Deliverable Title"
                    wire:model="ideationForm.deliverableTitle"
                />

                {{-- Objective & Business Case --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:textarea
                        label="Objective"
                        rows="4"
                        wire:model="ideationForm.objective"
                    />
                    <flux:textarea
                        label="Business Case"
                        rows="4"
                        wire:model="ideationForm.businessCase"
                    />
                </div>

                {{-- Benefits & Deadline/Initiative --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:textarea
                        label="Benefits Expected"
                        rows="4"
                        wire:model="ideationForm.benefits"
                    />

                    <div class="space-y-4">
                        <flux:input
                            label="Deadline / Key Milestone"
                            type="date"
                            wire:model="ideationForm.deadline"
                        />

                        <flux:select label="Strategic Initiative" wire:model="ideationForm.initiative">
                            @foreach($ideationForm->availableStrategicInitiatives as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <flux:separator />

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <div>
                        <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                    </div>
                    <div>&nbsp;</div>
                </div>
            </form>
        </flux:tab.panel>

        {{-- Other panels… --}}
    <flux:tab.panel name="feasibility" class="mt-6 space-y-6">
    {{-- Deliverable Title (readonly/auto‐populated) --}}
    <flux:input
        label="Deliverable Title"
        wire:model="feasibilityForm.deliverableTitle"
        disabled
    />

    {{-- Assessed By / Date Assessed --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:input label="Assessed By" wire:model="feasibilityForm.assessedBy" />
        <flux:input
        label="Date Assessed"
        type="date"
        wire:model="feasibilityForm.dateAssessed"
        />
    </div>

    {{-- Credence & Cost/Benefit --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea
        label="Technical Credence"
        rows="4"
        wire:model="feasibilityForm.technicalCredence"
        />
        <flux:textarea
        label="Cost / Benefit Case"
        rows="4"
        wire:model="feasibilityForm.costBenefitCase"
        />
    </div>

    {{-- Dependencies & Deadlines / Alternative --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea
        label="Dependencies / Prerequisites"
        rows="4"
        wire:model="feasibilityForm.dependenciesPrerequisites"
        />

        <div class="space-y-4">
        <flux:select
            label="Deadlines Achievable?"
            wire:model="feasibilityForm.deadlinesAchievable"
        >
            <flux:select.option value="yes">Yes</flux:select.option>
            <flux:select.option value="no">No</flux:select.option>
        </flux:select>

        <flux:textarea
            label="Alternative Proposal"
            rows="2"
            wire:model="feasibilityForm.alternativeProposal"
        />
        </div>
    </div>
    </flux:tab.panel>

    <flux:tab.panel name="testing" class="mt-6 space-y-6">
    {{-- Test Lead / Service Function --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:input label="Test Lead" wire:model="testLead" />
        <flux:input
        label="Service / Function"
        wire:model="serviceFunction"
        />
    </div>

    {{-- Functional & Non-Functional Testing --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
        <flux:textarea
            label="Functional Testing"
            rows="2"
            wire:model="functionalTestingTitle"
        />
        <flux:textarea
            rows="6"
            wire:model="functionalTests"
            placeholder="FR1:&#10;FR2:&#10;FR3:"
        />
        </div>

        <div>
        <flux:textarea
            label="Non-Functional Testing"
            rows="2"
            wire:model="nonFunctionalTestingTitle"
        />
        <flux:textarea
            rows="6"
            wire:model="nonFunctionalTests"
            placeholder="NFR1:&#10;NFR2:&#10;NFR3:"
        />
        </div>
    </div>

    {{-- Test Repository Link --}}
    <flux:input
        label="Test Repository (Approach/Plan/Scripts)"
        wire:model="testRepository"
        placeholder="https://…"
    />

    {{-- Sign-off matrix --}}
    <div class="grid grid-cols-5 gap-4">
        <flux:input label="Testing Sign Off" wire:model="testingSignOff" />
        <flux:input label="User Acceptance" wire:model="userAcceptance" />
        <flux:input label="Test Lead" wire:model="testingLeadSignOff" />
        <flux:input label="Service Delivery" wire:model="serviceDeliverySignOff" />
        <flux:input label="Service Resilience" wire:model="serviceResilienceSignOff" />
    </div>
    </flux:tab.panel>

    <flux:tab.panel name="detailed-design" class="mt-6 space-y-6">
    {{-- Deliverable (readonly) --}}
    <flux:input
        label="Deliverable"
        wire:model="deliverableTitle"
        disabled
    />

    {{-- Designed by / Service Function --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:input label="Designed by" wire:model="designedBy" />
        <flux:input
        label="Service / Function"
        wire:model="serviceFunction"
        />
    </div>

    {{-- Functional & Non-Functional Requirements --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea
        label="Functional Requirements"
        rows="8"
        wire:model="functionalRequirements"
        />
        <flux:textarea
        label="Non-Functional Requirements"
        rows="8"
        wire:model="nonFunctionalRequirements"
        />
    </div>

    {{-- HLD Link --}}
    <flux:input
        label="HLD Design"
        wire:model="hldDesignLink"
        placeholder="https://…"
    />

    {{-- Approvals --}}
    <div class="grid grid-cols-5 gap-4">
        <flux:input label="Approvals" wire:model="approvalsHeader" disabled />
        <flux:input label="Delivery" wire:model="approvalDelivery" />
        <flux:input label="Operations" wire:model="approvalOperations" />
        <flux:input label="Resilience" wire:model="approvalResilience" />
        <flux:input label="Change Board" wire:model="approvalChangeBoard" />
    </div>
    </flux:tab.panel>

    <flux:tab.panel name="scheduling" class="mt-6 space-y-6">
    {{-- Deliverable (readonly) --}}
    <flux:input
        label="Deliverable"
        wire:model="deliverableTitle"
        disabled
    />

    {{-- Key Skills / CoSE IT staff --}}
    <div class="grid grid-cols-2 gap-4">
        <flux:textarea
        label="Key skills matched"
        rows="3"
        wire:model="keySkills"
        />
        <flux:textarea
        label="CoSE IT staff"
        rows="3"
        wire:model="coseItStaff"
        disabled
        />
    </div>

    {{-- Dates --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <flux:input
        label="Estimated Start Date"
        type="date"
        wire:model="estimatedStartDate"
        />
        <flux:input
        label="Estimated Completion Date"
        type="date"
        wire:model="estimatedCompletionDate"
        />
        <flux:input
        label="Change Board Review/Approval Date"
        type="date"
        wire:model="changeBoardDate"
        />
    </div>

    {{-- Assigned To / Priority --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <flux:select label="Assigned To" wire:model="assignedTo">
        <flux:select.option value="">– Select –</flux:select.option>
        @foreach($users as $id => $name)
            <flux:select.option value="{{ $id }}">
            {{ $name }}
            </flux:select.option>
        @endforeach
        </flux:select>

        <flux:select label="Priority" wire:model="priority">
        <flux:select.option value="">– Select –</flux:select.option>
        <flux:select.option value="low">Low</flux:select.option>
        <flux:select.option value="medium">Medium</flux:select.option>
        <flux:select.option value="high">High</flux:select.option>
        </flux:select>
    </div>
    </flux:tab.panel>


    <flux:tab.panel name="scoping" class="mt-6 space-y-6">
    {{-- Deliverable (readonly) --}}
    <flux:input
        label="Deliverable"
        wire:model="deliverableTitle"
        disabled
    />

    {{-- Assessed By --}}
    <flux:input label="Assessed By" wire:model="assessedBy" />

    {{-- Effort, In-Scope, Out-of-Scope --}}
    <div class="grid grid-cols-3 gap-4">
        <flux:textarea
        label="Estimated Effort Involved"
        rows="4"
        wire:model="estimatedEffort"
        />

        <flux:textarea
        label="In-Scope"
        rows="4"
        wire:model="inScope"
        />

        <flux:textarea
        label="Out of Scope"
        rows="4"
        wire:model="outOfScope"
        />
    </div>

    {{-- Assumptions --}}
    <flux:textarea
        label="Assumptions"
        rows="3"
        wire:model="assumptions"
    />

    {{-- Skills / Competency --}}
    <flux:select label="Skills / Competency required" wire:model="skillsRequired">
        <flux:select.option value="">– Select –</flux:select.option>
        @foreach($skills as $id => $label)
        <flux:select.option value="{{ $id }}">
            {{ $label }}
        </flux:select.option>
        @endforeach
    </flux:select>
    </flux:tab.panel>


</flux:tab.group>

</div>
