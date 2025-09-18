<?php

namespace Database\Seeders;

use App\Enums\SkillLevel;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    // Stop the model events from firing to prevent side effects so we can seed exactly what we want
    use WithoutModelEvents;

    public function run(): void
    {
        // Create some default roles
        $roles = [
            [
                'name' => 'CoSE',
                'description' => 'College of Science and Engineering team',
                'is_active' => true,
            ],
            [
                'name' => 'Ai Research Role',
                'description' => 'Ai Research Role team',
                'is_active' => true,
            ],
            [
                'name' => 'Chemistry',
                'description' => 'Chemistry Department Staff',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Create additional random roles for testing
        Role::factory(10)->create();

        // Create skills
        $skills = $this->getSkills();
        foreach ($skills as $skillData) {
            Skill::factory()->create([
                'name' => $skillData['name'],
                'description' => $skillData['description'],
            ]);
        }

        User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        User::factory()->staff()->create([
            'username' => 'staff2x',
            'email' => 'staff2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        $users = User::factory()->count(10)->create();

        // Assign random roles to users
        $allRoles = Role::all();
        foreach ($users as $user) {
            // Assign 1-3 random roles to each user
            $randomRoles = $allRoles->random(rand(1, min(3, $allRoles->count())));
            $user->roles()->attach($randomRoles->pluck('id'));
        }

        // Assign skills to users (except admin)
        $allSkills = Skill::all();
        $skillLevels = SkillLevel::cases();

        foreach ($users as $user) {
            // Assign 3-8 random skills to each user with random skill levels
            $randomSkills = $allSkills->random(rand(3, min(8, $allSkills->count())));
            foreach ($randomSkills as $skill) {
                $skillLevel = $skillLevels[array_rand($skillLevels)];
                $user->skills()->attach($skill->id, ['skill_level' => $skillLevel->value]);
            }
        }

        foreach (User::all() as $user) {
            foreach (range(1, 10) as $i) {
                $project = Project::factory()->create([
                    'user_id' => $user->id,
                    'updated_at' => now()->subDays(rand(1, 100)),
                ]);
                $stage = rand(0, count(config('projman.subforms')) - 1);
                foreach (config('projman.subforms') as $index => $formName) {
                    // Create base data for each form type
                    $formData = [
                        'project_id' => $project->id,
                        'created_at' => $index <= $stage ? now()->subDays(rand(1, 100)) : now(),
                    ];

                    // Add user ID fields based on form type
                    if ($formName === 'App\Models\Feasibility') {
                        $formData['assessed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Scoping') {
                        $formData['assessed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\DetailedDesign') {
                        $formData['designed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Development') {
                        $formData['lead_developer'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Testing') {
                        $formData['test_lead'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Deployed') {
                        $formData['deployed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Scheduling') {
                        $formData['assigned_to'] = $users->random()->id;
                    }

                    $formName::factory()->create($formData);
                }

                foreach (range(1, rand(1, 100)) as $j) {
                    ProjectHistory::factory()->create([
                        'project_id' => $project->id,
                        'user_id' => $users->random()->id,
                        'created_at' => now()->subDays(rand(1, 100)),
                    ]);
                }
            }
        }
    }

    public function getSkills()
    {
        return [
            // Windows
            ['name' => 'Windows - Desktop Support', 'description' => 'Providing technical support and troubleshooting for Windows desktop environments.'],
            ['name' => 'Windows - Active Directory / SCCM', 'description' => 'Managing users, devices, and policies with Active Directory and SCCM.'],
            ['name' => 'Windows - Deployment & Imaging', 'description' => 'Setting up and deploying Windows systems using imaging tools.'],
            ['name' => 'Windows - Group Policy Management', 'description' => 'Creating and applying group policies to manage Windows environments.'],
            ['name' => 'Windows - PowerShell Scripting', 'description' => 'Automating administrative tasks using PowerShell scripts.'],
            ['name' => 'Windows Server Administration', 'description' => 'Installing, configuring, and maintaining Windows Server environments.'],

            // macOS
            ['name' => 'macOS - Desktop Support', 'description' => 'Supporting and troubleshooting Apple macOS devices for end users.'],
            ['name' => 'macOS - Deployment & Management', 'description' => 'Deploying and managing macOS systems across an organisation.'],
            ['name' => 'macOS - Jamf / MDM Administration', 'description' => 'Managing Apple devices using Jamf or other Mobile Device Management tools.'],
            ['name' => 'macOS - Integration with AD', 'description' => 'Connecting macOS systems to Active Directory for authentication and access.'],

            // Linux
            ['name' => 'Linux - Desktop Support', 'description' => 'Providing user support for Linux desktop environments.'],
            ['name' => 'Linux - Server Administration (Rocky / Ubuntu)', 'description' => 'Managing and maintaining Linux servers such as Rocky Linux and Ubuntu.'],
            ['name' => 'Linux - Shell Scripting & Automation', 'description' => 'Automating tasks and workflows with Linux shell scripts.'],
            ['name' => 'Linux - System Monitoring & Logging', 'description' => 'Monitoring system performance and analysing log files on Linux systems.'],
            ['name' => 'Linux - Security Hardening', 'description' => 'Securing Linux systems by applying best practices and configurations.'],

            // Directory & Identity
            ['name' => 'Identity Management - Active Directory', 'description' => 'Managing identities, permissions, and authentication using AD.'],
            ['name' => 'Identity Management - LDAP', 'description' => 'Administering and integrating Lightweight Directory Access Protocol systems.'],
            ['name' => 'Single Sign-On (SSO) / Shibboleth / SAML', 'description' => 'Implementing and supporting SSO solutions for unified authentication.'],
            ['name' => 'Multi-Factor Authentication (MFA) Integration', 'description' => 'Enhancing security with multi-factor authentication solutions.'],
            ['name' => 'Federated Identity Management', 'description' => 'Linking identity systems across organisations for secure access.'],

            // Infrastructure
            ['name' => 'Virtualisation - VMware / Hyper-V', 'description' => 'Creating and managing virtual machines with VMware or Hyper-V.'],
            ['name' => 'Containerisation - Docker / Podman', 'description' => 'Deploying and managing containerised applications using Docker or Podman.'],
            ['name' => 'Networking - Routing & Switching', 'description' => 'Configuring and supporting network routing and switching devices.'],
            ['name' => 'Networking - Firewalls & Security', 'description' => 'Protecting networks using firewalls and security best practices.'],
            ['name' => 'Networking - DNS / DHCP / IP Management', 'description' => 'Administering DNS, DHCP, and IP address management services.'],
            ['name' => 'Storage Administration (NAS / SAN)', 'description' => 'Managing and maintaining NAS and SAN storage systems.'],
            ['name' => 'Backup & Disaster Recovery', 'description' => 'Implementing backup strategies and disaster recovery plans.'],
            ['name' => 'Cloud Services - Microsoft 365 / Azure', 'description' => 'Supporting and administering Microsoft 365 and Azure environments.'],
            ['name' => 'Cloud Services - AWS / Google Cloud', 'description' => 'Managing cloud infrastructure and services on AWS or Google Cloud.'],

            // HPC & Research Computing
            ['name' => 'High Performance Computing (HPC) Cluster Support', 'description' => 'Supporting and maintaining HPC clusters for research workloads.'],
            ['name' => 'GPU Computing Support', 'description' => 'Configuring and supporting GPU-based high performance computing.'],
            ['name' => 'Slurm / Job Scheduling', 'description' => 'Managing and monitoring workloads with Slurm and other schedulers.'],
            ['name' => 'Linux Cluster Administration', 'description' => 'Administering and supporting Linux-based computing clusters.'],
            ['name' => 'Parallel Computing (MPI / OpenMP)', 'description' => 'Supporting parallel computing frameworks such as MPI and OpenMP.'],
            ['name' => 'Research Data Management', 'description' => 'Organising and securing research data in line with best practices.'],

            // Support & Front-line
            ['name' => 'First-line Helpdesk Support', 'description' => 'Providing initial IT support and issue resolution for users.'],
            ['name' => 'Second-line Technical Support', 'description' => 'Handling escalated technical issues requiring in-depth knowledge.'],
            ['name' => 'Hardware Procurement & Installation', 'description' => 'Sourcing, installing, and maintaining IT hardware.'],
            ['name' => 'AV & Classroom Technology Support', 'description' => 'Supporting audio-visual and classroom technology systems.'],
            ['name' => 'Printing & Device Management', 'description' => 'Managing printers and peripheral devices within an organisation.'],
            ['name' => 'End-User Training & Documentation', 'description' => 'Creating documentation and training resources for end users.'],
            ['name' => 'IT Asset Management', 'description' => 'Tracking and managing the lifecycle of IT assets.'],
            ['name' => 'Software Licensing & Compliance', 'description' => 'Ensuring software usage complies with licensing agreements.'],

            // Development
            ['name' => 'Web Development - Laravel / PHP', 'description' => 'Building and maintaining web applications with Laravel and PHP.'],
            ['name' => 'Web Development - Python / Django / Flask', 'description' => 'Developing web applications using Python frameworks like Django and Flask.'],
            ['name' => 'Web Development - JavaScript / Node.js', 'description' => 'Creating dynamic web applications with JavaScript and Node.js.'],
            ['name' => 'Front-end Development - Vue.js / React', 'description' => 'Designing and implementing front-end interfaces with Vue.js or React.'],
            ['name' => 'Database Administration (MySQL / PostgreSQL)', 'description' => 'Administering and optimising MySQL and PostgreSQL databases.'],
            ['name' => 'Database - MS SQL Server / Oracle', 'description' => 'Managing enterprise databases such as MS SQL Server and Oracle.'],
            ['name' => 'API Development & Integration', 'description' => 'Building and integrating APIs for data and service connectivity.'],
            ['name' => 'Version Control - Git / GitHub / GitLab', 'description' => 'Using Git-based tools for source control and collaboration.'],
            ['name' => 'Continuous Integration / Deployment (CI/CD)', 'description' => 'Automating builds, testing, and deployments through CI/CD pipelines.'],
            ['name' => 'Testing & Quality Assurance', 'description' => 'Ensuring software quality through structured testing processes.'],

            // Security & Policy
            ['name' => 'Information Security & Data Protection', 'description' => 'Implementing security measures to safeguard data and systems.'],
            ['name' => 'Penetration Testing & Vulnerability Management', 'description' => 'Identifying and mitigating security vulnerabilities through testing.'],
            ['name' => 'GDPR Compliance & Governance', 'description' => 'Ensuring IT practices comply with GDPR and data governance standards.'],
            ['name' => 'Incident Response & Monitoring', 'description' => 'Responding to and monitoring IT security incidents effectively.'],
            ['name' => 'IT Policy Development & Documentation', 'description' => 'Creating and maintaining IT policies and procedures.'],

            // Softer / non-technical
            ['name' => 'Project Management', 'description' => 'Planning and managing IT projects from start to finish.'],
            ['name' => 'Agile / Scrum Methodologies', 'description' => 'Applying Agile and Scrum principles to software and project delivery.'],
            ['name' => 'ITIL / Service Management', 'description' => 'Implementing IT service management practices based on ITIL.'],
            ['name' => 'Communication & Teamwork', 'description' => 'Working collaboratively and communicating effectively in teams.'],
            ['name' => 'User Requirements Gathering', 'description' => 'Collecting and analysing user requirements for IT solutions.'],
            ['name' => 'Technical Documentation & Writing', 'description' => 'Producing clear and accurate technical documentation.'],
            ['name' => 'Training & User Support', 'description' => 'Delivering user training and providing ongoing support.'],
            ['name' => 'Change Management', 'description' => 'Managing organisational change related to IT systems and processes.'],
            ['name' => 'Stakeholder Engagement', 'description' => 'Building and maintaining strong relationships with stakeholders.'],
            ['name' => 'Problem Solving & Troubleshooting', 'description' => 'Identifying, analysing, and resolving technical problems effectively.'],
        ];
    }
}
