<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Import Skills from Spreadsheet</flux:heading>
        <flux:link :href="route('skills.manage')" variant="ghost" icon="arrow-left">Back to Skills</flux:link>
    </div>

    @if ($step === 'upload')
        <flux:card class="mt-6">
            <flux:heading size="lg">Upload SFIA Spreadsheet</flux:heading>
            <flux:text class="mt-2">
                Upload the IT Training Modeller spreadsheet (.xlsx) to import skills and staff competency levels.
                The spreadsheet should contain a "Baseline" sheet with skill definitions and a "Master" sheet with staff assessments.
            </flux:text>

            <div class="mt-6 space-y-4">
                <flux:file-upload wire:model="spreadsheet" accept=".xlsx" label="Spreadsheet file">
                    <flux:file-upload.dropzone
                        heading="Drop your spreadsheet here or click to browse"
                        text="Only .xlsx files up to 10MB"
                    />
                </flux:file-upload>

                @if ($spreadsheet)
                    <flux:file-item
                        heading="{{ $spreadsheet->getClientOriginalName() }}"
                        size="{{ $spreadsheet->getSize() }}"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="$set('spreadsheet', null)" />
                        </x-slot>
                    </flux:file-item>
                @endif

                <flux:button variant="primary" wire:click="parseSpreadsheet" wire:loading.attr="disabled" wire:target="parseSpreadsheet">
                    Parse Spreadsheet
                </flux:button>
            </div>
        </flux:card>

    @elseif ($step === 'preview')
        <div class="mt-6 space-y-6">
            {{-- Skills summary --}}
            <flux:card>
                <flux:heading size="lg">Skills Summary</flux:heading>
                <flux:text class="mt-1">{{ count($parsedSkills) }} skills will be imported or updated.</flux:text>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($this->skillsByCategory as $category => $count)
                        <flux:badge color="blue" wire:key="skill-category-{{ $category }}">{{ $category }}: {{ $count }}</flux:badge>
                    @endforeach
                </div>
            </flux:card>

            {{-- Auto-matched users --}}
            @if (count($autoMatched) > 0)
                <flux:card>
                    <flux:heading size="lg">Matched Users ({{ count($autoMatched) }})</flux:heading>
                    <flux:text class="mt-1">These spreadsheet names were automatically matched to existing users by surname.</flux:text>

                    <flux:table class="mt-4">
                        <flux:table.columns>
                            <flux:table.column>Spreadsheet Name</flux:table.column>
                            <flux:table.column>Matched User</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($autoMatched as $spreadsheetName => $match)
                                <flux:table.row wire:key="matched-{{ $loop->index }}">
                                    <flux:table.cell>{{ $spreadsheetName }}</flux:table.cell>
                                    <flux:table.cell>{{ $match['userName'] }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="green">Matched</flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @endif

            {{-- Unmatched users --}}
            @if (count($unmatched) > 0)
                <flux:card>
                    <flux:heading size="lg">Unmatched Users ({{ count($unmatched) }})</flux:heading>
                    <flux:text class="mt-1">These names couldn't be automatically matched. Please select the correct user or mark as "Not in system".</flux:text>

                    <flux:table class="mt-4">
                        <flux:table.columns>
                            <flux:table.column>Spreadsheet Name</flux:table.column>
                            <flux:table.column>Match to User</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($unmatched as $spreadsheetName)
                                <flux:table.row wire:key="unmatched-{{ $loop->index }}">
                                    <flux:table.cell>{{ $spreadsheetName }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:select wire:model.live="userSelections.{{ $spreadsheetName }}" size="sm">
                                            <flux:select.option value="not_in_system">Not in system</flux:select.option>
                                            @foreach ($staffUsers as $user)
                                                <flux:select.option :value="$user->id" wire:key="option-{{ $spreadsheetName }}-{{ $user->id }}">{{ $user->full_name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @endif

            {{-- Not in system callout --}}
            @if ($this->notInSystemCount > 0)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Users not in system ({{ $this->notInSystemCount }})</flux:callout.heading>
                    <flux:callout.text>
                        The following people will be skipped during import. You'll need to create their accounts first if you want to import their skills:
                        {{ $this->notInSystemNames }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Skipped staff callout --}}
            @if ($this->skippedStaffCount > 0)
                <flux:callout variant="secondary" icon="information-circle">
                    <flux:callout.heading>Staff with no assessment data ({{ $this->skippedStaffCount }})</flux:callout.heading>
                    <flux:callout.text>
                        These staff members had no competency assessments in the spreadsheet (all marked as not yet assessed) and will be skipped:
                        {{ implode(', ', $skippedStaff) }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="confirmImport">Confirm Import</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Start Over</flux:button>
            </div>
        </div>

    @elseif ($step === 'complete')
        <flux:card class="mt-6">
            <flux:heading size="lg">Import Complete</flux:heading>

            <div class="mt-4 space-y-2">
                <flux:text>
                    <flux:text variant="strong" inline>{{ $importSummary['skills_imported'] ?? 0 }}</flux:text> skills imported or updated.
                </flux:text>
                <flux:text>
                    <flux:text variant="strong" inline>{{ $importSummary['users_updated'] ?? 0 }}</flux:text> users updated with skill levels.
                </flux:text>
                <flux:text>
                    <flux:text variant="strong" inline>{{ $importSummary['users_skipped'] ?? 0 }}</flux:text> users skipped.
                </flux:text>
            </div>

            <div class="mt-6 flex gap-3">
                <flux:button variant="primary" :href="route('skills.manage')">View Skills Manager</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Import Another</flux:button>
            </div>
        </flux:card>
    @endif
</div>
