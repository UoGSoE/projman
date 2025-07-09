<div>
    <flux:heading size="xl" level="1">Create a new project</flux:heading>

    <flux:separator variant="subtle" class="mt-6"/>

    <form wire:submit="saveProjectName">
        <flux:input.group>
            <flux:input placeholder="Project Name" wire:model="projectName" />
            <flux:button icon="plus" variant="primary" type="submit">Save</flux:button>
        </flux:input.group>
    </form>

    <flux:separator variant="subtle" class="mt-6"/>

    <div x-data="{show: $wire.projectId ? true : false}" x-show="show" x-cloak>
        <flux:tab.group class="mt-6">
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

            {{-- Feasibility panel --}}
            <flux:tab.panel name="feasibility" class="mt-6 space-y-6">
                <form wire:submit="save('feasibility')" class="space-y-6">
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

                    <flux:separator />

                    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                        <div>
                            <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                        </div>
                        <div>&nbsp;</div>
                    </div>
                </form>
            </flux:tab.panel>

            <flux:tab.panel name="testing" class="mt-6 space-y-6">
                <form wire:submit="save('testing')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="testingForm.deliverableTitle"
                        disabled
                    />

                    {{-- Test Lead / Service Function --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Test Lead" wire:model="testingForm.testLead" />
                        <flux:input
                            label="Service / Function"
                            wire:model="testingForm.serviceFunction"
                        />
                    </div>

                    {{-- Functional & Non-Functional Testing --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:textarea
                                label="Functional Testing"
                                rows="2"
                                wire:model="testingForm.functionalTestingTitle"
                            />
                            <flux:textarea
                                rows="6"
                                wire:model="testingForm.functionalTests"
                                placeholder="FR1:&#10;FR2:&#10;FR3:"
                            />
                        </div>

                        <div>
                            <flux:textarea
                                label="Non-Functional Testing"
                                rows="2"
                                wire:model="testingForm.nonFunctionalTestingTitle"
                            />
                            <flux:textarea
                                rows="6"
                                wire:model="testingForm.nonFunctionalTests"
                                placeholder="NFR1:&#10;NFR2:&#10;NFR3:"
                            />
                        </div>
                    </div>

                    {{-- Test Repository Link --}}
                    <flux:input
                        label="Test Repository (Approach/Plan/Scripts)"
                        wire:model="testingForm.testRepository"
                        placeholder="https://…"
                    />

                    {{-- Sign-off matrix --}}
                    <div class="grid grid-cols-5 gap-4">
                        <flux:input label="Testing Sign Off" wire:model="testingForm.testingSignOff" />
                        <flux:input label="User Acceptance" wire:model="testingForm.userAcceptance" />
                        <flux:input label="Test Lead" wire:model="testingForm.testingLeadSignOff" />
                        <flux:input label="Service Delivery" wire:model="testingForm.serviceDeliverySignOff" />
                        <flux:input label="Service Resilience" wire:model="testingForm.serviceResilienceSignOff" />
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

            <flux:tab.panel name="detailed-design" class="mt-6 space-y-6">
                <form wire:submit="save('detailed-design')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="detailedDesignForm.deliverableTitle"
                        disabled
                    />

                    {{-- Designed by / Service Function --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Designed by" wire:model="detailedDesignForm.designedBy" />
                        <flux:input
                            label="Service / Function"
                            wire:model="detailedDesignForm.serviceFunction"
                        />
                    </div>

                    {{-- Functional & Non-Functional Requirements --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:textarea
                            label="Functional Requirements"
                            rows="8"
                            wire:model="detailedDesignForm.functionalRequirements"
                        />
                        <flux:textarea
                            label="Non-Functional Requirements"
                            rows="8"
                            wire:model="detailedDesignForm.nonFunctionalRequirements"
                        />
                    </div>

                    {{-- HLD Link --}}
                    <flux:input
                        label="HLD Design"
                        wire:model="detailedDesignForm.hldDesignLink"
                        placeholder="https://…"
                    />

                    {{-- Approvals --}}
                    <div class="grid grid-cols-5 gap-4">
                        <flux:input label="Approvals" value="Approvals" disabled />
                        <flux:input label="Delivery" wire:model="detailedDesignForm.approvalDelivery" />
                        <flux:input label="Operations" wire:model="detailedDesignForm.approvalOperations" />
                        <flux:input label="Resilience" wire:model="detailedDesignForm.approvalResilience" />
                        <flux:input label="Change Board" wire:model="detailedDesignForm.approvalChangeBoard" />
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

            <flux:tab.panel name="scheduling" class="mt-6 space-y-6">
                <form wire:submit="save('scheduling')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="schedulingForm.deliverableTitle"
                        disabled
                    />

                    {{-- Key Skills / CoSE IT staff --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:textarea
                            label="Key skills matched"
                            rows="3"
                            wire:model="schedulingForm.keySkills"
                        />
                        <flux:textarea
                            label="CoSE IT staff"
                            rows="3"
                            wire:model="schedulingForm.coseItStaff"
                            disabled
                        />
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:input
                            label="Estimated Start Date"
                            type="date"
                            wire:model="schedulingForm.estimatedStartDate"
                        />
                        <flux:input
                            label="Estimated Completion Date"
                            type="date"
                            wire:model="schedulingForm.estimatedCompletionDate"
                        />
                        <flux:input
                            label="Change Board Review/Approval Date"
                            type="date"
                            wire:model="schedulingForm.changeBoardDate"
                        />
                    </div>

                    {{-- Assigned To / Priority --}}
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:select label="Assigned To" wire:model="schedulingForm.assignedTo">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($schedulingForm->availableUsers as $id => $name)
                                <flux:select.option value="{{ $id }}">
                                    {{ $name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select label="Priority" wire:model="schedulingForm.priority">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($schedulingForm->availablePriorities as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select label="Team Assignment" wire:model="schedulingForm.teamAssignment">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($schedulingForm->availableTeams as $id => $name)
                                <flux:select.option value="{{ $id }}">
                                    {{ $name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
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


            <flux:tab.panel name="scoping" class="mt-6 space-y-6">
                <form wire:submit="save('scoping')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="scopingForm.deliverableTitle"
                        disabled
                    />

                    {{-- Assessed By --}}
                    <flux:input label="Assessed By" wire:model="scopingForm.assessedBy" />

                    {{-- Effort, In-Scope, Out-of-Scope --}}
                    <div class="grid grid-cols-3 gap-4">
                        <flux:textarea
                            label="Estimated Effort Involved"
                            rows="4"
                            wire:model="scopingForm.estimatedEffort"
                        />

                        <flux:textarea
                            label="In-Scope"
                            rows="4"
                            wire:model="scopingForm.inScope"
                        />

                        <flux:textarea
                            label="Out of Scope"
                            rows="4"
                            wire:model="scopingForm.outOfScope"
                        />
                    </div>

                    {{-- Assumptions --}}
                    <flux:textarea
                        label="Assumptions"
                        rows="3"
                        wire:model="scopingForm.assumptions"
                    />

                    {{-- Skills / Competency --}}
                    <flux:select label="Skills / Competency required" wire:model="scopingForm.skillsRequired">
                        <flux:select.option value="">– Select –</flux:select.option>
                        @foreach($scopingForm->availableSkills as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:separator />

                    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                        <div>
                            <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                        </div>
                        <div>&nbsp;</div>
                    </div>
                </form>
            </flux:tab.panel>

            <flux:tab.panel name="development" class="mt-6 space-y-6">
                <form wire:submit="save('development')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="developmentForm.deliverableTitle"
                        disabled
                    />

                    {{-- Lead Developer / Development Team --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Lead Developer" wire:model="developmentForm.leadDeveloper" />
                        <flux:input label="Development Team" wire:model="developmentForm.developmentTeam" />
                    </div>

                    {{-- Technical Approach --}}
                    <flux:textarea
                        label="Technical Approach"
                        rows="4"
                        wire:model="developmentForm.technicalApproach"
                    />

                    {{-- Repository Link --}}
                    <flux:input
                        label="Repository Link"
                        wire:model="developmentForm.repositoryLink"
                        placeholder="https://…"
                    />

                    {{-- Status / Dates --}}
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <flux:select label="Status" wire:model="developmentForm.status">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($developmentForm->availableStatuses as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input
                            label="Start Date"
                            type="date"
                            wire:model="developmentForm.startDate"
                        />

                        <flux:input
                            label="Completion Date"
                            type="date"
                            wire:model="developmentForm.completionDate"
                        />
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

            <flux:tab.panel name="deployed" class="mt-6 space-y-6">
                <form wire:submit="save('deployed')" class="space-y-6">
                    {{-- Deliverable (readonly) --}}
                    <flux:input
                        label="Deliverable"
                        wire:model="deployedForm.deliverableTitle"
                        disabled
                    />

                    {{-- Deployment Details --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Deployed By" wire:model="deployedForm.deployedBy" />
                        <flux:input
                            label="Deployment Date"
                            type="date"
                            wire:model="deployedForm.deploymentDate"
                        />
                    </div>

                    {{-- Environment Details --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select label="Environment" wire:model="deployedForm.environment">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($deployedForm->availableEnvironments as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select label="Status" wire:model="deployedForm.status">
                            <flux:select.option value="">– Select –</flux:select.option>
                            @foreach($deployedForm->availableStatuses as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Version / URL --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="Version" wire:model="deployedForm.version" />
                        <flux:input
                            label="Production URL"
                            wire:model="deployedForm.productionUrl"
                            placeholder="https://…"
                        />
                    </div>

                    {{-- Sign-off matrix --}}
                    <div class="grid grid-cols-4 gap-4">
                        <flux:input label="Deployment Sign Off" wire:model="deployedForm.deploymentSignOff" />
                        <flux:input label="Operations Sign Off" wire:model="deployedForm.operationsSignOff" />
                        <flux:input label="User Acceptance" wire:model="deployedForm.userAcceptance" />
                        <flux:input label="Service Delivery" wire:model="deployedForm.serviceDeliverySignOff" />
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

        </flux:tab.group>
    </div>
</div>
