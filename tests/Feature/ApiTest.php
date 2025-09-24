<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->apiUser = User::factory()->create();
});

describe('Workpackage API authentication', function () {
    it('rejects unauthenticated listing requests', function () {
        $this->getJson('/api/workpackages')->assertUnauthorized();
    });

    it('rejects unauthenticated deployment updates', function () {
        $project = Project::factory()->create();

        $this->postJson("/api/workpackages/{$project->id}/deployment", [])->assertUnauthorized();
    });
});

describe('Workpackage API responses', function () {
    it('returns a paginated list with deployment metadata', function () {
        Sanctum::actingAs($this->apiUser);

        $project = Project::factory()
            ->state(['title' => 'Workflow Optimisation', 'status' => ProjectStatus::DEVELOPMENT])
            ->create();

        $project->load('deployed');

        $project->deployed->update([
            'deployed_by' => $this->apiUser->id,
            'environment' => 'staging',
            'status' => 'pending',
            'version' => '1.2.3',
        ]);

        $response = $this->getJson('/api/workpackages');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->where('data.0.id', $project->id)
                ->where('data.0.title', 'Workflow Optimisation')
                ->where('data.0.status', ProjectStatus::DEVELOPMENT->value)
                ->where('data.0.deployed.environment', 'staging')
                ->where('data.0.deployed.status', 'pending')
                ->where('data.0.deployed.version', '1.2.3')
                ->etc()
            );
    });
});

describe('Workpackage deployment updates', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->apiUser);
    });

    it('updates deployment details when payload is valid', function () {
        $deployer = User::factory()->create();

        $project = Project::factory()->create();
        $project->load('deployed');

        $project->deployed->update([
            'deployed_by' => $deployer->id,
        ]);

        $payload = [
            'deployed_by' => $deployer->id,
            'environment' => 'production',
            'status' => 'deployed',
            'deployment_date' => now()->toDateString(),
            'version' => '2025.01.01',
            'production_url' => 'https://example.org',
            'deployment_notes' => 'Deployed via GitLab',
            'rollback_plan' => 'Re-run previous tag',
            'monitoring_notes' => 'Check grafana dashboards',
            'deployment_sign_off' => 'approved',
            'operations_sign_off' => 'pending',
            'user_acceptance_sign_off' => 'pending',
            'service_delivery_sign_off' => 'pending',
            'change_advisory_sign_off' => 'approved',
        ];

        $response = $this->postJson("/api/workpackages/{$project->id}/deployment", $payload);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', 'Deployment details updated.')
                ->where('deployed.version', '2025.01.01')
                ->where('deployed.environment', 'production')
                ->etc()
            );

        $this->assertDatabaseHas('deployeds', [
            'project_id' => $project->id,
            'deployed_by' => $deployer->id,
            'environment' => 'production',
            'status' => 'deployed',
            'version' => '2025.01.01',
            'deployment_notes' => 'Deployed via GitLab',
        ]);
    });

    it('validates incoming payloads', function () {
        $project = Project::factory()->create();
        $project->load('deployed');

        $response = $this->postJson("/api/workpackages/{$project->id}/deployment", [
            'environment' => 'production',
            'status' => 'deployed',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'deployment_date',
            ]);
    });

    it('allows updating selected fields alongside the date', function () {
        $project = Project::factory()->create();
        $project->load('deployed');

        $project->deployed->update([
            'environment' => 'staging',
            'status' => 'pending',
            'monitoring_notes' => 'Initial notes',
        ]);

        $payload = [
            'deployment_date' => now()->toDateString(),
            'environment' => 'production',
        ];

        $response = $this->postJson("/api/workpackages/{$project->id}/deployment", $payload);

        $response->assertOk();

        $project->deployed->refresh();

        expect($project->deployed->environment)->toBe('production');
        expect($project->deployed->status)->toBe('pending');
        expect($project->deployed->monitoring_notes)->toBe('Initial notes');
    });

    it('allows updating only the deployment date', function () {
        $project = Project::factory()->create();
        $project->load('deployed');

        $project->deployed->update([
            'environment' => 'staging',
            'status' => 'pending',
            'version' => '1.0.0',
            'deployment_sign_off' => 'pending',
        ]);

        $newDate = now()->addDay()->toDateString();

        $response = $this->postJson("/api/workpackages/{$project->id}/deployment", [
            'deployment_date' => $newDate,
        ]);

        $response->assertOk();

        $project->deployed->refresh();

        expect($project->deployed->deployment_date->toDateString())->toBe($newDate);
        expect($project->deployed->environment)->toBe('staging');
        expect($project->deployed->version)->toBe('1.0.0');
    });

    it('returns 404 when workpackage is missing', function () {
        $this->postJson('/api/workpackages/999/deployment', [])->assertNotFound();
    });
});
