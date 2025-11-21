# Projman Project Plan

> **Note**: This is a consolidated planning document presenting the project's clean, intentional progression. For detailed development history and decision-making process, see archived documentation in `development_history/`.

---

## 1. Executive Summary

**Projman** is a governance-focused project management application for corporate LAN deployment. It tracks technology projects through multiple stages (Ideation → Feasibility → Scoping → Scheduling → Detailed Design → Development/Build → Testing → Deployment) with approval workflows, email notifications, and portfolio reporting.

**Requirements Source**: PowerPoint specification deck (text extracted to `pptx_text_extract.txt` for reference)

**Current Status**:
- ✅ Features 1-3 complete (Feasibility, Scoping, Scheduling enhancements)
- ✅ Software Development vs Build toggle complete
- ✅ 371 tests passing (1,268 assertions)
- ⏳ Features 4-6 remaining (Testing, Deployment, Portfolio outputs)

**Tech Stack**:
- Laravel 12 with streamlined structure
- Livewire 3 for reactive UI
- Flux UI Pro component library
- Pest testing framework
- Lando for local development

---

## 2. Guiding Principles

These principles guide all development decisions and prioritization:

- **Leadership directives first**: Requirements from `pptx_text_extract.txt` unlock governance reporting and lead the roadmap
- **Usability before hardening**: Validation bugs that block day-to-day edits get fixed ahead of theoretical exploit paths
- **Right-sized authorization**: Even on a trusted LAN we prevent obvious cross-user accidents, but lean on lightweight policies instead of building a fortress
- **Stage-by-stage parity**: Every workflow change touches both the Livewire form class in `app/Livewire/Forms/*` and the paired Blade partial in `resources/views/livewire/forms/`, plus relevant models/events/notifications

---

## 3. Architecture & Key Decisions

### Notification System: Config-Driven
- All notification routing defined in `config/projman.php`
- Maps events → roles → mailables
- No database configuration required (replaced 700 lines of complexity with 60 lines of config)
- Supports role-based recipients, project owner inclusion, and special recipients (like DCGG email)

### Listener Architecture
- Discrete listeners per event (not one massive listener)
- Laravel 12 auto-discovery (no manual registration needed)
- `RoleUserResolver` service handles recipient resolution logic
- Fail-fast with descriptive RuntimeException if no recipients found (for Sentry integration)

### Code Reuse Strategy
- **Traits over service classes** (team convention)
- Example: `HasHeatmapData` trait shares heatmap logic between `HeatMapViewer` and `ProjectEditor`
- Fat models with helper methods and business logic

### DCGG Workflow Location
- Digital Change Governance Group (DCGG) approval workflow lives on **Scheduling stage** (not Scoping)
- Submit to DCGG → Schedule progression with email notifications

### Development vs Build Projects
- Toggle checkbox on Scoping stage: "Requires in-house/custom software development"
- Both Development and Build tabs always visible (regardless of checkbox state)
- Build tab shows "TBC" placeholder until requirements defined by stakeholders
- Full Build model infrastructure created (model, factory, form, enum integration)

---

## 4. Completed Features

### Infrastructure Setup ✅
**Date Completed**: 2025-11-10

- Created enums: `EffortScale` (Small → XX-Large), `ChangeBoardOutcome` (Pending/Approved/Deferred/Rejected)
- Ran 6 database migrations (feasibility approval fields, scoping DCGG fields, scheduling triage, testing UAT, deployment acceptance, builds table)
- Seeded roles: "Work Package Assessor" and "Service Lead" (singular names)
- **Btw**: Role names are singular in database (important for queries)

### Feature 1: Feasibility Approvals & Rejection Workflow ✅
**Date Completed**: 2025-11-11

- Approve/Reject buttons with modal workflow
- Business rule enforced: Cannot approve if existing solution identified
- Email notifications to Work Package Assessors on approve/reject
- Rejection requires reason (captured in modal)
- Audit trail: `actioned_by` field tracks who approved/rejected
- History tracking for all actions
- Testing: 14 tests passing (31 assertions)

### Notification System Refactor ✅
**Date Completed**: 2025-11-18

- Replaced database-driven notification rules system (700+ lines with admin UI, JSON config, queue jobs)
- Now: Simple config-driven approach in `config/projman.php` (~60 lines)
- Benefits: No database seeds required, version controlled, simpler, more reliable
- **Btw**: Original system was over-engineered for fixed notification requirements

### Listener Architecture Refactor ✅
**Date Completed**: 2025-11-18

- Created `RoleUserResolver` service for centralized recipient resolution
- Discrete listeners per event (Laravel 12 auto-discovery pattern)
- Removed 117-line "mega listener" that handled everything
- Test helpers added: `setupBaseNotificationRoles()`, `fakeNotifications()`, `ensureProjectCreatedRoles()`
- Reduced from 84 test failures to 0 by fixing notification role setup
- All 342 tests passing after refactor

### Staff Heatmap Sorting Fix ✅
**Date Completed**: 2025-11-19

- Fixed multi-column sort to properly prioritize by skill score
- Now returns ALL staff users (not just those with matching skills) to support onboarding
- Matched staff appear at top sorted by skill score, others below alphabetically
- Testing: 4 tests updated, all passing

### Feature 2: Scoping & Scheduling Enhancements ✅
**Date Completed**: 2025-11-18

**Scoping Stage**:
- Effort scale dropdown replacing free text (Small, Medium, Large, X-Large, XX-Large enum)
- Submit button emails Work Package Assessors for review
- **Btw**: Originally had DCGG workflow here but moved to Scheduling per spec

**Scheduling Stage**:
- DCGG workflow: Submit to DCGG → Schedule progression
- Submit to DCGG emails Work Package Assessors + DCGG group email
- Schedule button validates Change Board date is filled
- Audit trail: `submitted_to_dcgg_by` tracks who submitted
- Model button displays inline heatmap with smart staff sorting

**Heatmap Infrastructure**:
- Extracted to `HasHeatmapData` trait for reuse
- Blade partial `components/heatmap-table.blade.php`
- Refactored `HeatMapViewer` from 171 lines to 24 lines
- Smart sorting: Assigned staff at top, others alphabetically below

- Testing: 32 tests passing (115 assertions across 3 test files)

### Feature 3: Scheduling Stage Triage Inputs ✅
**Date Completed**: 2025-11-21

- Added three dropdowns to Scheduling tab:
  - Technical Lead (user dropdown - foreign key to users)
  - Change Champion (user dropdown - foreign key to users)
  - Change Board Outcome (enum: Pending/Approved/Deferred/Rejected)
- Model relationships added: `technicalLead()`, `changeChampion()`
- 3-column grid layout for visual consistency
- Testing: 17 tests passing (47 assertions)
- **Btw**: Backend partially implemented by Feature 2, just needed UI and enum field

### Software Development vs Build Project Toggle ✅
**Date Completed**: 2025-11-21 (Both Phases)

**Phase 1 - Toggle & UI**:
- Checkbox on Scoping stage: "Requires in-house/custom software development"
- Defaults to `true` (checked) for backward compatibility
- When unchecked: Development form fields disabled (using Flux fieldset) with explanatory callout
- When checked: Development form fully editable (normal behavior)
- Both Development and Build tabs always visible
- Build tab shows "TBC" placeholder
- Testing: 10 tests passing (25 assertions)

**Phase 2 - Build Model Infrastructure**:
- Created complete Build model with `CanCheckIfEdited` trait
- Created BuildFactory for test data
- Created BuildForm Livewire form with `setProject()` and `save()` methods
- Updated `ProjectStatus` enum (9 comprehensive changes)
- Build stage added to progression: Deployed → Build → Completed
- Updated config and seeder for Build stage
- All 371 tests passing (no regressions)
- **Btw**: Build fields TBD - infrastructure complete, forms ready for stakeholder input

---

## 5. Form Partial Extraction

> **Important Note for Features 4-6 Implementation:**
>
> After Feature 3, all stage forms were extracted from the monolithic `project-editor.blade.php` (was 681 lines) into separate partial files in `resources/views/livewire/forms/`:
>
> - `ideation-form.blade.php`
> - `feasibility-form.blade.php`
> - `scoping-form.blade.php`
> - `scheduling-form.blade.php`
> - `detailed-design-form.blade.php`
> - `development-form.blade.php`
> - `build-form.blade.php`
> - `testing-form.blade.php`
> - `deployed-form.blade.php`
>
> The implementation guides for Features 4-6 below reference line numbers in the old monolithic file. **When implementing, edit the appropriate partial file instead.** The main `project-editor.blade.php` now just includes these partials (see lines 36-71).

---

## 6. Remaining Phase 1 Features

The following implementation guides were created during initial planning and contain valuable detail about requirements, file structure, and patterns to follow. Note that line numbers and file paths reference the old monolithic file structure - see note above about form partials.

### Feature 4: Testing Approvals & UAT Capture

**Changes Required:**

#### 4.1 Update Model (`app/Models/Testing.php`)
Add relationship and casts:
```php
public function uatTester(): BelongsTo
{
    return $this->belongsTo(User::class, 'uat_tester_id');
}

protected function casts(): array
{
    return [
        // ... existing
        'uat_approved_at' => 'datetime',
        'service_accepted_at' => 'datetime',
    ];
}
```

#### 4.2 Update Form (`app/Livewire/Forms/TestingForm.php`)
Add properties:
```php
public ?int $uatTesterId = null;
public string $uatApprovalStatus = 'pending';
public string $serviceAcceptanceStatus = 'pending';
```

Validation:
```php
'uatTesterId' => 'nullable|exists:users,id',
'uatApprovalStatus' => 'in:pending,approved,rejected',
'serviceAcceptanceStatus' => 'in:pending,approved,rejected',
```

#### 4.3 Add Component Actions (`app/Livewire/ProjectEditor.php`)
```php
public function approveUAT(): void
{
    if (empty($this->testingForm->uatTesterId)) {
        $this->addError('testing', 'UAT Tester must be assigned.');
        return;
    }

    $this->project->testing->update([
        'uat_approval_status' => 'approved',
        'uat_approved_at' => now(),
    ]);

    event(new \App\Events\UATApprovalRequested($this->project));

    $this->dispatch('uat-approved');
}

public function acceptService(): void
{
    // Validate UAT approved first
    if ($this->project->testing->uat_approval_status !== 'approved') {
        $this->addError('testing', 'UAT must be approved before Service Acceptance.');
        return;
    }

    // Validate all sign-offs are approved
    $signOffs = [
        $this->testingForm->testingSignOff,
        $this->testingForm->userAcceptance,
        $this->testingForm->testingLeadSignOff,
        $this->testingForm->serviceDeliverySignOff,
        $this->testingForm->serviceResilienceSignOff,
    ];

    if (in_array('pending', $signOffs) || in_array('rejected', $signOffs)) {
        $this->addError('testing', 'All sign-offs must be approved.');
        return;
    }

    $this->project->testing->update([
        'service_acceptance_status' => 'approved',
        'service_accepted_at' => now(),
    ]);

    event(new \App\Events\ServiceAcceptanceSubmitted($this->project));

    // Can now advance to Deployed stage
    $this->advanceStage();
}
```

#### 4.4 Update View (`resources/views/livewire/forms/testing-form.blade.php`)
Add UAT Tester field and new action buttons:
```blade
<flux:select label="UAT Tester" wire:model="testingForm.uatTesterId">
    <option value="">Select UAT tester...</option>
    @foreach($this->availableUsers as $user)
        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
    @endforeach
</flux:select>

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('testing')">Update</flux:button>

    @if($testingForm->uatApprovalStatus === 'pending')
        <flux:button wire:click="approveUAT">UAT Approval</flux:button>
    @endif

    @if($testingForm->uatApprovalStatus === 'approved')
        <flux:button wire:click="acceptService">Service Acceptance</flux:button>
    @endif
</div>
```

#### 4.5 Write Tests
Test UAT approval workflow, service acceptance gating, email notifications.

Run: `lando artisan test --filter=Testing`

---

### Feature 5: Deployment Acceptance Workflow

**Changes Required:**

#### 5.1 Update Model (`app/Models/Deployed.php`)
Add casts:
```php
protected function casts(): array
{
    return [
        // ... existing
        'service_accepted_at' => 'datetime',
        'deployment_approved_at' => 'datetime',
    ];
}
```

#### 5.2 Update Form (`app/Livewire/Forms/DeployedForm.php`)
Add properties:
```php
public ?string $serviceFunction = null;  // read-only, auto-populated
public string $serviceAcceptanceStatus = 'pending';
public string $deploymentApprovedStatus = 'pending';
```

Update `setProject()`:
```php
// Auto-populate from user
$this->serviceFunction = $project->user->service_function ?? 'Not Set';
```

#### 5.3 Add Component Actions (`app/Livewire/ProjectEditor.php`)
```php
public function acceptDeploymentService(): void
{
    $this->deployedForm->validate();

    // Check all required fields filled
    $requiredFields = [
        $this->deployedForm->actualDeploymentDate,
        $this->deployedForm->deploymentNotes,
        $this->deployedForm->deployedRepository,
        // etc.
    ];

    if (in_array(null, $requiredFields, true)) {
        $this->addError('deployed', 'All fields must be completed.');
        return;
    }

    $this->project->deployed->update([
        'service_acceptance_status' => 'approved',
        'service_accepted_at' => now(),
    ]);

    event(new \App\Events\DeploymentServiceAccepted($this->project));
}

public function approveDeployment(): void
{
    // Validate all sign-offs approved
    $signOffs = [
        $this->deployedForm->deploymentSignOff,
        $this->deployedForm->operationsSignOff,
        $this->deployedForm->userAcceptance,
        $this->deployedForm->serviceDeliverySignOff,
        $this->deployedForm->changeAdvisorySignOff,
    ];

    if (in_array('pending', $signOffs) || in_array('rejected', $signOffs)) {
        $this->addError('deployed', 'All sign-offs must be approved before final approval.');
        return;
    }

    $this->project->deployed->update([
        'deployment_approved_status' => 'approved',
        'deployment_approved_at' => now(),
    ]);

    // Set project to COMPLETED
    $this->project->update([
        'status' => ProjectStatus::COMPLETED,
    ]);

    event(new \App\Events\DeploymentApproved($this->project));

    $this->dispatch('deployment-completed');
}
```

#### 5.4 Update View (`resources/views/livewire/forms/deployed-form.blade.php`)
Add service function display and new buttons:
```blade
<!-- Auto-populated Service/Function -->
<flux:text label="Service / Function">{{ $deployedForm->serviceFunction }}</flux:text>

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('deployed')">Update</flux:button>

    @if($deployedForm->serviceAcceptanceStatus === 'pending')
        <flux:button wire:click="acceptDeploymentService">
            Service Acceptance
        </flux:button>
    @endif

    @if($deployedForm->serviceAcceptanceStatus === 'approved')
        <flux:button wire:click="approveDeployment">
            Approved (Complete Project)
        </flux:button>
    @endif
</div>
```

#### 5.5 Write Tests
Test completeness validation, sign-off gating, project completion.

Run: `lando artisan test --filter=Deployed`

---

### Feature 6: Portfolio Outputs

**New Components to Create:**

#### 6.1 Backlog List Component

**Create:** `lando artisan make:livewire BacklogList`

**`app/Livewire/BacklogList.php`:**
```php
<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class BacklogList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public function render()
    {
        $projects = Project::query()
            ->with(['user', 'scoping', 'scheduling', 'ideation'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('livewire.backlog-list', [
            'projects' => $projects,
        ]);
    }

    public function openChangeOnAPage(int $projectId): void
    {
        $this->dispatch('open-change-modal', projectId: $projectId);
    }
}
```

**`resources/views/livewire/backlog-list.blade.php`:**
```blade
<div>
    <flux:heading>Project Backlog</flux:heading>

    <!-- Filters -->
    <div class="flex gap-4 mb-4">
        <flux:input wire:model.live="search" placeholder="Search projects..." />
        <flux:select wire:model.live="statusFilter">
            <option value="all">All Statuses</option>
            <!-- Add status options -->
        </flux:select>
    </div>

    <!-- Backlog Table -->
    <flux:table>
        <flux:columns>
            <flux:column>Ref #</flux:column>
            <flux:column>Status</flux:column>
            <flux:column>Deliverable</flux:column>
            <flux:column>Raised By</flux:column>
            <flux:column>Effort</flux:column>
            <flux:column>Technical Owner</flux:column>
            <flux:column>Delivery Date</flux:column>
            <flux:column>Champion</flux:column>
        </flux:columns>

        <flux:rows>
            @foreach($projects as $project)
                <flux:row
                    wire:click="openChangeOnAPage({{ $project->id }})"
                    class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <flux:cell>{{ $project->id }}</flux:cell>
                    <flux:cell>
                        <flux:badge>{{ $project->status->label() }}</flux:badge>
                    </flux:cell>
                    <flux:cell>{{ $project->title }}</flux:cell>
                    <flux:cell>{{ $project->user->name }}</flux:cell>
                    <flux:cell>{{ $project->scoping?->estimated_effort?->label() ?? 'Not Set' }}</flux:cell>
                    <flux:cell>{{ $project->scheduling?->assignedTo?->name ?? 'Unassigned' }}</flux:cell>
                    <flux:cell>{{ $project->scheduling?->estimated_completion_date?->format('d/m/Y') ?? 'TBD' }}</flux:cell>
                    <flux:cell>{{ $project->ideation?->school_group ?? 'N/A' }}</flux:cell>
                </flux:row>
            @endforeach
        </flux:rows>
    </flux:table>

    {{ $projects->links() }}
</div>
```

#### 6.2 Change on a Page Modal

**Create:** `lando artisan make:livewire ChangeOnAPage`

**`app/Livewire/ChangeOnAPage.php`:**
```php
<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ChangeOnAPage extends Component
{
    public ?Project $project = null;
    public bool $showModal = false;

    protected $listeners = ['open-change-modal' => 'openModal'];

    public function openModal(int $projectId): void
    {
        $this->project = Project::with([
            'user',
            'ideation',
            'scoping',
            'feasibility',
            'detailedDesign',
            'scheduling',
            'development',
            'testing',
            'deployed',
        ])->findOrFail($projectId);

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->project = null;
    }

    public function render()
    {
        return view('livewire.change-on-a-page');
    }
}
```

**View:** Create comprehensive summary card showing all stage data.

#### 6.3 Roadmap View

**Create:** `lando artisan make:livewire RoadmapView`

Group projects by service function, show timeline with BRAG status indicators.

**Logic for BRAG status:**
```php
private function calculateBRAGStatus(Project $project): string
{
    // Black = Completed
    if ($project->status === ProjectStatus::COMPLETED) {
        return 'black';
    }

    // Red = Overdue or blocked
    if ($project->scheduling?->estimated_completion_date?->isPast()) {
        return 'red';
    }

    // Amber = At risk (within 2 weeks of deadline)
    if ($project->scheduling?->estimated_completion_date?->diffInDays(now()) < 14) {
        return 'amber';
    }

    // Green = On track
    return 'green';
}
```

#### 6.4 Planning Heatmap

**Create:** `lando artisan make:livewire PlanningHeatmap`

Extend/duplicate `HeatMapViewer` to show projects at scoping/scheduling stages with effort scale and skill requirements overlay.

#### 6.5 Add Routes

**Update `routes/web.php`:**
```php
Route::middleware(['auth'])->group(function () {
    // ... existing routes

    Route::get('/backlog', BacklogList::class)->name('backlog.list');
    Route::get('/roadmap', RoadmapView::class)->name('roadmap.view');
    Route::get('/planning-heatmap', PlanningHeatmap::class)->name('planning.heatmap');
});
```

#### 6.6 Write Tests
Create `tests/Feature/PortfolioOutputsTest.php`:
```php
it('displays backlog projects', function () {
    $projects = Project::factory()->count(5)->create();

    $this->get(route('backlog.list'))
        ->assertOk()
        ->assertSeeLivewire(BacklogList::class)
        ->assertSee($projects->first()->title);
});

it('groups roadmap by function', function () {
    // Create projects with different functions
    $project1 = Project::factory()->create();
    $project1->user->update(['service_function' => 'Applications & Data']);

    $this->get(route('roadmap.view'))
        ->assertOk()
        ->assertSee('Applications & Data');
});

// etc.
```

Run: `lando artisan test --filter=PortfolioOutputs`

---

## 7. Phases 2-4: Future Work

### Phase 2 – Form & Validation Fixes (High Priority Usability)

Several Phase 2 items were naturally bundled into Phase 1 implementation:
- ✅ **Scheduling key skills field** - Completed in Feature 3
- ✅ **Button label changes** - All "Save" → "Update" changes applied throughout Features 1-5

**Remaining Phase 2 Items:**
1. **Date validation realism** - Adjust forms so stored past dates remain valid (e.g., `before_or_equal:today`)
2. **Enum safety in `ProjectEditor::save()`** - Validate `$formType` against known statuses before calling `ProjectStatus::from()` to avoid ValueErrors on crafted URLs
3. **Regression tests** - Expand existing Pest feature tests to cover restored fields and relaxed validation paths

### Phase 3 – Lightweight Authorization Guardrails (Moderate Priority)

1. **Implement `ProjectPolicy`** - Provide `view`, `update`, and `cancel` checks that allow project owners, assigned staff, or admins; default deny otherwise
2. **Route middleware alignment** - Apply `can:view,project` (viewer) and `can:update,project` (editor) to relevant routes
3. **Livewire component enforcement** - Call `$this->authorize()` inside `ProjectViewer`, `ProjectEditor`, and `ProjectStatusTable` actions
4. **Admin tab gating** - Mirror the `@admin` guard on panels or centralize checks
5. **Cancellation control** - Ensure `cancelProject()` verifies permissions before mutating records
6. **Targeted tests** - Add policy-focused feature tests ensuring unauthorized users receive 403 responses, balancing effort with "trusted LAN" context

### Phase 4 – Polish & Rollout

1. **Documentation & change log** - Update `README.md` or internal docs summarizing new workflows
2. **Styling & UX consistency** - Confirm Flux UI components follow design tokens; add helper text or tooltips where governance steps may confuse users
3. **Notifications audit** - Review listeners and mailables to guarantee new actions emit the right emails
4. **Testing and QA** - Run focused Pest suites per feature plus final `lando artisan test`; refresh seeded data via `lando db:seed --class=TestDataSeeder` and validate in-browser

---

## 8. Developer Reference: Patterns & Conventions ⭐

This section consolidates all lessons learned, common patterns, and gotchas discovered during development.

### Testing Patterns

**Use `data-test` attributes for UI testing:**
- More reliable than text matching (text can appear in stack traces)
- Example: `data-test="approve-feasibility-button"`
- Test with: `assertSeeHtml('data-test="approve-feasibility-button"')`

**Pre-assertions show state transitions:**
```php
// Assert initial state
expect($project->feasibility->isReadyForApproval())->toBeFalse();

// Act
$project->feasibility->update([...]);

// Assert final state
expect($project->feasibility->isReadyForApproval())->toBeTrue();
```

**Create setup helpers for complex forms:**
```php
beforeEach(function () {
    // Helper closure to pre-populate required fields
    $this->setupValidScheduling = function ($project) {
        $project->scheduling->update([
            'start_date' => now(),
            'completion_date' => now()->addDays(30),
            // ... other required fields
        ]);
    };
});
```

**Test behavior, not implementation:**
- Test what users see, not internal methods
- If a test breaks when you refactor without changing behavior, it's too coupled to implementation
- Example: Don't test `getAssignedStaffIds()` directly, test that staff appears correctly in heatmap

**Setup order matters with fakeNotifications():**
```php
// CORRECT ORDER:
beforeEach(function () {
    // 1. Create users and attach roles FIRST
    $this->user = User::factory()->create(['is_admin' => true]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->user->roles()->attach($adminRole);

    // 2. THEN call fakeNotifications()
    $this->fakeNotifications();
});

// INCORRECT ORDER (creates duplicate users):
beforeEach(function () {
    $this->fakeNotifications();  // ← Calls ensureProjectCreatedRoles() which creates admin user
    $this->user = User::factory()->create(['is_admin' => true]);  // ← Duplicate!
});
```

**Use `assertSeeInOrder` for display order:**
- Great for verifying items appear in correct sequence without knowing exact indices

### Livewire Patterns

**Enum validation pattern:**
```php
use Illuminate\Validation\Rule;

#[Validate]  // Empty attribute triggers real-time validation
public ?ChangeBoardOutcome $changeBoardOutcome = null;

public function rules(): array
{
    return [
        'changeBoardOutcome' => ['nullable', Rule::enum(ChangeBoardOutcome::class)],
    ];
}
```
Why this pattern? PHP attributes can't use Laravel Rule objects directly, so validation rule must be in `rules()` method.

**Computed properties for expensive operations:**
```php
use Livewire\Attributes\Computed;

#[Computed]
public function heatmapData(): array
{
    return [
        'days' => $this->upcomingWorkingDays(10),
        'staff' => $this->staffWithBusyness(...),
        // ... expensive operations cached until form changes
    ];
}
```

**Enum nullable handling:**
- Empty string from dropdown → null via Livewire EnumSynth
- Save using `?->value` to handle null gracefully: `'change_board_outcome' => $this->changeBoardOutcome?->value`

**Form Request classes:**
- Always use Form Request classes for validation (not inline validation in controllers)
- Include both validation rules and custom error messages

### Code Organization

**Fat models:**
- Helper methods and business logic on models
- Example: `isReadyForApproval()` helper method on Feasibility model

**Traits over service classes:**
- Team convention: Avoid service classes unless approved
- Use traits for shared behavior (e.g., `HasHeatmapData`, `CanCheckIfEdited`)

**Config-driven for fixed requirements:**
- Notification routing in `config/projman.php`
- Stage roles mapping
- DCGG email address

**Use idiomatic Laravel Collections:**
```php
// GOOD - Idiomatic Laravel Collections
return collect([
    $this->schedulingForm->assignedTo,
    $this->schedulingForm->technicalLeadId,
])
    ->merge($this->schedulingForm->coseItStaff ?? [])
    ->filter()
    ->unique()
    ->values()
    ->all();

// BAD - Raw array operations
return array_filter([
    $this->schedulingForm->assignedTo,
    ...($this->schedulingForm->coseItStaff ?? []),
]);
```

**Keep logic in components, not views:**
- Views should only display data with simple conditionals
- Complex logic in component methods
- Example: Return `hasAssignedStaff` boolean from component instead of complex `@if` in blade

### UI & Blade Conventions

**Flux components:**
- Use `<flux:fieldset>` not plain HTML `<fieldset>` for proper Flux integration
- Flux checkboxes: Use `label` attribute for cleaner markup
- Flux badge: Use `color` attribute (not `variant`), use color names (green, red) not semantic (success, danger)
- Flux button variants: Use `filled` (not `info`), `primary` (not `success`)

**Form partials:**
- All stage forms extracted to `resources/views/livewire/forms/` directory
- Main `project-editor.blade.php` just includes partials: `@include('livewire.forms.scheduling-form')`
- Benefits: Better navigation, cleaner git diffs, no merge conflicts

**Complex logic belongs in components:**
```blade
{{-- GOOD - Simple conditional --}}
@if($this->heatmapData['hasAssignedStaff'])
    <flux:text>Assigned staff shown at top</flux:text>
@endif

{{-- BAD - Complex logic in view --}}
@if(!empty(array_filter([$schedulingForm->assignedTo, $schedulingForm->technicalLeadId, ...])))
    <flux:text>...</flux:text>
@endif
```

### Common Gotchas

**Livewire EnumSynth throws ValueError before validation:**
- This is expected behavior and provides protection
- Livewire throws ValueError for invalid backing values before validation rules run
- Don't try to catch this - it's intentional

**Empty strings vs null for enums:**
- For enum properties, Livewire dropdowns send empty strings which EnumSynth converts to null
- For foreign key properties, use null directly

**Multi-column sort order:**
```php
// WRONG - Final sort wins, earlier sorts ignored
->sortByDesc('total_skill_score')  // Applied first
->sortBy('surname')                // Overwrites previous
->sortBy('forenames')              // Final (WRONG!)

// CORRECT - Apply least important first, most important last
->sortBy('forenames')              // Least important
->sortBy('surname')                // More important
->sortByDesc('total_skill_score')  // Most important (final)
```

**Database column modifications:**
- When modifying a column with migration, must include ALL previous attributes or they'll be dropped
- Example: Changing type of `estimated_effort` requires specifying nullable status again

**Don't remove team conventions selectively:**
- No `select()` calls to filter columns (validation hook will catch this)
- We don't micro-optimize queries in this application (small dataset)

### Event & Notification Patterns

**Discrete listeners per event:**
```php
// GOOD - One listener per event
class FeasibilityApprovedListener
{
    public function handle(FeasibilityApproved $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new RuntimeException('No recipients found...');
        }

        Mail::to($users->pluck('email'))->queue(new FeasibilityApprovedMail($event->project));
    }
}
```

**RoleUserResolver service:**
- Returns Collection of users for an event
- Handles role-based recipients, project owner inclusion, stage-specific roles
- Centralized logic instead of duplicating across listeners

**Fail-fast error handling:**
- Throw RuntimeException if no recipients found
- Descriptive message with event class and project ID
- Integrates with Sentry for production monitoring

**Config maps events to roles to mailables:**
```php
'notifications' => [
    \App\Events\FeasibilityApproved::class => [
        'roles' => ['Work Package Assessor'],
        'include_project_owner' => false,
        'mailable' => \App\Mail\FeasibilityApprovedMail::class,
    ],
    // ... other events
]
```

### When You're Stuck: Check the Original Spec

**Source material:** `pptx_text_extract.txt` in project root

Contains text extracted from the original PowerPoint requirements deck. Not always clear (it's a spec, after all), but sometimes has useful nuggets when feature requirements are ambiguous. Good for double-checking intended workflow or field names when spec seems unclear.

**Example use cases:**
- "Should this button be on Scoping or Scheduling?" → Check slides for that stage
- "What values should this dropdown have?" → Search for field name
- "Who should receive this notification?" → Look for stage-specific roles

---

## 9. Development Workflow

**Local development commands:**
- Use `lando` prefix for all Laravel commands: `lando artisan`, `lando test`, `lando composer`
- Direct PHP OK for code generation: `php artisan make:event EventName` (faster than spinning up Docker)

**Code formatting:**
- Run `vendor/bin/pint --dirty` before commits (or `lando pint --dirty`)
- Formats both PHP and Blade files
- Required before marking features complete

**Testing during development:**
- Use filters for speed: `lando artisan test --filter=FeatureName`
- Test specific file: `lando artisan test tests/Feature/ExampleTest.php`
- Before marking complete: Run full test suite `lando artisan test`

**Database seeding:**
- Use `TestDataSeeder` (not default seeder): `lando db:seed --class=TestDataSeeder`
- Creates realistic test data including notification roles
- Run `lando mfs` to fresh migrate and seed (alias for migrate:fresh --seed)

**Git commits:**
- Only create commits when user requests
- Follow existing commit message style in repo
- Always run Pint before committing
- Include co-author footer:
```
🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

---

## 10. Appendices

### A. Database Schema Reference

**Key Tables:**
- `projects` - Main project table with status enum
- `users` - Users with roles (many-to-many via `role_user`)
- `roles` - Notification recipients (singular names: "Work Package Assessor", "Service Lead")

**Stage Tables (one per project):**
- `ideations` (project_id FK)
- `feasibilities` (project_id FK, approval_status, actioned_by FK)
- `scopings` (project_id FK, estimated_effort enum, requires_software_dev boolean)
- `schedulings` (project_id FK, submitted_to_dcgg_at, submitted_to_dcgg_by FK, scheduled_at, technical_lead_id FK, change_champion_id FK, change_board_outcome enum)
- `detailed_designs` (project_id FK)
- `developments` (project_id FK)
- `builds` (project_id FK) - TBD fields
- `testings` (project_id FK, uat_tester_id FK, uat_approval_status, service_acceptance_status)
- `deployeds` (project_id FK, service_acceptance_status, deployment_approved_status)

### B. Event → Listener → Notification Map

**Feasibility Stage:**
- `FeasibilityApproved` → `FeasibilityApprovedListener` → Work Package Assessor
- `FeasibilityRejected` → `FeasibilityRejectedListener` → Project Owner

**Scoping Stage:**
- `ScopingSubmitted` → `ScopingSubmittedListener` → Work Package Assessor

**Scheduling Stage:**
- `SchedulingSubmittedToDCGG` → `SchedulingSubmittedToDCGGListener` → Work Package Assessor + DCGG email (special)
- `SchedulingScheduled` → `SchedulingScheduledListener` → Work Package Assessor

**Project Lifecycle:**
- `ProjectCreated` → `ProjectCreatedListener` → Admin + Project Manager roles
- `ProjectStageChange` → `ProjectStageChangeListener` → Stage-specific roles (per config)

### C. Enum Reference

**EffortScale** (Scoping estimated effort):
- Small (≤5 days)
- Medium (6-15 days)
- Large (16-30 days)
- X-Large (31-50 days)
- XX-Large (>50 days)

**ChangeBoardOutcome** (Scheduling Change Board result):
- Pending
- Approved
- Deferred
- Rejected

**ProjectStatus** (Project progression):
- Ideation → Feasibility → Scoping → Scheduling → Detailed Design → Development → Build → Testing → Deployed → Completed
- Cancelled (terminal status)

---

## Testing Strategy

After completing each feature:
1. Run feature-specific tests: `lando artisan test --filter=FeatureName`
2. Run full test suite: `lando artisan test`
3. Test in browser with seeded data: `lando db:seed --class=TestDataSeeder`
4. Verify emails in Mailhog/mail trap
5. Check notification dispatch in Horizon (if using queues)

---

**Document Version**: 2.0
**Last Updated**: 2025-11-21
**Status**: 371 tests passing (1,268 assertions)
