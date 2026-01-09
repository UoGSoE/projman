@if($project->build)
<section class="card">
    <h2 class="card-title">7. Build</h2>
    <div class="grid grid-2">
        <div class="field">
            <p class="field-label">Build Requirements</p>
            <p class="field-value {{ !$project->build->build_requirements ? 'empty' : '' }}">
                {{ $project->build->build_requirements ?? 'Not specified' }}
            </p>
        </div>
    </div>
</section>
@endif
