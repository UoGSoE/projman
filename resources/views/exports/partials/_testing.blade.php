@if($project->testing)
<section class="card">
    <h2 class="card-title">8. Testing</h2>
    <div class="grid grid-2">
        @if($project->testing->deliverable_title)
        <div class="field">
            <p class="field-label">Deliverable Title</p>
            <p class="field-value">{{ $project->testing->deliverable_title }}</p>
        </div>
        @endif
        @if($project->testing->testLead)
        <div class="field">
            <p class="field-label">Test Lead</p>
            <p class="field-value">{{ $project->testing->testLead->full_name }}</p>
        </div>
        @endif
        @if($project->testing->uatTester)
        <div class="field">
            <p class="field-label">UAT Tester</p>
            <p class="field-value">{{ $project->testing->uatTester->full_name }}</p>
        </div>
        @endif
        @if($project->testing->service_function)
        <div class="field">
            <p class="field-label">Service Function</p>
            <p class="field-value">{{ $project->testing->service_function }}</p>
        </div>
        @endif
        <div class="field">
            <p class="field-label">Functional Tests</p>
            <p class="field-value {{ !$project->testing->functional_tests ? 'empty' : '' }}">
                {{ $project->testing->functional_tests ?? 'Not specified' }}
            </p>
        </div>
        <div class="field">
            <p class="field-label">Non-Functional Tests</p>
            <p class="field-value {{ !$project->testing->non_functional_tests ? 'empty' : '' }}">
                {{ $project->testing->non_functional_tests ?? 'Not specified' }}
            </p>
        </div>
        @if($project->testing->test_repository)
        <div class="field">
            <p class="field-label">Test Repository</p>
            <p class="field-value">{{ $project->testing->test_repository }}</p>
        </div>
        @endif
    </div>

    {{-- Sign-offs --}}
    @if($project->testing->testing_sign_off || $project->testing->user_acceptance || $project->testing->testing_lead_sign_off || $project->testing->service_delivery_sign_off || $project->testing->service_resilience_sign_off)
    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        <p class="field-label" style="margin-bottom: 0.5rem;">Sign-offs</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @if($project->testing->testing_sign_off)
                <span class="badge badge-{{ $project->testing->testing_sign_off === 'approved' ? 'green' : 'amber' }}">
                    Testing: {{ ucfirst($project->testing->testing_sign_off) }}
                </span>
            @endif
            @if($project->testing->user_acceptance)
                <span class="badge badge-{{ $project->testing->user_acceptance === 'approved' ? 'green' : 'amber' }}">
                    UAT: {{ ucfirst($project->testing->user_acceptance) }}
                </span>
            @endif
            @if($project->testing->testing_lead_sign_off)
                <span class="badge badge-{{ $project->testing->testing_lead_sign_off === 'approved' ? 'green' : 'amber' }}">
                    Lead: {{ ucfirst($project->testing->testing_lead_sign_off) }}
                </span>
            @endif
            @if($project->testing->service_delivery_sign_off)
                <span class="badge badge-{{ $project->testing->service_delivery_sign_off === 'approved' ? 'green' : 'amber' }}">
                    Service Delivery: {{ ucfirst($project->testing->service_delivery_sign_off) }}
                </span>
            @endif
            @if($project->testing->service_resilience_sign_off)
                <span class="badge badge-{{ $project->testing->service_resilience_sign_off === 'approved' ? 'green' : 'amber' }}">
                    Resilience: {{ ucfirst($project->testing->service_resilience_sign_off) }}
                </span>
            @endif
        </div>
    </div>
    @endif
</section>
@endif
