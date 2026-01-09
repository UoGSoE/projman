@if($project->ideation)
<section class="card">
    <h2 class="card-title">1. Ideation</h2>
    <div class="grid grid-2">
        <div class="field">
            <p class="field-label">Objective</p>
            <p class="field-value {{ !$project->ideation->objective ? 'empty' : '' }}">
                {{ $project->ideation->objective ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Business Case</p>
            <p class="field-value {{ !$project->ideation->business_case ? 'empty' : '' }}">
                {{ $project->ideation->business_case ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Benefits</p>
            <p class="field-value {{ !$project->ideation->benefits ? 'empty' : '' }}">
                {{ $project->ideation->benefits ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Strategic Initiative</p>
            <p class="field-value {{ !$project->ideation->strategic_initiative ? 'empty' : '' }}">
                {{ $project->ideation->strategic_initiative ?? 'Not specified' }}
            </p>
        </div>
        @if($project->ideation->deadline)
        <div class="field">
            <p class="field-label">Deadline</p>
            <p class="field-value">{{ $project->ideation->deadline->format('d/m/Y') }}</p>
        </div>
        @endif
    </div>
</section>
@endif
