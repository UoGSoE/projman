<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roles() as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }

    /**
     * @return array<int, array{name: string, description: string, is_active: bool}>
     */
    private function roles(): array
    {
        return [
            ['name' => 'Admin', 'description' => 'System administration and oversight access', 'is_active' => true],
            ['name' => 'Project Manager', 'description' => 'Coordinates the project lifecycle and stakeholder communication', 'is_active' => true],
            ['name' => 'Work Package Assessor', 'description' => 'Assesses work packages and provides feedback', 'is_active' => true],
            ['name' => 'Service Lead', 'description' => 'Leads service delivery and acceptance processes', 'is_active' => true],
            ['name' => 'Ideation Manager', 'description' => 'Responsible for managing the ideation phase of projects', 'is_active' => true],
            ['name' => 'Feasibility Manager', 'description' => 'Oversees technical and business feasibility assessments', 'is_active' => true],
            ['name' => 'Scoping Manager', 'description' => 'Defines scope, deliverables, and boundaries for projects', 'is_active' => true],
            ['name' => 'Scheduling Manager', 'description' => 'Handles project timelines and scheduling activities', 'is_active' => true],
            ['name' => 'Detailed Design Manager', 'description' => 'Supervises detailed design documentation and architecture', 'is_active' => true],
            ['name' => 'Development Manager', 'description' => 'Leads the overall software or product development process', 'is_active' => true],
            ['name' => 'Testing Manager', 'description' => 'Ensures end-to-end testing and quality assurance', 'is_active' => true],
            ['name' => 'Build Manager', 'description' => 'Manages build activities and outputs', 'is_active' => true],
            ['name' => 'Deployment Manager', 'description' => 'Manages releases, deployments, and environment changes', 'is_active' => true],
            ['name' => 'Completed Manager', 'description' => 'Manages the completion of projects', 'is_active' => true],
            ['name' => 'Cancelled Manager', 'description' => 'Manages the cancellation of projects', 'is_active' => true],
            ['name' => 'Change Manager', 'description' => 'Manages organisational and technical changes within projects', 'is_active' => true],
        ];
    }
}
