@if($project->scheduling)
<section class="card">
    <h2 class="card-title">4. Scheduling</h2>
    <div class="grid grid-2">
        @if($project->scheduling->priority)
        <div class="field">
            <p class="field-label">Priority</p>
            <p class="field-value">{{ $project->scheduling->priority->label() }}</p>
        </div>
        @endif
        @if($project->scheduling->assignedUser)
        <div class="field">
            <p class="field-label">Assigned To</p>
            <p class="field-value">{{ $project->scheduling->assignedUser->full_name }}</p>
        </div>
        @endif
        @if($project->scheduling->technicalLead)
        <div class="field">
            <p class="field-label">Technical Lead</p>
            <p class="field-value">{{ $project->scheduling->technicalLead->full_name }}</p>
        </div>
        @endif
        @if($project->scheduling->changeChampion)
        <div class="field">
            <p class="field-label">Change Champion</p>
            <p class="field-value">{{ $project->scheduling->changeChampion->full_name }}</p>
        </div>
        @endif
        @if($project->scheduling->cose_it_staff && count($project->scheduling->cose_it_staff) > 0)
        @php
            $coseStaffNames = \App\Models\User::whereIn('id', $project->scheduling->cose_it_staff)->get()->map(fn($u) => $u->full_name)->toArray();
        @endphp
        <div class="field">
            <p class="field-label">CoSE IT Staff</p>
            <p class="field-value">{{ implode(', ', $coseStaffNames) }}</p>
        </div>
        @endif
        @if($project->scheduling->estimated_start_date)
        <div class="field">
            <p class="field-label">Estimated Start Date</p>
            <p class="field-value">{{ $project->scheduling->estimated_start_date->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($project->scheduling->estimated_completion_date)
        <div class="field">
            <p class="field-label">Estimated Completion Date</p>
            <p class="field-value">{{ $project->scheduling->estimated_completion_date->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($project->scheduling->change_board_date)
        <div class="field">
            <p class="field-label">Change Board Date</p>
            <p class="field-value">{{ $project->scheduling->change_board_date->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($project->scheduling->change_board_outcome)
        <div class="field">
            <p class="field-label">Change Board Outcome</p>
            <p class="field-value">{{ $project->scheduling->change_board_outcome->label() }}</p>
        </div>
        @endif
    </div>
</section>
@endif
