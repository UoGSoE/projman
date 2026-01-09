@if($project->scoping)
<section class="card">
    <h2 class="card-title">3. Scoping</h2>
    <div class="grid grid-2">
        @if($project->scoping->deliverable_title)
        <div class="field">
            <p class="field-label">Deliverable Title</p>
            <p class="field-value">{{ $project->scoping->deliverable_title }}</p>
        </div>
        @endif
        <div class="field">
            <p class="field-label">Estimated Effort</p>
            <p class="field-value {{ !$project->scoping->estimated_effort ? 'empty' : '' }}">
                {{ $project->scoping->estimated_effort?->label() ?? 'Not assessed' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">In Scope</p>
            <p class="field-value {{ !$project->scoping->in_scope ? 'empty' : '' }}">
                {{ $project->scoping->in_scope ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Out of Scope</p>
            <p class="field-value {{ !$project->scoping->out_of_scope ? 'empty' : '' }}">
                {{ $project->scoping->out_of_scope ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Assumptions</p>
            <p class="field-value {{ !$project->scoping->assumptions ? 'empty' : '' }}">
                {{ $project->scoping->assumptions ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Requires Software Development</p>
            <p class="field-value">
                @if($project->scoping->requires_software_dev === null)
                    <span class="empty">Not specified</span>
                @else
                    {{ $project->scoping->requires_software_dev ? 'Yes' : 'No' }}
                @endif
            </p>
        </div>
        @if($project->scoping->skills_required && count($project->scoping->skills_required) > 0)
        @php
            $skillNames = \App\Models\Skill::whereIn('id', $project->scoping->skills_required)->pluck('name')->toArray();
        @endphp
        <div class="field">
            <p class="field-label">Skills Required</p>
            <p class="field-value">{{ implode(', ', $skillNames) }}</p>
        </div>
        @endif
        @if($project->scoping->dcgg_status)
        <div class="field">
            <p class="field-label">DCGG Status</p>
            <p class="field-value">{{ $project->scoping->dcgg_status }}</p>
        </div>
        @endif
    </div>
</section>
@endif
