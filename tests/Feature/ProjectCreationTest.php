<?php

use App\Livewire\ProjectCreator;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;



uses(RefreshDatabase::class);

describe('Project Creation', function () {
    beforeEach(function () {
        // Create a test admin user
        $this->user = User::factory()->create(['is_admin' => true]);

        // Create test users with names that match what the tests expect
        $this->testAssessor = User::factory()->create([
            'forenames' => 'Test',
            'surname' => 'Assessor',
            'username' => 'test.assessor',
            'email' => 'test.assessor@example.ac.uk',
        ]);

        $this->testDesigner = User::factory()->create([
            'forenames' => 'Test',
            'surname' => 'Designer',
            'username' => 'test.designer',
            'email' => 'test.designer@example.ac.uk',
        ]);

        $this->testLead = User::factory()->create([
            'forenames' => 'Test',
            'surname' => 'Lead',
            'username' => 'test.lead',
            'email' => 'test.lead@example.ac.uk',
        ]);

        $this->testDeployer = User::factory()->create([
            'forenames' => 'Test',
            'surname' => 'Deployer',
            'username' => 'test.deployer',
            'email' => 'test.deployer@example.ac.uk',
        ]);
    });

    describe('Ideation Form', function () {
        it('can create an ideation form with valid data', function () {
            $tomorrow = now()->addDay()->format('Y-m-d');

            livewire(ProjectCreator::class)
                ->set('ideationForm.name', 'Test Project')
                ->set('ideationForm.schoolGroup', 'Test School')
                ->set('ideationForm.deliverableTitle', 'Test Deliverable')
                ->set('ideationForm.objective', 'Test Objective')
                ->set('ideationForm.businessCase', 'Test Business Case')
                ->set('ideationForm.benefits', 'Test Benefits')
                ->set('ideationForm.deadline', $tomorrow)
                ->set('ideationForm.initiative', 'thing')
                ->call('save', 'ideation')
                ->assertHasNoErrors();
        });

        it('validates required fields for ideation form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'ideation')
                ->assertHasErrors([
                    'ideationForm.name' => 'required',
                    'ideationForm.schoolGroup' => 'required',
                    'ideationForm.deliverableTitle' => 'required',
                    'ideationForm.objective' => 'required',
                    'ideationForm.businessCase' => 'required',
                    'ideationForm.benefits' => 'required',
                    'ideationForm.deadline' => 'required',
                    'ideationForm.initiative' => 'required',
                ]);
        });

        it('validates deadline must be after today', function () {
            $yesterday = now()->subDay()->format('Y-m-d');

            livewire(ProjectCreator::class)
                ->set('ideationForm.deadline', $yesterday)
                ->call('save', 'ideation')
                ->assertHasErrors(['ideationForm.deadline' => 'after']);
        });
    });

    describe('Feasibility Form', function () {
        it('can create a feasibility form with valid data', function () {
            $tomorrow = now()->addDay()->format('Y-m-d');

            livewire(ProjectCreator::class)
                ->set('feasibilityForm.deliverableTitle', 'Test Deliverable')
                ->set('feasibilityForm.technicalCredence', 'Test Technical Credence')
                ->set('feasibilityForm.costBenefitCase', 'Test Cost Benefit Case')
                ->set('feasibilityForm.dependenciesPrerequisites', 'Test Dependencies')
                ->set('feasibilityForm.deadlinesAchievable', 'yes')
                ->set('feasibilityForm.alternativeProposal', 'Test Alternative')
                ->set('feasibilityForm.assessedBy', $this->testAssessor->id)
                ->set('feasibilityForm.dateAssessed', $tomorrow)
                ->call('save', 'feasibility')
                ->assertHasNoErrors();
        });

        it('validates required fields for feasibility form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'feasibility')
                ->assertHasErrors([
                    'feasibilityForm.deliverableTitle' => 'required',
                    'feasibilityForm.technicalCredence' => 'required',
                    'feasibilityForm.costBenefitCase' => 'required',
                    'feasibilityForm.dependenciesPrerequisites' => 'required',
                    'feasibilityForm.deadlinesAchievable' => 'required',
                    'feasibilityForm.alternativeProposal' => 'required',
                    'feasibilityForm.assessedBy' => 'required',
                    'feasibilityForm.dateAssessed' => 'required',
                ]);
        });
    });

    describe('Scoping Form', function () {
        it('can create a scoping form with valid data', function () {
            livewire(ProjectCreator::class)
                ->set('scopingForm.deliverableTitle', 'Test Deliverable')
                ->set('scopingForm.assessedBy', $this->testAssessor->id)
                ->set('scopingForm.estimatedEffort', 'Test Effort')
                ->set('scopingForm.inScope', 'Test In Scope')
                ->set('scopingForm.outOfScope', 'Test Out of Scope')
                ->set('scopingForm.assumptions', 'Test Assumptions')
                ->set('scopingForm.skillsRequired', 'one')
                ->call('save', 'scoping')
                ->assertHasNoErrors();
        });

        it('validates required fields for scoping form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'scoping')
                ->assertHasErrors([
                    'scopingForm.deliverableTitle' => 'required',
                    'scopingForm.assessedBy' => 'required',
                    'scopingForm.estimatedEffort' => 'required',
                    'scopingForm.inScope' => 'required',
                    'scopingForm.outOfScope' => 'required',
                    'scopingForm.assumptions' => 'required',
                    'scopingForm.skillsRequired' => 'required',
                ]);
        });
    });

    describe('Scheduling Form', function () {
        it('can create a scheduling form with valid data', function () {
            $tomorrow = now()->addDay()->format('Y-m-d');
            $dayAfterTomorrow = now()->addDays(2)->format('Y-m-d');

            livewire(ProjectCreator::class)
                ->set('schedulingForm.deliverableTitle', 'Test Deliverable')
                ->set('schedulingForm.keySkills', 'Test Key Skills')
                ->set('schedulingForm.coseItStaff', 'Test Staff')
                ->set('schedulingForm.estimatedStartDate', $tomorrow)
                ->set('schedulingForm.estimatedCompletionDate', $dayAfterTomorrow)
                ->set('schedulingForm.changeBoardDate', $tomorrow)
                ->set('schedulingForm.assignedTo', $this->testLead->id)
                ->set('schedulingForm.priority', 'high')
                ->set('schedulingForm.teamAssignment', '1')
                ->call('save', 'scheduling')
                ->assertHasNoErrors();
        });

        it('validates required fields for scheduling form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'scheduling')
                ->assertHasErrors([
                    'schedulingForm.deliverableTitle' => 'required',
                    'schedulingForm.keySkills' => 'required',
                    'schedulingForm.estimatedStartDate' => 'required',
                    'schedulingForm.estimatedCompletionDate' => 'required',
                                    'schedulingForm.changeBoardDate' => 'required',
                'schedulingForm.assignedTo' => 'required',
                'schedulingForm.priority' => 'required',
                'schedulingForm.teamAssignment' => 'required',
            ]);
        });

        it('validates completion date must be after start date', function () {
            $tomorrow = now()->addDay()->format('Y-m-d');
            $today = now()->format('Y-m-d');

            livewire(ProjectCreator::class)
                ->set('schedulingForm.estimatedStartDate', $tomorrow)
                ->set('schedulingForm.estimatedCompletionDate', $today)
                ->call('save', 'scheduling')
                ->assertHasErrors(['schedulingForm.estimatedCompletionDate' => 'after']);
        });
    });

    describe('Detailed Design Form', function () {
        it('can create a detailed design form with valid data', function () {
            livewire(ProjectCreator::class)
                ->set('detailedDesignForm.deliverableTitle', 'Test Deliverable')
                ->set('detailedDesignForm.designedBy', $this->testDesigner->id)
                ->set('detailedDesignForm.serviceFunction', 'Test Service')
                ->set('detailedDesignForm.functionalRequirements', 'Test Functional Requirements')
                ->set('detailedDesignForm.nonFunctionalRequirements', 'Test Non-Functional Requirements')
                ->set('detailedDesignForm.hldDesignLink', 'https://example.com/design')
                ->set('detailedDesignForm.approvalDelivery', 'Test Approval')
                ->set('detailedDesignForm.approvalOperations', 'Test Operations')
                ->set('detailedDesignForm.approvalResilience', 'Test Resilience')
                ->set('detailedDesignForm.approvalChangeBoard', 'Test Change Board')
                ->call('save', 'detailed-design')
                ->assertHasNoErrors();
        });

        it('validates required fields for detailed design form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'detailed-design')
                ->assertHasErrors([
                    'detailedDesignForm.deliverableTitle' => 'required',
                    'detailedDesignForm.designedBy' => 'required',
                    'detailedDesignForm.serviceFunction' => 'required',
                    'detailedDesignForm.functionalRequirements' => 'required',
                    'detailedDesignForm.nonFunctionalRequirements' => 'required',
                    'detailedDesignForm.hldDesignLink' => 'required',
                    'detailedDesignForm.approvalDelivery' => 'required',
                    'detailedDesignForm.approvalOperations' => 'required',
                    'detailedDesignForm.approvalResilience' => 'required',
                    'detailedDesignForm.approvalChangeBoard' => 'required',
                ]);
        });

        it('validates URL format for design link', function () {
            livewire(ProjectCreator::class)
                ->set('detailedDesignForm.hldDesignLink', 'not-a-url')
                ->call('save', 'detailed-design')
                ->assertHasErrors(['detailedDesignForm.hldDesignLink' => 'url']);
        });
    });

    describe('Development Form', function () {
        it('can create a development form with valid data', function () {
            $tomorrow = now()->addDay()->format('Y-m-d');
            $dayAfterTomorrow = now()->addDays(2)->format('Y-m-d');

                    livewire(ProjectCreator::class)
            ->set('developmentForm.deliverableTitle', 'Test Deliverable')
            ->set('developmentForm.leadDeveloper', $this->testLead->id)
            ->set('developmentForm.developmentTeam', 'Test Team')
            ->set('developmentForm.technicalApproach', 'Test Technical Approach')
            ->set('developmentForm.developmentNotes', 'Test Development Notes')
            ->set('developmentForm.repositoryLink', 'https://github.com/test/repo')
            ->set('developmentForm.status', 'in_progress')
            ->set('developmentForm.startDate', $tomorrow)
            ->set('developmentForm.completionDate', $dayAfterTomorrow)
            ->set('developmentForm.codeReviewNotes', 'Test Code Review Notes')
            ->call('save', 'development')
            ->assertHasNoErrors();
        });

        it('validates required fields for development form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'development')
                            ->assertHasErrors([
                'developmentForm.deliverableTitle' => 'required',
                'developmentForm.leadDeveloper' => 'required',
                'developmentForm.developmentTeam' => 'required',
                'developmentForm.technicalApproach' => 'required',
                'developmentForm.developmentNotes' => 'required',
                'developmentForm.repositoryLink' => 'required',
                'developmentForm.status' => 'required',
                'developmentForm.startDate' => 'required',
                'developmentForm.completionDate' => 'required',
            ]);
        });

        it('validates URL format for repository URL', function () {
            livewire(ProjectCreator::class)
                ->set('developmentForm.repositoryLink', 'not-a-url')
                ->call('save', 'development')
                ->assertHasErrors(['developmentForm.repositoryLink' => 'url']);
        });
    });

    describe('Testing Form', function () {
        it('can create a testing form with valid data', function () {
            livewire(ProjectCreator::class)
                ->set('testingForm.deliverableTitle', 'Test Deliverable')
                ->set('testingForm.testLead', $this->testLead->id)
                ->set('testingForm.serviceFunction', 'Test Service')
                ->set('testingForm.functionalTestingTitle', 'Functional Testing')
                ->set('testingForm.functionalTests', 'Test functional tests')
                ->set('testingForm.nonFunctionalTestingTitle', 'Non-Functional Testing')
                ->set('testingForm.nonFunctionalTests', 'Test non-functional tests')
                ->set('testingForm.testRepository', 'https://github.com/test/tests')
                ->set('testingForm.testingSignOff', 'Test Sign Off')
                ->set('testingForm.userAcceptance', 'Test User Acceptance')
                ->set('testingForm.testingLeadSignOff', 'Test Lead Sign Off')
                ->set('testingForm.serviceDeliverySignOff', 'Test Service Delivery')
                ->set('testingForm.serviceResilienceSignOff', 'Test Service Resilience Sign Off')
                ->call('save', 'testing')
                ->assertHasNoErrors();
        });

        it('validates required fields for testing form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'testing')
                ->assertHasErrors([
                    'testingForm.deliverableTitle' => 'required',
                    'testingForm.testLead' => 'required',
                    'testingForm.serviceFunction' => 'required',
                    'testingForm.functionalTestingTitle' => 'required',
                    'testingForm.functionalTests' => 'required',
                    'testingForm.nonFunctionalTestingTitle' => 'required',
                    'testingForm.nonFunctionalTests' => 'required',
                    'testingForm.testRepository' => 'required',
                    'testingForm.testingSignOff' => 'required',
                    'testingForm.userAcceptance' => 'required',
                    'testingForm.testingLeadSignOff' => 'required',
                    'testingForm.serviceDeliverySignOff' => 'required',
                    'testingForm.serviceResilienceSignOff' => 'required',
                ]);
        });

        it('validates URL format for test repository', function () {
            livewire(ProjectCreator::class)
                ->set('testingForm.testRepository', 'not-a-url')
                ->call('save', 'testing')
                ->assertHasErrors(['testingForm.testRepository' => 'url']);
        });
    });

    describe('Deployed Form', function () {
        it('can create a deployed form with valid data', function () {
            $today = now()->format('Y-m-d');

                    livewire(ProjectCreator::class)
            ->set('deployedForm.deliverableTitle', 'Test Deliverable')
            ->set('deployedForm.deployedBy', $this->testDeployer->id)
            ->set('deployedForm.environment', 'production')
            ->set('deployedForm.status', 'deployed')
            ->set('deployedForm.deploymentDate', $today)
            ->set('deployedForm.version', '1.0.0')
            ->set('deployedForm.productionUrl', 'https://example.com/app')
            ->set('deployedForm.deploymentNotes', 'Test deployment notes')
            ->set('deployedForm.rollbackPlan', 'Test rollback plan')
            ->set('deployedForm.monitoringNotes', 'Test monitoring notes')
            ->set('deployedForm.deploymentSignOff', 'Test Deployment Sign Off')
            ->set('deployedForm.operationsSignOff', 'Test Operations Sign Off')
            ->set('deployedForm.userAcceptanceSignOff', 'Test User Acceptance Sign Off')
            ->set('deployedForm.serviceDeliverySignOff', 'Test Service Delivery Sign Off')
            ->set('deployedForm.changeAdvisorySignOff', 'Test Change Advisory Sign Off')
            ->call('save', 'deployed')
            ->assertHasNoErrors();
        });

        it('validates required fields for deployed form', function () {
            livewire(ProjectCreator::class)
                ->call('save', 'deployed')
                            ->assertHasErrors([
                'deployedForm.deliverableTitle' => 'required',
                'deployedForm.deployedBy' => 'required',
                'deployedForm.environment' => 'required',
                'deployedForm.status' => 'required',
                'deployedForm.deploymentDate' => 'required',
                'deployedForm.version' => 'required',
                'deployedForm.productionUrl' => 'required',
                'deployedForm.deploymentSignOff' => 'required',
                'deployedForm.operationsSignOff' => 'required',
                'deployedForm.userAcceptanceSignOff' => 'required',
                'deployedForm.serviceDeliverySignOff' => 'required',
                'deployedForm.changeAdvisorySignOff' => 'required',
            ]);
        });

        it('validates URL format for deployment URL', function () {
            livewire(ProjectCreator::class)
                ->set('deployedForm.productionUrl', 'not-a-url')
                ->call('save', 'deployed')
                ->assertHasErrors(['deployedForm.productionUrl' => 'url']);
        });
    });

    describe('Form Field Length Validation', function () {
        it('validates maximum length for string fields', function () {
            $longString = str_repeat('a', 256); // 256 characters

            livewire(ProjectCreator::class)
                ->set('ideationForm.name', $longString)
                ->call('save', 'ideation')
                ->assertHasErrors(['ideationForm.name' => 'max']);
        });

        it('validates maximum length for textarea fields', function () {
            $longString = str_repeat('a', 2049); // 2049 characters

            livewire(ProjectCreator::class)
                ->set('ideationForm.businessCase', $longString)
                ->call('save', 'ideation')
                ->assertHasErrors(['ideationForm.businessCase' => 'max']);
        });
    });
});
