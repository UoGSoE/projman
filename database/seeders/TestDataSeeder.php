<?php

namespace Database\Seeders;

use App\Enums\Busyness;
use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Role;
use App\Models\Scheduling;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private ?Collection $skillCache = null;

    private ?Collection $skillNameCache = null;

    public function run(): void
    {
        $this->seedRoles();
        $this->seedSkills();

        $this->skillCache = null;
        $this->skillNameCache = null;

        $this->seedCoreUsers();
        $this->seedStaffTeam();
        $this->seedAdditionalStaff(5);

        $staffMembers = User::where('is_staff', true)->get();

        $this->assignRolesAndSkills($staffMembers);
        $this->seedProjectPortfolio($staffMembers);
        $this->updateBusynessFromWorkload();
    }

    private function seedRoles(): void
    {
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

        Role::factory(10)->create();
    }

    private function seedSkills(): void
    {
        foreach ($this->getSkills() as $skillData) {
            Skill::factory()->create([
                'name' => $skillData['name'],
                'description' => $skillData['description'],
            ]);
        }
    }

    private function seedCoreUsers(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
            'busyness_week_1' => Busyness::MEDIUM,
            'busyness_week_2' => Busyness::MEDIUM,
        ]);

        User::factory()->staff()->create([
            'username' => 'staff2x',
            'email' => 'staff2x@example.ac.uk',
            'password' => bcrypt('secret'),
            'busyness_week_1' => Busyness::LOW,
            'busyness_week_2' => Busyness::MEDIUM,
        ]);
    }

    private function seedStaffTeam(): void
    {
        collect($this->staffProfiles())->each(function (array $profile, int $index) {
            User::factory()->staff()->create([
                'forenames' => $profile['forenames'],
                'surname' => $profile['surname'],
                'username' => Str::slug($profile['forenames'].' '.$profile['surname']).($index + 1),
                'email' => Str::slug($profile['forenames']).'.'.Str::slug($profile['surname']).'@example.ac.uk',
                'busyness_week_1' => $profile['week_1'],
                'busyness_week_2' => $profile['week_2'],
            ]);
        });
    }

    private function seedAdditionalStaff(int $count): void
    {
        $levels = collect(Busyness::cases());

        $users = User::factory()->count($count)->create();
        $users->each(function (User $user) use ($levels) {
            $user->forceFill([
                'busyness_week_1' => $levels->random(),
                'busyness_week_2' => $levels->random(),
            ])->save();
        });
    }

    private function assignRolesAndSkills(Collection $staffMembers): void
    {
        $allRoles = Role::all();
        $allSkills = $this->skills();
        $skillLevels = collect(SkillLevel::cases());

        foreach ($staffMembers as $member) {
            if ($allRoles->isNotEmpty()) {
                $maxRoles = min(3, $allRoles->count());
                $roleCount = random_int(1, $maxRoles);
                $roles = $allRoles->shuffle()->take($roleCount);
                $member->roles()->syncWithoutDetaching($roles->pluck('id')->all());
            }

            if ($allSkills->isNotEmpty()) {
                $maxSkills = min(14, $allSkills->count());
                $minSkills = min(5, $maxSkills);
                $skills = $allSkills->shuffle()->take(random_int($minSkills, $maxSkills));

                $skills->each(function (Skill $skill) use ($member, $skillLevels) {
                    $member->skills()->syncWithoutDetaching([
                        $skill->id => ['skill_level' => $skillLevels->random()->value],
                    ]);
                });
            }
        }
    }

    private function seedProjectPortfolio(Collection $staffMembers): void
    {
        $progression = array_values(ProjectStatus::getProgressStages());
        $statusPool = collect(ProjectStatus::cases())
            ->reject(fn (ProjectStatus $status) => $status === ProjectStatus::COMPLETED);

        foreach ($staffMembers as $member) {
            $projectCount = random_int(1, 6);
            $statuses = collect([ProjectStatus::COMPLETED])
                ->when(random_int(0, 1), fn (Collection $collection) => $collection->push(ProjectStatus::CANCELLED))
                ->merge($statusPool->shuffle()->take($projectCount))
                ->take($projectCount)
                ->shuffle();

            foreach ($statuses as $status) {
                $timeline = $this->projectTimeline($status);

                $project = Project::factory()
                    ->for($member, 'user')
                    ->create([
                        'status' => $status,
                        'deadline' => $timeline['deadline'],
                    ]);

                $project->forceFill([
                    'created_at' => $timeline['created_at'],
                    'updated_at' => $timeline['updated_at'],
                ])->save();

                $this->seedStageData($project, $status, $staffMembers, $progression);
                $this->ensureTeamForInFlightProject($project, $status, $staffMembers);
                $this->ensureStagePlaceholders($project, $staffMembers);
                $this->seedHistoryEntries($project, $staffMembers, $timeline['created_at'], $timeline['updated_at']);
            }
        }
    }

    private function seedStageData(Project $project, ProjectStatus $status, Collection $staffMembers, array $progression): void
    {
        if ($status === ProjectStatus::CANCELLED) {
            $targetIndex = random_int(0, max(0, count($progression) - 2));
        } elseif ($status === ProjectStatus::COMPLETED) {
            $targetIndex = count($progression) - 1;
        } else {
            $targetIndex = array_search($status, $progression, true);
        }

        if ($targetIndex === false) {
            return;
        }

        $stagesToCreate = array_slice($progression, 0, $targetIndex + 1);
        $faker = fake();

        foreach ($stagesToCreate as $stage) {
            $modelClass = $stage->stageModel();

            if (! $modelClass) {
                continue;
            }

            $attributes = ['project_id' => $project->id];
            $assignee = $staffMembers->random();

            switch ($stage) {
                case ProjectStatus::FEASIBILITY:
                case ProjectStatus::SCOPING:
                    $attributes['assessed_by'] = $assignee->id;
                    break;
                case ProjectStatus::SCHEDULING:
                    $attributes['assigned_to'] = $assignee->id;
                    $requiredSkillIds = collect(optional($project->scoping)->skills_required ?? []);
                    if ($requiredSkillIds->isEmpty()) {
                        $requiredSkillIds = $this->sampleSkillIds();
                        $project->scoping?->update(['skills_required' => $requiredSkillIds->all()]);
                    }

                    $attributes['key_skills'] = $this->skillNamesFor($requiredSkillIds);
                    $teamSize = random_int(1, 4);
                    $attributes['cose_it_staff'] = $this->randomStaffIds($staffMembers, $teamSize, [$assignee->id, $project->user_id]);
                    $attributes['estimated_start_date'] = Carbon::now()->addDays(random_int(10, 30));
                    $attributes['estimated_completion_date'] = Carbon::now()->addDays(random_int(40, 80));
                    $attributes['change_board_date'] = Carbon::now()->addDays(random_int(15, 45));
                    $attributes['priority'] = $faker->randomElement(['low', 'medium', 'high', 'critical']);
                    $attributes['team_assignment'] = $faker->words(2, true);
                    break;
                case ProjectStatus::DETAILED_DESIGN:
                    $attributes['designed_by'] = $assignee->id;
                    break;
                case ProjectStatus::DEVELOPMENT:
                    $attributes['lead_developer'] = $assignee->id;
                    $attributes['development_team'] = $this->randomStaffIds($staffMembers, random_int(1, 3), [$assignee->id, $project->user_id]);
                    $attributes['technical_approach'] = $faker->paragraph();
                    $attributes['development_notes'] = $faker->paragraph();
                    $attributes['repository_link'] = $faker->url();
                    $attributes['status'] = $faker->randomElement(['planning', 'in_progress', 'review', 'completed']);
                    $attributes['start_date'] = Carbon::now()->subDays(random_int(10, 60));
                    $attributes['completion_date'] = Carbon::now()->addDays(random_int(15, 90));
                    $attributes['code_review_notes'] = $faker->sentence();
                    break;
                case ProjectStatus::TESTING:
                    $attributes['test_lead'] = $assignee->id;
                    break;
                case ProjectStatus::DEPLOYED:
                    $attributes['deployed_by'] = $assignee->id;
                    break;
            }

            if ($stage === ProjectStatus::SCOPING) {
                $skillIds = $this->sampleSkillIds();
                $attributes['skills_required'] = $skillIds->all();
            }

            $record = $modelClass::factory()->create($attributes);

            if ($relation = $stage->relationName()) {
                $project->setRelation($relation, $record);
            }
        }
    }

    private function ensureTeamForInFlightProject(Project $project, ProjectStatus $status, Collection $staffMembers): void
    {
        $stagesRequiringTeam = [
            ProjectStatus::DETAILED_DESIGN,
            ProjectStatus::DEVELOPMENT,
            ProjectStatus::TESTING,
            ProjectStatus::DEPLOYED,
            ProjectStatus::COMPLETED,
        ];

        if (! in_array($status, $stagesRequiringTeam, true)) {
            return;
        }

        $availableStaff = $staffMembers
            ->filter(fn ($member) => $member->id !== $project->user_id)
            ->values();

        if ($availableStaff->isEmpty()) {
            return;
        }

        $requiredSkillIds = collect(optional($project->scoping)->skills_required ?? []);

        if ($requiredSkillIds->isEmpty()) {
            $requiredSkillIds = $this->sampleSkillIds();
            $project->scoping?->update(['skills_required' => $requiredSkillIds->all()]);
        }

        $assigned = optional($project->scheduling)->assigned_to;

        if (! $assigned || $assigned === $project->user_id) {
            $assigned = $availableStaff->random()->id;
        }

        $existingExtras = collect(optional($project->scheduling)->cose_it_staff ?? [])
            ->filter(fn ($id) => $id !== $project->user_id && $id !== $assigned)
            ->values();

        $extraTarget = random_int(0, 3);
        $needed = max(0, $extraTarget - $existingExtras->count());
        $newExtras = $needed > 0
            ? $this->randomStaffIds($availableStaff, $needed, array_merge([$assigned], $existingExtras->all()))
            : [];
        $team = $existingExtras
            ->concat($newExtras)
            ->prepend($assigned)
            ->unique()
            ->values();

        $maxTeamMembers = max(1, min(4, $team->count()));
        $finalSize = random_int(1, $maxTeamMembers);
        $team = $team->take($finalSize);
        $payload = [
            'assigned_to' => $assigned,
            'cose_it_staff' => $team->all(),
            'key_skills' => $this->skillNamesFor($requiredSkillIds),
        ];

        if ($project->scheduling) {
            $project->scheduling->forceFill($payload)->save();
        } else {
            $payload['project_id'] = $project->id;
            $project->setRelation('scheduling', Scheduling::factory()->create($payload));
        }
    }

    private function ensureStagePlaceholders(Project $project, Collection $staffMembers): void
    {
        foreach (ProjectStatus::stageStatuses() as $status) {
            $modelClass = $status->stageModel();

            if (! $modelClass) {
                continue;
            }

            $modelClass::firstOrCreate(
                ['project_id' => $project->id],
                $this->stagePlaceholderData($status, $project, $staffMembers)
            );
        }
    }

    private function stagePlaceholderData(ProjectStatus $status, Project $project, Collection $staffMembers): array
    {
        $faker = fake();
        $staffId = $this->pickStaffId($staffMembers, [$project->user_id]);

        $factory = match ($status) {
            ProjectStatus::IDEATION => function () use ($faker) {
                return [
                    'school_group' => $faker->randomElement(['Engineering', 'Computing', 'Chemistry', 'Business']),
                    'objective' => $faker->sentence(),
                    'business_case' => $faker->paragraph(),
                    'benefits' => $faker->paragraph(),
                    'deadline' => Carbon::now()->addDays(random_int(45, 120)),
                    'strategic_initiative' => $faker->randomElement(['thing', 'other', 'something']),
                ];
            },
            ProjectStatus::FEASIBILITY => function () use ($faker, $staffId, $project) {
                $assessor = $staffId ?? $project->user_id;

                return [
                    'assessed_by' => $assessor,
                    'date_assessed' => Carbon::now()->addDays(random_int(7, 30)),
                    'technical_credence' => $faker->sentences(2, true),
                    'cost_benefit_case' => $faker->paragraph(),
                    'dependencies_prerequisites' => $faker->paragraph(),
                    'deadlines_achievable' => $faker->boolean(),
                    'alternative_proposal' => $faker->sentence(),
                ];
            },
            ProjectStatus::SCOPING => function () use ($faker, $staffId, $project) {
                $assessor = $staffId ?? $project->user_id;
                $skillIds = $this->sampleSkillIds();

                return [
                    'assessed_by' => $assessor,
                    'estimated_effort' => $faker->sentence(),
                    'in_scope' => $faker->paragraph(),
                    'out_of_scope' => $faker->paragraph(),
                    'assumptions' => $faker->paragraph(),
                    'skills_required' => $skillIds->all(),
                ];
            },
            ProjectStatus::SCHEDULING => function () use ($faker, $staffMembers, $project) {
                $assigned = $this->pickStaffId($staffMembers, [$project->user_id]);
                $start = Carbon::now()->addDays(random_int(10, 40));
                $completion = $start->copy()->addDays(random_int(20, 60));
                $existingSkills = collect(optional($project->scoping)->skills_required ?? []);

                if ($existingSkills->isEmpty()) {
                    $existingSkills = $this->sampleSkillIds();
                }

                $team = collect($this->randomStaffIds($staffMembers, random_int(2, 4), [$assigned, $project->user_id]));

                return [
                    'assigned_to' => $assigned,
                    'key_skills' => $this->skillNamesFor($existingSkills),
                    'cose_it_staff' => $team->all(),
                    'estimated_start_date' => $start,
                    'estimated_completion_date' => $completion,
                    'change_board_date' => Carbon::now()->addDays(random_int(5, 25)),
                    'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                    'team_assignment' => $faker->words(2, true),
                ];
            },
            ProjectStatus::DETAILED_DESIGN => function () use ($faker, $staffMembers, $project) {
                $designer = $this->pickStaffId($staffMembers, [$project->user_id]);

                return [
                    'designed_by' => $designer,
                    'service_function' => $faker->sentence(),
                    'functional_requirements' => $faker->paragraph(),
                    'non_functional_requirements' => $faker->paragraph(),
                    'hld_design_link' => $faker->url(),
                    'approval_delivery' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'approval_operations' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'approval_resilience' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'approval_change_board' => $faker->randomElement(['pending', 'approved', 'rejected']),
                ];
            },
            ProjectStatus::DEVELOPMENT => function () use ($faker, $staffMembers, $project) {
                $lead = $this->pickStaffId($staffMembers, [$project->user_id]);
                $team = collect($this->randomStaffIds($staffMembers, random_int(1, 3), [$lead, $project->user_id]));
                $start = Carbon::now()->subDays(random_int(30, 90));
                $completion = $start->copy()->addDays(random_int(20, 80));

                return [
                    'lead_developer' => $lead,
                    'development_team' => $team->prepend($lead)->unique()->values()->all(),
                    'technical_approach' => $faker->paragraph(),
                    'development_notes' => $faker->paragraph(),
                    'repository_link' => $faker->url(),
                    'status' => $faker->randomElement(['planning', 'in_progress', 'review', 'completed']),
                    'start_date' => $start,
                    'completion_date' => $completion,
                    'code_review_notes' => $faker->sentence(),
                ];
            },
            ProjectStatus::TESTING => function () use ($faker, $staffMembers, $project) {
                $lead = $this->pickStaffId($staffMembers, [$project->user_id]);

                return [
                    'test_lead' => $lead,
                    'service_function' => $faker->sentence(),
                    'functional_testing_title' => $faker->sentence(),
                    'functional_tests' => $faker->paragraph(),
                    'non_functional_testing_title' => $faker->sentence(),
                    'non_functional_tests' => $faker->paragraph(),
                    'test_repository' => $faker->url(),
                    'testing_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'user_acceptance' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'testing_lead_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'service_delivery_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'service_resilience_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                ];
            },
            ProjectStatus::DEPLOYED => function () use ($faker, $staffMembers, $project) {
                $deployer = $this->pickStaffId($staffMembers, [$project->user_id]);

                return [
                    'deployed_by' => $deployer,
                    'environment' => $faker->randomElement(['development', 'staging', 'production']),
                    'status' => $faker->randomElement(['pending', 'deployed', 'failed', 'rolled_back']),
                    'deployment_date' => Carbon::now()->subDays(random_int(1, 14)),
                    'version' => $faker->semver(),
                    'production_url' => $faker->url(),
                    'deployment_notes' => $faker->sentence(),
                    'rollback_plan' => $faker->sentence(),
                    'monitoring_notes' => $faker->sentence(),
                    'deployment_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'operations_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'user_acceptance' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'service_delivery_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'change_advisory_sign_off' => $faker->randomElement(['pending', 'approved', 'rejected']),
                ];
            },
            default => fn () => [],
        };

        return $factory();
    }

    private function sampleSkillIds(int $min = 2, int $max = 4): Collection
    {
        $skills = $this->skills();

        if ($skills->isEmpty()) {
            return collect();
        }

        $upper = max(1, min($max, $skills->count()));
        $lower = max(1, min($min, $upper));
        $take = $lower === $upper ? $upper : random_int($lower, $upper);

        return $skills->shuffle()
            ->take($take)
            ->pluck('id');
    }

    private function skillNamesFor(Collection|array $skillIds): string
    {
        $ids = $skillIds instanceof Collection ? $skillIds : collect($skillIds);

        if ($ids->isEmpty()) {
            return 'General support';
        }

        $names = $this->skillNames();

        $label = $ids
            ->map(fn ($id) => $names->get($id))
            ->filter()
            ->unique()
            ->take(3)
            ->implode(', ');

        return $label ?: 'General support';
    }

    private function seedHistoryEntries(Project $project, Collection $staffMembers, Carbon $createdAt, Carbon $updatedAt): void
    {
        $historyCount = random_int(2, 5);
        $start = $createdAt->copy();
        $end = $updatedAt->copy();

        foreach (range(1, $historyCount) as $index) {
            $timestamp = $start->copy()->addDays(random_int(1, max(1, $start->diffInDays($end) ?: 1)));

            if ($timestamp->gt($end)) {
                $timestamp = $end->copy();
            }

            $history = ProjectHistory::factory()->create([
                'project_id' => $project->id,
                'user_id' => $staffMembers->random()->id,
                'description' => fake()->sentence(),
            ]);

            $history->forceFill([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->save();
        }
    }

    private function updateBusynessFromWorkload(): void
    {
        $staffMembers = User::where('is_staff', true)->get();

        foreach ($staffMembers as $member) {
            $activeCount = $member->projects()
                ->whereNotIn('status', [
                    ProjectStatus::COMPLETED->value,
                    ProjectStatus::CANCELLED->value,
                ])->count();

            $weekOne = $this->busynessLevelForCount($activeCount);
            $weekTwo = $this->busynessLevelForCount(max(0, $activeCount + random_int(-1, 1)));

            $member->forceFill([
                'busyness_week_1' => $weekOne,
                'busyness_week_2' => $weekTwo,
            ])->save();
        }
    }

    private function busynessLevelForCount(int $activeProjects): Busyness
    {
        return match (true) {
            $activeProjects >= 5 => Busyness::HIGH,
            $activeProjects >= 3 => Busyness::MEDIUM,
            $activeProjects >= 1 => Busyness::LOW,
            default => Busyness::UNKNOWN,
        };
    }

    private function projectTimeline(ProjectStatus $status): array
    {
        $now = now();

        return match ($status) {
            ProjectStatus::COMPLETED => [
                'deadline' => $now->copy()->subDays(random_int(7, 40)),
                'created_at' => $now->copy()->subDays(random_int(220, 320)),
                'updated_at' => $now->copy()->subDays(random_int(5, 20)),
            ],
            ProjectStatus::CANCELLED => [
                'deadline' => null,
                'created_at' => $now->copy()->subDays(random_int(160, 260)),
                'updated_at' => $now->copy()->subDays(random_int(2, 15)),
            ],
            ProjectStatus::DEVELOPMENT, ProjectStatus::TESTING => [
                'deadline' => $now->copy()->addDays(random_int(7, 30)),
                'created_at' => $now->copy()->subDays(random_int(120, 200)),
                'updated_at' => $now->copy()->subDays(random_int(0, 4)),
            ],
            ProjectStatus::DEPLOYED => [
                'deadline' => $now->copy()->addDays(random_int(0, 7)),
                'created_at' => $now->copy()->subDays(random_int(140, 220)),
                'updated_at' => $now->copy()->subDays(random_int(0, 3)),
            ],
            ProjectStatus::SCOPING, ProjectStatus::SCHEDULING, ProjectStatus::DETAILED_DESIGN => [
                'deadline' => $now->copy()->addDays(random_int(20, 60)),
                'created_at' => $now->copy()->subDays(random_int(90, 180)),
                'updated_at' => $now->copy()->subDays(random_int(1, 10)),
            ],
            default => [
                'deadline' => $now->copy()->addDays(random_int(30, 90)),
                'created_at' => $now->copy()->subDays(random_int(60, 160)),
                'updated_at' => $now->copy()->subDays(random_int(3, 12)),
            ],
        };
    }

    private function randomStaffIds(Collection $staffMembers, int $count, array $except = []): array
    {
        $filtered = $staffMembers
            ->filter(fn ($member) => ! in_array($member->id, $except, true))
            ->values();

        if ($filtered->isEmpty()) {
            return [];
        }

        return $filtered
            ->shuffle()
            ->take(min($count, $filtered->count()))
            ->pluck('id')
            ->values()
            ->all();
    }

    private function pickStaffId(Collection $staffMembers, array $except = []): ?int
    {
        $filtered = $staffMembers
            ->filter(fn ($member) => ! in_array($member->id, $except, true))
            ->values();

        if ($filtered->isEmpty()) {
            $filtered = $staffMembers->values();
        }

        return optional($filtered->shuffle()->first())->id;
    }

    private function staffProfiles(): array
    {
        return [
            ['forenames' => 'Amelia', 'surname' => 'Wu', 'week_1' => Busyness::LOW, 'week_2' => Busyness::MEDIUM],
            ['forenames' => 'Noah', 'surname' => 'Patel', 'week_1' => Busyness::MEDIUM, 'week_2' => Busyness::HIGH],
            ['forenames' => 'Freya', 'surname' => 'Campbell', 'week_1' => Busyness::LOW, 'week_2' => Busyness::LOW],
            ['forenames' => 'Luca', 'surname' => 'Martinez', 'week_1' => Busyness::MEDIUM, 'week_2' => Busyness::HIGH],
            ['forenames' => 'Elena', 'surname' => 'Hughes', 'week_1' => Busyness::HIGH, 'week_2' => Busyness::MEDIUM],
            ['forenames' => 'Kofi', 'surname' => 'Mensah', 'week_1' => Busyness::UNKNOWN, 'week_2' => Busyness::LOW],
            ['forenames' => 'Chloe', 'surname' => 'Murphy', 'week_1' => Busyness::MEDIUM, 'week_2' => Busyness::MEDIUM],
            ['forenames' => 'Jonah', 'surname' => 'Schulte', 'week_1' => Busyness::HIGH, 'week_2' => Busyness::HIGH],
        ];
    }

    private function getSkills(): array
    {
        return [
            ['name' => 'Project Management - Agile Planning & Monitoring', 'description' => 'Managing prioritised backlogs, creating sprint plans, allocating resource assignments, and monitoring delivery progress in Agile environments.'],
            ['name' => 'Project Management - Risk & Issue Management', 'description' => 'Ensuring risk and issue logs and monitoring of resolution.'],
            ['name' => 'Project Management - Stakeholder Communication', 'description' => 'Keeping stakeholders informed and aware of project progress and upcoming work.'],
            ['name' => 'Project Management - Budget & Resource Management', 'description' => 'Tracking costs, resource usage, and forecast budget to complete.'],
            ['name' => 'Project Management - Project Governance & Reporting', 'description' => 'Preparing meeting packs, following governance processes, and providing status updates.'],
            ['name' => 'Project Management - Team Leadership & Coaching', 'description' => 'Managing workloads, coaching staff, and ensuring consistent quality of delivery.'],
            ['name' => 'Project Management - Continuous Improvement & Retrospectives', 'description' => 'Leading retrospectives, identifying process improvements, and following up on actions.'],
            ['name' => 'Project Management - Contract & Vendor Management', 'description' => 'Managing third-party suppliers, contracts, service dependencies, and service level agreements.'],

            // Business Analysis
            ['name' => 'Business Analysis - Requirements Elicitation & Analysis', 'description' => 'Gathering, analysing, and documenting business, functional, and non-functional requirements.'],
            ['name' => 'Business Analysis - Process Mapping & Optimisation', 'description' => 'Creating process maps, identifying process gaps, and optimising workflows.'],
            ['name' => 'Business Analysis - Use Cases & User Stories', 'description' => 'Writing use cases, user stories, acceptance criteria, and maintaining product backlogs.'],
            ['name' => 'Business Analysis - Stakeholder Collaboration', 'description' => 'Working closely with stakeholders to ensure alignment and clarity of requirements.'],
            ['name' => 'Business Analysis - Business Case Development', 'description' => 'Creating business cases, feasibility studies, and cost-benefit analyses.'],
            ['name' => 'Business Analysis - Change Management Support', 'description' => 'Supporting change impact assessments and transition planning.'],

            // Service Design / Product Management
            ['name' => 'Service Design - User Research & Interviews', 'description' => 'Conducting user research, interviews, and workshops to gather insights.'],
            ['name' => 'Service Design - Personas & Experience Maps', 'description' => 'Creating user personas, journey maps, and service blueprints.'],
            ['name' => 'Service Design - Prototyping & Wireframing', 'description' => 'Designing prototypes, mockups, and wireframes for digital experiences.'],
            ['name' => 'Service Design - UX Writing & Content Design', 'description' => 'Crafting user-friendly content, microcopy, and supporting structured content models.'],
            ['name' => 'Service Design - Usability Testing & User Feedback', 'description' => 'Planning and conducting usability tests and analysing user feedback.'],
            ['name' => 'Service Design - Accessibility & Inclusive Design', 'description' => 'Ensuring services are accessible and inclusive for all users.'],
            ['name' => 'Service Design - Roadmapping & Prioritisation', 'description' => 'Building roadmaps based on user needs and organisational priorities.'],
            ['name' => 'Service Design - Service Support & Continuous Improvement', 'description' => 'Monitoring service performance and identifying areas for improvement.'],

            // Data & Reporting
            ['name' => 'Data - Data Strategy & Data Governance', 'description' => 'Developing data strategies and governance frameworks for effective management.'],
            ['name' => 'Data - Data Modelling & Entity Design', 'description' => 'Designing logical and physical data models to support business processes.'],
            ['name' => 'Data - Data Warehousing & ETL Processes', 'description' => 'Designing and implementing ETL pipelines and data warehousing solutions.'],
            ['name' => 'Data - Business Intelligence & Dashboards', 'description' => 'Building dashboards and reports using BI tools to support decision-making.'],
            ['name' => 'Data - Data Quality & Cleansing', 'description' => 'Ensuring data accuracy through validation, cleansing, and quality checks.'],
            ['name' => 'Data - Data Privacy & Compliance', 'description' => 'Implementing data protection measures and ensuring compliance with regulations.'],
            ['name' => 'Data - Database Administration & Optimisation', 'description' => 'Managing, tuning, and maintaining database performance and reliability.'],
            ['name' => 'Data - Data Integration & APIs', 'description' => 'Integrating data from multiple systems using APIs and integration platforms.'],

            // Cloud & Platform Engineering
            ['name' => 'Cloud Engineering - AWS Architecture & Operations', 'description' => 'Designing and operating workloads on Amazon Web Services.'],
            ['name' => 'Cloud Engineering - Microsoft Azure', 'description' => 'Designing, implementing, and managing services on Azure.'],
            ['name' => 'Cloud Engineering - Google Cloud', 'description' => 'Designing, implementing, and managing services on Google Cloud Platform.'],
            ['name' => 'Cloud Engineering - Infrastructure as Code', 'description' => 'Automating infrastructure using IaC tools like Terraform or CloudFormation.'],
            ['name' => 'Cloud Engineering - CI/CD Automation', 'description' => 'Implementing CI/CD pipelines for cloud-native applications.'],
            ['name' => 'Cloud Engineering - Cloud Security & Access Management', 'description' => 'Managing cloud security policies, IAM, and compliance.'],
            ['name' => 'Cloud Engineering - Cost Optimisation', 'description' => 'Monitoring and optimising cloud costs and resource usage.'],
            ['name' => 'Cloud Engineering - Observability & Monitoring', 'description' => 'Implementing monitoring, alerting, and observability practices for cloud systems.'],

            // SysOps & Infrastructure
            ['name' => 'Linux - Administration & Maintenance', 'description' => 'Configuring, managing, and maintaining Linux servers such as Rocky Linux and Ubuntu.'],
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

    private function skills(): Collection
    {
        return $this->skillCache ??= Skill::query()->get(['id', 'name']);
    }

    private function skillNames(): Collection
    {
        return $this->skillNameCache ??= $this->skills()->pluck('name', 'id');
    }
}
