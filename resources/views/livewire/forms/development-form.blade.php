<form wire:submit="save('development')" class="space-y-6">
    {{-- Explanatory Note When Not Software Development --}}
    @if (!$scopingForm->requiresSoftwareDev)
        <flux:callout variant="info">
            This work package does not require custom software development. Fields are disabled.
        </flux:callout>
    @endif

    <flux:fieldset :disabled="!$scopingForm->requiresSoftwareDev" data-test="development-form-fieldset" class="space-y-6">
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

    <div class="flex items-center gap-2">
        <flux:button wire:click="saveAndAdvance('development')" variant="primary" icon:trailing="arrow-right">
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

    </flux:fieldset>

</form>

<flux:separator class="my-6" />

<x-notes-list
    :noteable="$project->development"
    formPrefix="developmentForm"
    addNoteMethod="addDevelopmentNote"
/>
