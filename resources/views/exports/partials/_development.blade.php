@if($project->development)
<section class="card">
    <h2 class="card-title">6. Development</h2>
    <div class="grid grid-2">
        @if($project->development->deliverable_title)
        <div class="field">
            <p class="field-label">Deliverable Title</p>
            <p class="field-value">{{ $project->development->deliverable_title }}</p>
        </div>
        @endif
        @if($project->development->leadDeveloper)
        <div class="field">
            <p class="field-label">Lead Developer</p>
            <p class="field-value">{{ $project->development->leadDeveloper->full_name }}</p>
        </div>
        @endif
        @if($project->development->development_team && count($project->development->development_team) > 0)
        @php
            $devTeamNames = \App\Models\User::whereIn('id', $project->development->development_team)->get()->map(fn($u) => $u->full_name)->toArray();
        @endphp
        <div class="field">
            <p class="field-label">Development Team</p>
            <p class="field-value">{{ implode(', ', $devTeamNames) }}</p>
        </div>
        @endif
        @if($project->development->status)
        <div class="field">
            <p class="field-label">Status</p>
            <p class="field-value">{{ $project->development->status }}</p>
        </div>
        @endif
        <div class="field">
            <p class="field-label">Technical Approach</p>
            <p class="field-value {{ !$project->development->technical_approach ? 'empty' : '' }}">
                {{ $project->development->technical_approach ?? 'Not specified' }}
            </p>
        </div>
        @if($project->development->development_notes)
        <div class="field">
            <p class="field-label">Development Notes</p>
            <p class="field-value">{{ $project->development->development_notes }}</p>
        </div>
        @endif
        @if($project->development->repository_link)
        <div class="field">
            <p class="field-label">Repository</p>
            <p class="field-value">{{ $project->development->repository_link }}</p>
        </div>
        @endif
        @if($project->development->start_date)
        <div class="field">
            <p class="field-label">Start Date</p>
            <p class="field-value">{{ $project->development->start_date->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($project->development->completion_date)
        <div class="field">
            <p class="field-label">Completion Date</p>
            <p class="field-value">{{ $project->development->completion_date->format('d/m/Y') }}</p>
        </div>
        @endif
        @if($project->development->code_review_notes)
        <div class="field">
            <p class="field-label">Code Review Notes</p>
            <p class="field-value">{{ $project->development->code_review_notes }}</p>
        </div>
        @endif
    </div>
</section>
@endif
