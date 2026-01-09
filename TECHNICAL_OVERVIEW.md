# Technical Overview

Last updated: 2026-01-09

## What This Is

A demand tracking and delivery governance tool for small IT teams - tracking projects from ideation through deployment with skills-based staffing and workload visualization.

## Stack

- PHP 8.4 / Laravel 12
- Livewire 3.7 / Flux UI Pro 2.10
- Tailwind CSS 4
- MySQL
- Pest 3 (testing)
- Laravel Horizon (queues)
- Sentry (error tracking)

## Directory Structure

```
app/
├── Enums/              # ProjectStatus, SkillLevel, Busyness, Priority, etc.
├── Events/             # Project lifecycle events (14 events)
├── Http/Middleware/    # staff, admin middleware
├── Listeners/          # Event listeners for notifications & history
├── Livewire/           # All UI components (27 components)
│   └── Forms/          # Stage-specific form objects
├── Mail/               # Email templates
├── Models/             # Eloquent models (15 models)
│   └── Traits/         # CanCheckIfEdited trait
└── Providers/          # AppServiceProvider (@admin blade directive)

routes/
├── web.php             # Main routes
└── sso-auth.php        # SSO/Shibboleth routes

tests/Feature/          # 27 feature tests covering all workflows
```

## Domain Model

```
User ←──────────────────────────────────────────────────┐
 │                                                      │
 ├──→ Project (hasMany)                                 │
 │     ├──→ Ideation (hasOne)                          │
 │     ├──→ Feasibility (hasOne)                       │
 │     ├──→ Scoping (hasOne)                           │
 │     ├──→ Scheduling (hasOne) ──→ assignedUser       │
 │     │                        ──→ technicalLead      │
 │     │                        ──→ changeChampion     │
 │     ├──→ DetailedDesign (hasOne)                    │
 │     ├──→ Development (hasOne)                       │
 │     ├──→ Build (hasOne)                             │
 │     ├──→ Testing (hasOne)                           │
 │     ├──→ Deployed (hasOne)                          │
 │     └──→ ProjectHistory (hasMany)                   │
 │                                                      │
 ├←─→ Role (belongsToMany)                             │
 │                                                      │
 └←─→ Skill (belongsToMany with pivot: skill_level)    │
```

### Key Model Fields

**User**: `username`, `surname`, `forenames`, `email`, `is_staff`, `is_admin`, `service_function`, `busyness_week_1`, `busyness_week_2`

**Project**: `user_id`, `title`, `school_group`, `deadline`, `status` (ProjectStatus enum)

**Scheduling** (team assignment stage): `assigned_to`, `technical_lead_id`, `change_champion_id`, `cose_it_staff` (JSON array), `priority`, `estimated_start_date`, `estimated_completion_date`, `change_board_outcome`

**Skill**: `name`, `description`, `skill_category` - users have skill via pivot with `skill_level`

### Enums

| Enum | Values | Notes |
|------|--------|-------|
| `ProjectStatus` | ideation → feasibility → scoping → scheduling → detailed-design → development → build → testing → deployed → completed, cancelled | Has `getNextStatus()`, `stageModel()`, `relationName()` helpers |
| `SkillLevel` | beginner, intermediate, advanced | With numeric values 1-3 |
| `Busyness` | UNKNOWN(0), LOW(30), MEDIUM(60), HIGH(90) | Used for heat map, has `fromProjectCount()` |
| `Priority` | (defined in app/Enums/Priority.php) | Project priority levels |
| `ServiceFunction` | college_infrastructure, research_computing, applications_data, service_resilience, service_delivery | IT team functions |
| `ChangeBoardOutcome` | (defined in app/Enums/ChangeBoardOutcome.php) | Change board decisions |
| `EffortScale` | (defined in app/Enums/EffortScale.php) | Effort estimation |

## Authorization

| Check | How |
|-------|-----|
| Admin | `User::is_admin` boolean, `@admin` Blade directive |
| Staff | `User::is_staff` boolean |
| Middleware | `admin` → `IsAdmin`, `staff` → `EnsureUserIsStaff` |

### Blade Directive

```blade
@admin
    <!-- Only visible to admins -->
@endadmin
```

## Routes Overview

### Web Routes

| Route | Handler | Access |
|-------|---------|--------|
| `/` | `HomePage` | Auth |
| `/work-packages` | `ProjectList` | Staff |
| `/work-package/create` | `ProjectCreator` | Staff |
| `/work-package/{project}` | `ProjectViewer` | Staff |
| `/work-package/{project}/edit` | `ProjectEditor` | Staff |
| `/portfolio/backlog` | `BacklogList` | Staff |
| `/portfolio/roadmap` | `RoadmapView` | Staff |
| `/portfolio/change-on-a-page/{project}` | `ChangeOnAPage` | Staff |
| `/staff` | `UserList` | Admin |
| `/staff/heatmap` | `HeatMapViewer` | Staff |
| `/user/{user}` | `UserViewer` | Staff |
| `/skills` | `SkillsManager` | Admin |
| `/roles` | `RolesList` | Admin |
| `/profile` | `Profile` | Auth |

### Auth Routes

| Route | Handler |
|-------|---------|
| `/login` | Local login form |
| `/login/sso` | SSO/Shibboleth redirect |
| `/auth/callback` | SSO callback |
| `/logout` | Logout |

## Key Business Logic

| Location | Purpose |
|----------|---------|
| `Project::advanceToNextStage()` | Stage transitions, dispatches `ProjectStageChange` |
| `ProjectStatus::getNextStatus()` | Determines next stage (skips Development if no software) |
| `Scheduling` model | Team assignments (assigned_to, technical_lead, change_champion, cose_it_staff) |
| `User::activeAssignedProjectCount()` | Counts projects user is assigned to |
| `Busyness::fromProjectCount()` | Calculates busyness from project count |
| `app/Events/` | 14 lifecycle events for notifications |
| `app/Listeners/` | Send notifications, record history |

### Project Lifecycle

Projects progress through stages in order. Each stage has:
- A dedicated model (Ideation, Feasibility, etc.)
- A Livewire form in `app/Livewire/Forms/`
- Stage data created via `CreateRelatedForms` listener on project creation

Stage progression: Ideation → Feasibility → Scoping → Scheduling → Detailed Design → Development (optional) → Build → Testing → Deployed → Completed

## Testing

- Framework: Pest 3
- Pattern: Feature tests with factories, RefreshDatabase trait
- Factories: All models have factories with useful states
- Run: `php artisan test --compact` or with `--filter=testName`

### Notable Test Files

- `ProjectCreationTest.php` - Project creation flow
- `SchedulingWorkflowTest.php` - Team assignment
- `DeploymentWorkflowTest.php` - Deployment approval
- `SkillMatchingTest.php` - Skill-based assignments
- `HeatMapViewerTest.php` - Workload visualization

## Feature Flags

None currently - all features are always enabled.

## Local Development

```bash
# Start environment
lando start

# Fresh database
lando mfs  # migrate fresh + seed

# Run tests
php artisan test --compact

# Format code
vendor/bin/pint --dirty

# Queue worker (for notifications)
lando artisan horizon

# Build assets
npm run dev
```

### Default Logins (seeded)

- Admin: `admin2x` / `secret`
- Staff: `staff2x` / `secret`
- Test user: `testuser` / `password`
