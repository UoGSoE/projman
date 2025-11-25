<form wire:submit="save('scheduling')" class="space-y-6">
    {{-- Key Skills / CoSE IT staff --}}
    <div class="grid grid-cols-2 gap-4">
        {{-- <flux:textarea label="Key skills matched" rows="3" wire:model="schedulingForm.keySkills" /> --}}
        <flux:pillbox multiple placeholder="Choose skills/competency..."
            label="Skills / Competency required" wire:model.live="scopingForm.skillsRequired"
            :disabled="true">
            @foreach ($availableSkills as $skill)
                <flux:pillbox.option value="{{ $skill->id }}">
                    {{ $skill->name }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>
        {{-- <flux:textarea label="CoSE IT staff" rows="3" wire:model="schedulingForm.coseItStaff"
            disabled /> --}}
        <flux:select label="Priority" wire:model="schedulingForm.priority">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach (\App\Enums\Priority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">
                    {{ $priority->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>
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

    {{-- Technical Lead / Change Champion / Board Outcome --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:select label="Technical Lead" wire:model="schedulingForm.technicalLeadId" data-test="technical-lead-select">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Change Champion" wire:model="schedulingForm.changeChampionId" data-test="change-champion-select">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($this->availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">
                    {{ $user->full_name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Change Board Outcome" wire:model="schedulingForm.changeBoardOutcome" data-test="change-board-outcome-select">
            <flux:select.option value="">– Pending –</flux:select.option>
            @foreach (\App\Enums\ChangeBoardOutcome::cases() as $outcome)
                <flux:select.option value="{{ $outcome->value }}">
                    {{ $outcome->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Assigned To / Priority --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <flux:select label="Assigned To (Skill Score)" wire:model="schedulingForm.assignedTo">
            {{--  TODO: this should allow for multiple users --}}
            @if ($this->skillMatchedUsers->isNotEmpty())
                @php
                    $totalRequiredSkills = count($this->scopingForm->skillsRequired ?? []);
                @endphp
                @foreach ($this->skillMatchedUsers as $user)
                    @php
                        // Count how many of the required skills this user has
                        $userSkillIds = $user->skills->pluck('id')->toArray();
                        $matchedSkillsCount = count(
                            array_intersect($this->scopingForm->skillsRequired ?? [], $userSkillIds),
                        );

                    @endphp
                    <flux:select.option value="{{ $user->id }}">
                        {{ $user->full_name }} ({{ $user->total_skill_score }})
                    </flux:select.option>
                @endforeach
            @else
                @foreach ($this->availableUsers as $user)
                    <flux:select.option value="{{ $user->id }}">
                        {{ $user->full_name }}
                    </flux:select.option>
                @endforeach
            @endif
        </flux:select>

        {{-- <flux:select label="Team Assignment" wire:model="schedulingForm.teamAssignment">
            <flux:select.option value="">– Select –</flux:select.option>
            @foreach ($schedulingForm->availableTeams as $id => $name)
                <flux:select.option value="{{ $id }}">
                    {{ $name }}
                </flux:select.option>
            @endforeach
        </flux:select> --}}

        <flux:pillbox multiple placeholder="Pick staff..." label="CoSE IT staff (Skill Score)"
            wire:model.live="schedulingForm.coseItStaff">
            @foreach ($this->skillMatchedUsers as $user)
                <flux:pillbox.option value="{{ $user->id }}">
                    {{ $user->full_name }} - ({{ $user->total_skill_score }})
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>
    </div>

    <flux:separator />

    {{-- Action Buttons --}}
    <div class="flex flex-col md:flex-row gap-4 w-full justify-between items-center">
        {{-- Row 1: Update and Model --}}
        <div class="flex flex-wrap gap-2">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button wire:click="toggleHeatmap" variant="filled" data-test="model-heatmap-button">
                {{ $showHeatmap ? 'Hide Heatmap' : 'Model' }}
            </flux:button>
        </div>

        {{-- Row 2: DCGG Workflow Buttons --}}
        <div class="flex flex-wrap gap-2">
            @if(!$schedulingForm->submittedToDcggAt)
                <flux:button wire:click="submitSchedulingToDCGG" variant="filled" data-test="submit-scheduling-to-dcgg-button">
                    Submit to DCGG
                </flux:button>
            @endif

            @if($schedulingForm->submittedToDcggAt && !$schedulingForm->scheduledAt)
                <flux:button wire:click="scheduleScheduling" variant="primary" data-test="schedule-scheduling-button">
                    Schedule
                </flux:button>
            @endif

            <flux:button icon:trailing="arrow-right" wire:click="advanceToNextStage()">
                Advance To Next Stage
            </flux:button>
        </div>
    </div>
</form>

@if($showHeatmap)
    <div class="mt-8">
        <flux:heading size="lg">Staff Heatmap</flux:heading>
        <flux:text class="mt-2 mb-4">
            @if($this->heatmapData['hasAssignedStaff'])
                Assigned staff are shown first, followed by all other staff members.
            @else
                All staff members are shown alphabetically.
            @endif
        </flux:text>
        @include('components.heatmap-table', $this->heatmapData)
    </div>
@endif
