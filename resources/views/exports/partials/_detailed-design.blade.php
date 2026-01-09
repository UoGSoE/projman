@if($project->detailedDesign)
<section class="card">
    <h2 class="card-title">5. Detailed Design</h2>
    <div class="grid grid-2">
        @if($project->detailedDesign->deliverable_title)
        <div class="field">
            <p class="field-label">Deliverable Title</p>
            <p class="field-value">{{ $project->detailedDesign->deliverable_title }}</p>
        </div>
        @endif
        @if($project->detailedDesign->designed_by)
        <div class="field">
            <p class="field-label">Designed By</p>
            <p class="field-value">{{ $project->detailedDesign->designed_by }}</p>
        </div>
        @endif
        @if($project->detailedDesign->service_function)
        <div class="field">
            <p class="field-label">Service Function</p>
            <p class="field-value">{{ $project->detailedDesign->service_function }}</p>
        </div>
        @endif
        <div class="field">
            <p class="field-label">Functional Requirements</p>
            <p class="field-value {{ !$project->detailedDesign->functional_requirements ? 'empty' : '' }}">
                {{ $project->detailedDesign->functional_requirements ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Non-Functional Requirements</p>
            <p class="field-value {{ !$project->detailedDesign->non_functional_requirements ? 'empty' : '' }}">
                {{ $project->detailedDesign->non_functional_requirements ?? 'Not specified' }}
            </p>
        </div>
        @if($project->detailedDesign->hld_design_link)
        <div class="field">
            <p class="field-label">HLD Design Link</p>
            <p class="field-value">{{ $project->detailedDesign->hld_design_link }}</p>
        </div>
        @endif
        @if($project->detailedDesign->approval_delivery || $project->detailedDesign->approval_operations || $project->detailedDesign->approval_resilience || $project->detailedDesign->approval_change_board)
        <div class="field">
            <p class="field-label">Approvals</p>
            <div class="field-value">
                @if($project->detailedDesign->approval_delivery)
                    <span class="badge badge-{{ $project->detailedDesign->approval_delivery === 'approved' ? 'green' : 'amber' }}">
                        Delivery: {{ ucfirst($project->detailedDesign->approval_delivery) }}
                    </span>
                @endif
                @if($project->detailedDesign->approval_operations)
                    <span class="badge badge-{{ $project->detailedDesign->approval_operations === 'approved' ? 'green' : 'amber' }}">
                        Operations: {{ ucfirst($project->detailedDesign->approval_operations) }}
                    </span>
                @endif
                @if($project->detailedDesign->approval_resilience)
                    <span class="badge badge-{{ $project->detailedDesign->approval_resilience === 'approved' ? 'green' : 'amber' }}">
                        Resilience: {{ ucfirst($project->detailedDesign->approval_resilience) }}
                    </span>
                @endif
                @if($project->detailedDesign->approval_change_board)
                    <span class="badge badge-{{ $project->detailedDesign->approval_change_board === 'approved' ? 'green' : 'amber' }}">
                        Change Board: {{ ucfirst($project->detailedDesign->approval_change_board) }}
                    </span>
                @endif
            </div>
        </div>
        @endif
    </div>
</section>
@endif
