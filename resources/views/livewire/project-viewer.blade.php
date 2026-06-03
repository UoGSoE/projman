<div>
    <div class="flex flex-col md:flex-row gap-4 justify-between md:items-center">
        <div class="flex flex-wrap items-center gap-3">
            <flux:badge color="{{ $project->status->colour() }}" icon="adjustments-horizontal" size="lg">
                {{ $project->status->label() }}
            </flux:badge>
            <flux:heading size="xl" level="1">{{ $project->title }}</flux:heading>
        </div>
        <div class="flex gap-2">
            @admin
            <flux:button icon="arrow-down-tray" href="{{ route('project.export', $project) }}" target="_blank">Export</flux:button>
            @endadmin
            @can('update', $project)
                <flux:button icon="pencil" variant="primary" href="{{ route('project.edit', $project) }}">Edit</flux:button>
            @endcan
        </div>
    </div>

    <flux:callout icon="user" class="mt-6">
        <flux:callout.heading>
            Requested by {{ $project->user->full_name }}
            @if ($project->ideation?->school_group)
                &middot; {{ $project->ideation->school_group }}
            @endif
            @if ($project->ideation?->deadline)
                &middot; Due {{ $project->ideation->deadline->format('d/m/Y') }}
            @endif
        </flux:callout.heading>
    </flux:callout>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @if ($project->ideation?->objective)
            <flux:card>
                <flux:heading size="lg">Objective</flux:heading>
                <flux:separator variant="subtle" class="mt-1 mb-3" />
                <flux:text class="whitespace-pre-line">{{ $project->ideation->objective }}</flux:text>
            </flux:card>
        @endif

        @if ($project->ideation?->business_case)
            <flux:card>
                <flux:heading size="lg">Business Case</flux:heading>
                <flux:separator variant="subtle" class="mt-1 mb-3" />
                <flux:text class="whitespace-pre-line">{{ $project->ideation->business_case }}</flux:text>
            </flux:card>
        @endif

        @if ($project->ideation?->benefits)
            <flux:card>
                <flux:heading size="lg">Expected Benefits</flux:heading>
                <flux:separator variant="subtle" class="mt-1 mb-3" />
                <flux:text class="whitespace-pre-line">{{ $project->ideation->benefits }}</flux:text>
            </flux:card>
        @endif
    </div>

    @if ($project->feasibility?->assessed_by)
        <div class="mt-8">
            <flux:heading size="lg">Feasibility</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>
                Assessed by {{ $project->feasibility->assessor?->full_name }}
                @if ($project->feasibility->date_assessed)
                    on {{ $project->feasibility->date_assessed->format('d/m/Y') }}
                @endif
            </flux:text>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @if (! is_null($project->feasibility->deadlines_achievable))
                    <x-approval-badge
                        label="Deadline Achievable"
                        :status="$project->feasibility->deadlines_achievable ? 'approved' : 'rejected'"
                        :display="$project->feasibility->deadlines_achievable ? 'Yes' : 'No'"
                    />
                @endif

                @if ($project->feasibility->alternative_proposal)
                    <flux:card>
                        <flux:heading size="sm">Alternative Proposal</flux:heading>
                        <flux:separator variant="subtle" class="mt-1 mb-2" />
                        <flux:text class="whitespace-pre-line">{{ $project->feasibility->alternative_proposal }}</flux:text>
                    </flux:card>
                @endif

                <x-approval-badge label="Feasibility" :status="$project->feasibility->approval_status ?? 'pending'" />
            </div>
        </div>
    @endif

    @if ($project->scoping?->assessed_by)
        <div class="mt-8">
            <flux:heading size="lg">Scoping</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>
                Assessed by {{ $project->scoping->assessor?->full_name }}
                @if ($project->scoping->estimated_effort)
                    &middot; Estimated effort: <strong>{{ $project->scoping->estimated_effort->label() }}</strong>
                @endif
            </flux:text>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                @if ($project->scoping->in_scope)
                    <flux:card>
                        <flux:heading size="sm">In Scope</flux:heading>
                        <flux:separator variant="subtle" class="mt-1 mb-2" />
                        <flux:text class="whitespace-pre-line">{{ $project->scoping->in_scope }}</flux:text>
                    </flux:card>
                @endif

                @if ($project->scoping->out_of_scope)
                    <flux:card>
                        <flux:heading size="sm">Out of Scope</flux:heading>
                        <flux:separator variant="subtle" class="mt-1 mb-2" />
                        <flux:text class="whitespace-pre-line">{{ $project->scoping->out_of_scope }}</flux:text>
                    </flux:card>
                @endif

                @if ($project->scoping->assumptions)
                    <flux:card>
                        <flux:heading size="sm">Assumptions</flux:heading>
                        <flux:separator variant="subtle" class="mt-1 mb-2" />
                        <flux:text class="whitespace-pre-line">{{ $project->scoping->assumptions }}</flux:text>
                    </flux:card>
                @endif
            </div>
        </div>
    @endif

    @if ($project->scheduling?->estimated_start_date)
        <div class="mt-8">
            <flux:heading size="lg">Scheduling</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:card>
                    <flux:heading size="sm">Estimated Start</flux:heading>
                    <flux:text class="mt-1">{{ $project->scheduling->estimated_start_date->format('d/m/Y') }}</flux:text>
                </flux:card>

                @if ($project->scheduling->estimated_completion_date)
                    <flux:card>
                        <flux:heading size="sm">Estimated Completion</flux:heading>
                        <flux:text class="mt-1">{{ $project->scheduling->estimated_completion_date->format('d/m/Y') }}</flux:text>
                    </flux:card>
                @endif

                @if ($project->scheduling->change_board_date)
                    <flux:card>
                        <flux:heading size="sm">Change Board</flux:heading>
                        <flux:text class="mt-1">{{ $project->scheduling->change_board_date->format('d/m/Y') }}</flux:text>
                    </flux:card>
                @endif
            </div>

            @if ($project->scheduling->technicalLead)
                <flux:text class="mt-4">
                    Technical lead: <strong>{{ $project->scheduling->technicalLead->full_name }}</strong>
                </flux:text>
            @endif

            @if ($project->scheduling->change_board_outcome)
                <flux:heading size="sm" class="mt-4">Outcome</flux:heading>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <x-approval-badge label="Change Board" :status="$project->scheduling->change_board_outcome->value" :display="$project->scheduling->change_board_outcome->label()" />
                </div>
            @endif
        </div>
    @endif

    @if ($project->detailedDesign?->designed_by)
        <div class="mt-8">
            <flux:heading size="lg">Detailed Design</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>Designed by <strong>{{ $project->detailedDesign->designer?->full_name }}</strong></flux:text>

            <flux:heading size="sm" class="mt-4">Approvals</flux:heading>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <x-approval-badge label="Delivery" :status="$project->detailedDesign->approval_delivery ?? 'pending'" />
                <x-approval-badge label="Operations" :status="$project->detailedDesign->approval_operations ?? 'pending'" />
                <x-approval-badge label="Resilience" :status="$project->detailedDesign->approval_resilience ?? 'pending'" />
                <x-approval-badge label="Architecture Governance Board" :status="$project->detailedDesign->approval_agb ?? 'pending'" :display="\App\Enums\AgbApproval::tryFrom($project->detailedDesign->approval_agb ?? 'pending')?->label()" />
            </div>
        </div>
    @endif

    @if ($project->development?->lead_developer)
        <div class="mt-8">
            <flux:heading size="lg">Development</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>Lead developer: <strong>{{ $project->development->leadDeveloper?->full_name }}</strong></flux:text>
            @if ($project->development->status)
                <flux:text class="mt-2">Status: <strong>{{ ucfirst(str_replace('_', ' ', $project->development->status)) }}</strong></flux:text>
            @endif
        </div>
    @endif

    @if ($project->build?->build_requirements)
        <div class="mt-8">
            <flux:heading size="lg">Build</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text class="whitespace-pre-line">{{ $project->build->build_requirements }}</flux:text>
        </div>
    @endif

    @if ($project->testing?->test_lead)
        <div class="mt-8">
            <flux:heading size="lg">Testing</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>Test lead: <strong>{{ $project->testing->testLead?->full_name }}</strong></flux:text>
            @if ($project->testing->uatTester)
                <flux:text class="mt-1">UAT tester: <strong>{{ $project->testing->uatTester->full_name }}</strong></flux:text>
            @endif

            <flux:heading size="sm" class="mt-4">Approvals</flux:heading>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <x-approval-badge label="Testing Sign-off" :status="$project->testing->testing_sign_off ?? 'pending'" />
                <x-approval-badge label="User Acceptance" :status="$project->testing->user_acceptance ?? 'pending'" />
                <x-approval-badge label="Testing Lead" :status="$project->testing->testing_lead_sign_off ?? 'pending'" />
                <x-approval-badge label="Service Delivery" :status="$project->testing->service_delivery_sign_off ?? 'pending'" />
                <x-approval-badge label="Service Resilience" :status="$project->testing->service_resilience_sign_off ?? 'pending'" />
            </div>
        </div>
    @endif

    @if ($project->deployed?->deployment_lead_id)
        <div class="mt-8">
            <flux:heading size="lg">Deployment</flux:heading>
            <flux:separator variant="subtle" class="mt-1 mb-3" />

            <flux:text>Deployment lead: <strong>{{ $project->deployed->deploymentLead?->full_name }}</strong></flux:text>

            <flux:heading size="sm" class="mt-4">Approvals</flux:heading>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <x-approval-badge label="Service Resilience" :status="$project->deployed->service_resilience_approval ?? 'pending'" />
                <x-approval-badge label="Service Operations" :status="$project->deployed->service_operations_approval ?? 'pending'" />
                <x-approval-badge label="Service Delivery" :status="$project->deployed->service_delivery_approval ?? 'pending'" />
            </div>
        </div>
    @endif

    <flux:separator variant="subtle" class="mt-8" />

    <flux:heading class="mt-6">Work Package History</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Description</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($project->history as $history)
                <flux:table.row>
                    <flux:table.cell>{{ $history->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $history->user_name }}</flux:table.cell>
                    <flux:table.cell>{{ $history->description }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
