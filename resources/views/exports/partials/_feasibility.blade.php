@if($project->feasibility)
<section class="card">
    <h2 class="card-title">2. Feasibility</h2>
    <div class="grid grid-2">
        <div class="field">
            <p class="field-label">Technical Credence</p>
            <p class="field-value {{ !$project->feasibility->technical_credence ? 'empty' : '' }}">
                {{ $project->feasibility->technical_credence ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Cost/Benefit Case</p>
            <p class="field-value {{ !$project->feasibility->cost_benefit_case ? 'empty' : '' }}">
                {{ $project->feasibility->cost_benefit_case ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Dependencies & Prerequisites</p>
            <p class="field-value {{ !$project->feasibility->dependencies_prerequisites ? 'empty' : '' }}">
                {{ $project->feasibility->dependencies_prerequisites ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Deadlines Achievable</p>
            <p class="field-value">
                @if($project->feasibility->deadlines_achievable === null)
                    <span class="empty">Not assessed</span>
                @else
                    {{ $project->feasibility->deadlines_achievable ? 'Yes' : 'No' }}
                @endif
            </p>
        </div>
        @if($project->feasibility->existing_solution_status)
        <div class="field">
            <p class="field-label">Existing Solution</p>
            <p class="field-value">{{ $project->feasibility->existing_solution_status }}</p>
            @if($project->feasibility->existing_solution_status_notes)
                <p class="field-value" style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">
                    {{ $project->feasibility->existing_solution_status_notes }}
                </p>
            @endif
        </div>
        @endif
        @if($project->feasibility->off_the_shelf_solution_status)
        <div class="field">
            <p class="field-label">Off-the-Shelf Solution</p>
            <p class="field-value">{{ $project->feasibility->off_the_shelf_solution_status }}</p>
            @if($project->feasibility->off_the_shelf_solution_status_notes)
                <p class="field-value" style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">
                    {{ $project->feasibility->off_the_shelf_solution_status_notes }}
                </p>
            @endif
        </div>
        @endif
        <div class="field">
            <p class="field-label">Approval Status</p>
            <p class="field-value">
                @if($project->feasibility->approval_status === 'approved')
                    <span class="badge badge-green">Approved</span>
                @elseif($project->feasibility->approval_status === 'rejected')
                    <span class="badge badge-red">Rejected</span>
                @else
                    <span class="badge badge-amber">Pending</span>
                @endif
            </p>
        </div>
        @if($project->feasibility->assessor)
        <div class="field">
            <p class="field-label">Assessed By</p>
            <p class="field-value">{{ $project->feasibility->assessor->full_name }}</p>
            @if($project->feasibility->date_assessed)
                <p class="field-value" style="font-size: 0.875rem; color: #6b7280;">
                    {{ $project->feasibility->date_assessed->format('d/m/Y') }}
                </p>
            @endif
        </div>
        @endif
        @if($project->feasibility->reject_reason)
        <div class="field">
            <p class="field-label">Rejection Reason</p>
            <p class="field-value">{{ $project->feasibility->reject_reason }}</p>
        </div>
        @endif
    </div>
</section>
@endif
