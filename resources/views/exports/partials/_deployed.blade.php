@if($project->deployed)
<section class="card">
    <h2 class="card-title">9. Deployed</h2>
    <div class="grid grid-2">
        @if($project->deployed->deploymentLead)
        <div class="field">
            <p class="field-label">Deployment Lead</p>
            <p class="field-value">{{ $project->deployed->deploymentLead->full_name }}</p>
        </div>
        @endif
        @if($project->deployed->service_function)
        <div class="field">
            <p class="field-label">Service Function</p>
            <p class="field-value">{{ $project->deployed->service_function }}</p>
        </div>
        @endif
        @if($project->deployed->functional_tests)
        <div class="field">
            <p class="field-label">Functional Tests</p>
            <p class="field-value">{{ $project->deployed->functional_tests }}</p>
        </div>
        @endif
        @if($project->deployed->non_functional_tests)
        <div class="field">
            <p class="field-label">Non-Functional Tests</p>
            <p class="field-value">{{ $project->deployed->non_functional_tests }}</p>
        </div>
        @endif
        @if($project->deployed->bau_operational_wiki)
        <div class="field">
            <p class="field-label">BAU Operational Wiki</p>
            <p class="field-value">{{ $project->deployed->bau_operational_wiki }}</p>
        </div>
        @endif
        @if($project->deployed->deployment_approved_at)
        <div class="field">
            <p class="field-label">Deployment Approved</p>
            <p class="field-value">{{ $project->deployed->deployment_approved_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif
        @if($project->deployed->service_accepted_at)
        <div class="field">
            <p class="field-label">Service Accepted</p>
            <p class="field-value">{{ $project->deployed->service_accepted_at->format('d/m/Y H:i') }}</p>
        </div>
        @endif
    </div>

    {{-- Service Approvals --}}
    @if($project->deployed->service_resilience_approval || $project->deployed->service_operations_approval || $project->deployed->service_delivery_approval)
    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        <p class="field-label" style="margin-bottom: 0.5rem;">Service Approvals</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @if($project->deployed->service_resilience_approval)
                <span class="badge badge-{{ $project->deployed->service_resilience_approval === 'approved' ? 'green' : 'amber' }}">
                    Resilience: {{ ucfirst($project->deployed->service_resilience_approval) }}
                </span>
            @endif
            @if($project->deployed->service_operations_approval)
                <span class="badge badge-{{ $project->deployed->service_operations_approval === 'approved' ? 'green' : 'amber' }}">
                    Operations: {{ ucfirst($project->deployed->service_operations_approval) }}
                </span>
            @endif
            @if($project->deployed->service_delivery_approval)
                <span class="badge badge-{{ $project->deployed->service_delivery_approval === 'approved' ? 'green' : 'amber' }}">
                    Delivery: {{ ucfirst($project->deployed->service_delivery_approval) }}
                </span>
            @endif
        </div>
    </div>
    @endif
</section>
@endif
