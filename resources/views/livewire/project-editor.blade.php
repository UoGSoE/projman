<div>
    <flux:heading size="xl" level="1">Edit Work Package</flux:heading>
    <flux:subheading class="flex items-center gap-2"><b>Title:</b> {{ $project->title }}</flux:subheading>
    <flux:subheading class="flex items-center gap-2"><b>Requested By:</b> {{ $project->user->full_name }}
    </flux:subheading>
    <flux:separator variant="subtle" class="mt-6" />

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
                    <flux:input label="Name" value="{{ $project->user->full_name }}" disabled />
                    <flux:input label="Schools / Group" wire:model="ideationForm.schoolGroup" />
                </div>

                {{-- Objective & Business Case --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:textarea label="Objective" rows="4" wire:model="ideationForm.objective" />
                    <flux:textarea label="Business Case" rows="4" wire:model="ideationForm.businessCase" />
                </div>

                {{-- Benefits & Deadline/Initiative --}}
                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:textarea label="Benefits Expected" rows="4" wire:model="ideationForm.benefits" />

                    <div class="space-y-4">
                        <flux:input label="Deadline / Key Milestone" type="date"
                            wire:model="ideationForm.deadline" />

                        <flux:select label="Strategic Initiative" wire:model="ideationForm.initiative">
                            @foreach ($ideationForm->availableStrategicInitiatives as $id => $label)
                                <flux:select.option value="{{ $id }}">
                                    {{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <flux:separator />

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                    <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">
                        Advance To Next Stage
                    </flux:button>
                </div>
            </form>
        </flux:tab.panel>

        {{-- Feasibility panel --}}
        <flux:tab.panel name="feasibility" class="mt-6 space-y-6">
            <form wire:submit="save('feasibility')" class="space-y-6">

                {{-- Assessed By / Date Assessed --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:select label="Assessed By" wire:model="feasibilityForm.assessedBy">
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

                <flux:separator />

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                    <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">Advance To
                        Next Stage
                    </flux:button>
                </div>
            </form>
        </flux:tab.panel>

        <flux:tab.panel name="testing" class="mt-6 space-y-6">
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
                        <flux:textarea label="Functional Testing" rows="2"
                            wire:model="testingForm.functionalTestingTitle" />
                        <flux:textarea rows="6" wire:model="testingForm.functionalTests"
                            placeholder="FR1:&#10;FR2:&#10;FR3:" />
                    </div>

                    <div>
                        <flux:textarea label="Non-Functional Testing" rows="2"
                            wire:model="testingForm.nonFunctionalTestingTitle" />
                        <flux:textarea rows="6" wire:model="testingForm.nonFunctionalTests"
                            placeholder="NFR1:&#10;NFR2:&#10;NFR3:" />
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
        </flux:tab.panel>

        <flux:tab.panel name="detailed-design" class="mt-6 space-y-6">
            <form wire:submit="save('detailed-design')" class="space-y-6">
                {{-- Designed by / Service Function --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:select label="Designed by" wire:model="detailedDesignForm.designedBy">
                        @foreach ($this->availableUsers as $user)
                            <flux:select.option value="{{ $user->id }}">
                                {{ $user->full_name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Service / Function" wire:model="detailedDesignForm.serviceFunction" />
                </div>

                {{-- Functional & Non-Functional Requirements --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:textarea label="Functional Requirements" rows="8"
                        wire:model="detailedDesignForm.functionalRequirements" />
                    <flux:textarea label="Non-Functional Requirements" rows="8"
                        wire:model="detailedDesignForm.nonFunctionalRequirements" />
                </div>

                {{-- HLD Link --}}
                <flux:input label="HLD Design" wire:model="detailedDesignForm.hldDesignLink"
                    placeholder="https://…" />

                {{-- Approvals --}}
                <div class="grid grid-cols-5 gap-4">
                    <flux:input label="Approvals" value="Approvals" disabled />
                    <flux:select label="Delivery" wire:model="detailedDesignForm.approvalDelivery">
                        @foreach ($detailedDesignForm->availableApprovalStates as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Operations" wire:model="detailedDesignForm.approvalOperations">
                        @foreach ($detailedDesignForm->availableApprovalStates as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Resilience" wire:model="detailedDesignForm.approvalResilience">
                        @foreach ($detailedDesignForm->availableApprovalStates as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="Change Board" wire:model="detailedDesignForm.approvalChangeBoard">
                        @foreach ($detailedDesignForm->availableApprovalStates as $id => $label)
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
        </flux:tab.panel>

        <flux:tab.panel name="scheduling" class="mt-6 space-y-6">
            <form wire:submit="save('scheduling')" class="space-y-6">
                {{-- Key Skills / CoSE IT staff --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:textarea label="Key skills matched" rows="3" wire:model="schedulingForm.keySkills" />
                    <flux:textarea label="CoSE IT staff" rows="3" wire:model="schedulingForm.coseItStaff"
                        disabled />
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <flux:input label="Estimated Start Date" type="date"
                        wire:model="schedulingForm.estimatedStartDate" />
                    <flux:input label="Estimated Completion Date" type="date"
                        wire:model="schedulingForm.estimatedCompletionDate" />
                    <flux:input label="Change Board Review/Approval Date" type="date"
                        wire:model="schedulingForm.changeBoardDate" />
                </div>

                {{-- Assigned To / Priority --}}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <flux:select label="Assigned To" wire:model="schedulingForm.assignedTo">
                        {{--  TODO: this should allow for multiple users --}}
                        @foreach ($this->availableUsers as $user)
                            <flux:select.option value="{{ $user->id }}">
                                {{ $user->full_name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Priority" wire:model="schedulingForm.priority">
                        <flux:select.option value="">– Select –</flux:select.option>
                        @foreach ($schedulingForm->availablePriorities as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Team Assignment" wire:model="schedulingForm.teamAssignment">
                        <flux:select.option value="">– Select –</flux:select.option>
                        @foreach ($schedulingForm->availableTeams as $id => $name)
                            <flux:select.option value="{{ $id }}">
                                {{ $name }}
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
        </flux:tab.panel>


        <flux:tab.panel name="scoping" class="mt-6 space-y-6">
            <form wire:submit="save('scoping')" class="space-y-6">
                {{-- Assessed By --}}
                <flux:select label="Assessed By" wire:model="scopingForm.assessedBy">
                    @foreach ($this->availableUsers as $user)
                        <flux:select.option value="{{ $user->id }}">
                            {{ $user->full_name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Effort, In-Scope, Out-of-Scope --}}
                <div class="grid grid-cols-3 gap-4">
                    <flux:textarea label="Estimated Effort Involved" rows="4"
                        wire:model="scopingForm.estimatedEffort" />

                    <flux:textarea label="In-Scope" rows="4" wire:model="scopingForm.inScope" />

                    <flux:textarea label="Out of Scope" rows="4" wire:model="scopingForm.outOfScope" />
                </div>

                {{-- Assumptions --}}
                <flux:textarea label="Assumptions" rows="3" wire:model="scopingForm.assumptions" />

                {{-- Skills / Competency --}}
                <flux:select variant="listbox" multiple label="Skills / Competency required"
                    wire:model="scopingForm.skillsRequired">
                    @foreach ($scopingForm->availableSkills as $id => $label)
                        <flux:select.option value="{{ $id }}">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:separator />

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                    <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">Advance
                        To Next Stage
                    </flux:button>
                </div>
            </form>
        </flux:tab.panel>

        <flux:tab.panel name="development" class="mt-6 space-y-6">
            <form wire:submit="save('development')" class="space-y-6">
                {{-- Lead Developer / Development Team --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:select label="Lead Developer" wire:model="developmentForm.leadDeveloper">
                        @foreach ($this->availableUsers as $user)
                            <flux:select.option value="{{ $user->id }}">
                                {{ $user->full_name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select variant="listbox" multiple label="Development Team"
                        wire:model="developmentForm.developmentTeam">
                        @foreach ($this->availableUsers as $user)
                            <flux:select.option value="{{ $user->id }}">
                                {{ $user->full_name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Technical Approach --}}
                <flux:textarea label="Technical Approach" rows="4"
                    wire:model="developmentForm.technicalApproach" />

                {{-- Development Notes --}}
                <flux:textarea label="Development Notes" rows="4"
                    wire:model="developmentForm.developmentNotes" />

                {{--  Code Review Notes --}}
                <flux:textarea label="Code Review Notes" rows="4"
                    wire:model="developmentForm.codeReviewNotes" />

                {{-- Repository Link --}}
                <flux:input label="Repository Link" wire:model="developmentForm.repositoryLink"
                    placeholder="https://…" />

                {{-- Status / Dates --}}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <flux:select label="Status" wire:model="developmentForm.status">
                        <flux:select.option value="">– Select –</flux:select.option>
                        @foreach ($developmentForm->availableStatuses as $id => $label)
                            <flux:select.option value="{{ $id }}">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input label="Start Date" type="date" wire:model="developmentForm.startDate" />

                    <flux:input label="Completion Date" type="date" wire:model="developmentForm.completionDate" />
                </div>

                <flux:separator />

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <flux:button type="submit" variant="primary" class="w-full">Save</flux:button>
                    <flux:button class="w-full" icon:trailing="arrow-right" wire:click="advanceToNextStage()">Advance
                        To Next Stage
                    </flux:button>
                </div>
            </form>
        </flux:tab.panel>

        <flux:tab.panel name="deployed" class="mt-6 space-y-6">
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
        </flux:tab.panel>

    </flux:tab.group>
</div>
