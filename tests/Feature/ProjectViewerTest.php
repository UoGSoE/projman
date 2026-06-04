<?php

use App\Enums\ChangeBoardOutcome;
use App\Enums\EffortScale;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

describe('Project show page ideation summary', function () {
    it('surfaces the ideation details so the owner can see their submission at a glance', function () {
        $owner = User::factory()->requester()->create([
            'forenames' => 'Anon',
            'surname' => 'Person',
        ]);

        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Replace timetable system',
        ]);

        $project->ideation->update([
            'school_group' => 'MVLS',
            'objective' => 'Replace the ageing timetable system.',
            'business_case' => 'The current system is unsupported.',
            'benefits' => 'Faster timetable changes for staff and students.',
            'deadline' => '2027-03-31',
            'strategic_initiative' => 'Inspire',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Replace timetable system')
            ->assertSee('Anon Person')
            ->assertSee('MVLS')
            ->assertSee('31/03/2027')
            ->assertSee('Replace the ageing timetable system.')
            ->assertSee('The current system is unsupported.')
            ->assertSee('Faster timetable changes for staff and students.')
            ->assertDontSee('Inspire');
    });
});

describe('Project show page feasibility summary', function () {
    it('surfaces feasibility details once the feasibility form has been assessed', function () {
        $owner = User::factory()->requester()->create();
        $assessor = User::factory()->staff()->create([
            'forenames' => 'Fiona',
            'surname' => 'Feasibilitor',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->feasibility->update([
            'assessed_by' => $assessor->id,
            'date_assessed' => '2026-02-10',
            'deadlines_achievable' => true,
            'alternative_proposal' => 'Could outsource to a third-party vendor instead.',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Feasibility')
            ->assertSee('Fiona Feasibilitor')
            ->assertSee('10/02/2026')
            ->assertSeeInOrder([
                'Deadline Achievable', 'Yes',
                'Alternative Proposal', 'Could outsource to a third-party vendor instead.',
                'Feasibility', 'Approved',
            ]);
    });
});

describe('Project show page scoping summary', function () {
    it('surfaces scoping details once the scoping form has been assessed', function () {
        $owner = User::factory()->requester()->create();
        $assessor = User::factory()->staff()->create([
            'forenames' => 'Sam',
            'surname' => 'Scoper',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->scoping->update([
            'assessed_by' => $assessor->id,
            'estimated_effort' => EffortScale::MEDIUM,
            'in_scope' => 'Replacing the timetable UI.',
            'out_of_scope' => 'Migrating historical timetable data.',
            'assumptions' => 'Vendor will provide an API by Q3.',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Scoping')
            ->assertSee('Sam Scoper')
            ->assertSee('Medium')
            ->assertSee('Replacing the timetable UI.')
            ->assertSee('Migrating historical timetable data.')
            ->assertSee('Vendor will provide an API by Q3.');
    });
});

describe('Project show page scheduling summary', function () {
    it('surfaces scheduling details once the scheduling form has been filled in', function () {
        $owner = User::factory()->requester()->create();
        $techLead = User::factory()->staff()->create([
            'forenames' => 'Terry',
            'surname' => 'Technicallead',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->scheduling->update([
            'estimated_start_date' => '2026-06-01',
            'estimated_completion_date' => '2026-09-30',
            'change_board_date' => '2026-05-20',
            'change_board_outcome' => ChangeBoardOutcome::APPROVED,
            'technical_lead_id' => $techLead->id,
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Scheduling')
            ->assertSee('Terry Technicallead')
            ->assertSee('01/06/2026')
            ->assertSee('30/09/2026')
            ->assertSee('20/05/2026')
            ->assertSee('Approved');
    });

    it('shows a Not Required change board outcome with a readable label', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->scheduling->update([
            'estimated_start_date' => '2026-06-01',
            'change_board_outcome' => ChangeBoardOutcome::NOT_REQUIRED,
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Not Required')
            ->assertDontSee('not_required');
    });
});

describe('Project show page detailed design summary', function () {
    it('surfaces detailed design details with an approval badge for each governance area', function () {
        $owner = User::factory()->requester()->create();
        $designer = User::factory()->staff()->create([
            'forenames' => 'Dana',
            'surname' => 'Designer',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->detailedDesign->update([
            'designed_by' => $designer->id,
            'approval_delivery' => 'approved',
            'approval_operations' => 'pending',
            'approval_resilience' => 'rejected',
            'approval_agb' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Detailed Design')
            ->assertSee('Dana Designer')
            ->assertSee('Approvals')
            ->assertSeeInOrder([
                'Delivery', 'Approved',
                'Operations', 'Pending',
                'Resilience', 'Rejected',
                'Architecture Governance Board', 'Pending',
            ]);
    });

    it('renders the architecture governance board "Not Required" state without an underscore', function () {
        $owner = User::factory()->requester()->create();
        $designer = User::factory()->staff()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->detailedDesign->update([
            'designed_by' => $designer->id,
            'approval_agb' => 'not_required',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Not Required')
            ->assertDontSee('not_required');
    });
});

describe('Project show page development summary', function () {
    it('surfaces development details once a lead developer has been recorded', function () {
        $owner = User::factory()->requester()->create();
        $leadDev = User::factory()->staff()->create([
            'forenames' => 'Lee',
            'surname' => 'Developer',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->development->update([
            'lead_developer' => $leadDev->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Development')
            ->assertSee('Lee Developer')
            ->assertSee('In progress');
    });
});

describe('Project show page build summary', function () {
    it('surfaces the build requirements once they have been recorded', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->build->update([
            'build_requirements' => 'Provision two new VMs in the EuroDC region.',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Build')
            ->assertSee('Provision two new VMs in the EuroDC region.');
    });
});

describe('Project show page testing summary', function () {
    it('surfaces testing details with an approval badge for each sign-off', function () {
        $owner = User::factory()->requester()->create();
        $testLead = User::factory()->staff()->create([
            'forenames' => 'Tara',
            'surname' => 'Tester',
        ]);
        $uatTester = User::factory()->requester()->create([
            'forenames' => 'Ulrich',
            'surname' => 'UATer',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->testing->update([
            'test_lead' => $testLead->id,
            'uat_tester_id' => $uatTester->id,
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'pending',
            'testing_lead_sign_off' => 'rejected',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Testing')
            ->assertSee('Tara Tester')
            ->assertSee('Ulrich UATer')
            ->assertSee('Approvals')
            ->assertSeeInOrder([
                'Testing Sign-off', 'Approved',
                'User Acceptance', 'Pending',
                'Testing Lead', 'Rejected',
                'Service Delivery', 'Approved',
                'Service Resilience', 'Pending',
            ]);
    });
});

describe('Project show page deployment summary', function () {
    it('surfaces deployment details with an approval badge for each governance area', function () {
        $owner = User::factory()->requester()->create();
        $deployLead = User::factory()->staff()->create([
            'forenames' => 'Dani',
            'surname' => 'Deploylead',
        ]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->deployed->update([
            'deployment_lead_id' => $deployLead->id,
            'service_resilience_approval' => 'approved',
            'service_operations_approval' => 'pending',
            'service_delivery_approval' => 'rejected',
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee('Deployment')
            ->assertSee('Dani Deploylead')
            ->assertSee('Approvals')
            ->assertSeeInOrder([
                'Service Resilience', 'Approved',
                'Service Operations', 'Pending',
                'Service Delivery', 'Rejected',
            ]);
    });
});
