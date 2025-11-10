# Projman Delivery Plan

This document translates the current review findings and senior management requests into a practical, internally focused delivery plan. Because the app runs on the corporate LAN for trusted employees, we will prioritize feature work that leadership is actively requesting, while still addressing the most disruptive usability and authorization gaps.

## Guiding Principles
- **Leadership directives first**: Anything pulled from `pptx_text_extract.txt` directly unlocks governance reporting and must lead the roadmap.
- **Usability before hardening**: Validation bugs that block day-to-day edits get fixed ahead of theoretical exploit paths.
- **Right-sized authorization**: Even on a trusted LAN we should prevent obvious cross-user accidents, but we can lean on lightweight policies instead of building a fortress.
- **Stage-by-stage parity**: Every workflow change should touch both the Livewire form class in `app/Livewire/Forms/*` and the paired Blade panels in `resources/views/livewire/project-editor.blade.php`, plus the relevant models/events/notifications.

## Phase 1 – Deliver Leadership Workflows (Highest Priority)
1. **Feasibility approvals & rejection workflow**
   - Add new fields (`existing_solution`, `off_the_shelf_solution`, `reject_reason`) with validation updates in `FeasibilityForm`.
   - Introduce Approve/Reject actions that enforce “reject when an existing solution exists” and dispatch emails to the “Work Package Assessors” role (extend `ProjectStageChangeMail` or add a notification).
   - Update Blade tabs to show new questions, buttons, and status indicators; ensure tab labels read “Update”.
   - Tests: extend `ProjectEditingTest` (or add a new feature test) to cover approval/rejection paths and email notifications.

2. **Scoping effort scale & DCGG workflow**
   - Replace free-text effort estimation with an enum-backed dropdown (Small → XXL) in `ScopingForm`.
   - Capture competencies and add Model/Submit/Schedule actions that hook into the heatmap (may require extending `Project` model fields and `HeatMapViewer` component).
   - Introduce governance buttons tied to Digital Change Governance Group flow, triggering events/notifications as described.
   - Tests: ensure transitions set correct status flags and heatmap data.

3. **Scheduling stage triage inputs**
   - Reintroduce and expand fields for Technical Lead, Change Champion, Change Board outcome, and Change Board date locking on Model/Submit/Schedule actions.
   - Wire dropdown sources (enums or config) for board outcomes to keep data clean.
   - Ensure notifications go to Work Package Assessors per slides.

4. **Testing approvals & UAT capture**
   - Add UAT tester field plus approval toggles for UAT Approval and Service Acceptance in both form + Blade view.
   - Enforce “all approvals collected before progressing” inside Livewire actions.
   - Notify UAT tester and Service Leads upon submission.

5. **Deployment acceptance workflow**
   - Add Service Acceptance + Approved buttons gated by completeness checks.
   - Auto-populate Service/Function display (likely from Project or Role data) and change primary CTA labels to “Update”.
   - Tie Accepted/Approved actions to status transitions and notifications to Service Leads.

6. **Portfolio outputs**
   - Build new Livewire components and routes for:
     - Backlog list with “Change on a Page” drill-down.
     - Roadmap grouped by function.
     - Planning heatmap aligned with scoping/scheduling data.
   - Ensure data is exposed via queries optimized for these dashboards.
   - Tests: coverage for each component’s data filters and visibility.

## Phase 2 – Form & Validation Fixes (High Priority Usability)
1. **Scheduling key skills field**
   - Restore the field in the Blade template and keep the `required|string|max:1024` rule, or relax the rule if leadership prefers optional input.
2. **Date validation realism**
   - Adjust `FeasibilityForm`, `SchedulingForm`, and other affected forms so stored past dates remain valid (e.g., `before_or_equal:today`).
3. **Button labels**
   - Rename every stage “Save” button to “Update” per slides; confirm localization or constants if needed.
4. **Enum safety in `ProjectEditor::save()`**
   - Validate `$formType` against known statuses before calling `ProjectStatus::from()` to avoid ValueErrors on crafted URLs.
5. **Regression tests**
   - Expand existing Pest feature tests to cover the restored fields and relaxed validation paths.

## Phase 3 – Lightweight Authorization Guardrails (Moderate Priority)
1. **Implement `ProjectPolicy`**
   - Provide `view`, `update`, and `cancel` checks that allow project owners, assigned staff, or admins; default deny otherwise.
2. **Route middleware alignment**
   - Apply `can:view,project` (viewer) and `can:update,project` (editor) to relevant routes in `routes/web.php`.
3. **Livewire component enforcement**
   - Call `$this->authorize()` inside `ProjectViewer`, `ProjectEditor`, and `ProjectStatusTable` actions to prevent accidental cross-project edits.
4. **Admin tab gating**
   - Mirror the `@admin` guard on panels or centralize checks so only authorized roles can trigger stage-specific actions.
5. **Cancellation control**
   - Ensure `cancelProject()` verifies permissions before mutating records.
6. **Targeted tests**
   - Add policy-focused feature tests ensuring unauthorized users receive 403 responses, balancing effort with our “trusted LAN” context.

## Phase 4 – Polish & Rollout
1. **Documentation & change log**
   - Update `README.md` or internal docs summarizing new workflows so staff know how to use the buttons and dashboards.
2. **Styling & UX consistency**
   - Confirm Flux UI components follow design tokens; add helper text or tooltips where governance steps may confuse users.
3. **Notifications audit**
   - Review `ProjectEventsListener` and mailables to guarantee new actions emit the right emails.
4. **Testing and QA**
   - Run focused Pest suites per feature plus a final `php artisan test`.
   - If dashboards rely on seeded data, refresh via `lando mfs` and validate in-browser.

## Ordering & Timeline
1. **Week 1-2**: Implement Feasibility + Scoping workflows; merge once tests pass.
2. **Week 2-3**: Scheduling + Testing + Deployment enhancements plus button renames.
3. **Week 3-4**: Build portfolio dashboards and heatmap changes (may require additional UX review).
4. **Week 4**: Address lingering validation bugs, enum safety, and add lightweight policies.
5. **Final week**: Documentation, QA, and stakeholder demos.

By following this plan we satisfy the most visible leadership asks first, unblock daily usage through validation fixes, and still add enough authorization to prevent accidental misuse inside the trusted network.

---

## Phase 1 Implementation Details

This section provides step-by-step implementation guidance for all 6 Phase 1 features, informed by thorough codebase analysis.

### Prerequisites & Infrastructure Setup (Days 1-2)

Before implementing features, establish the foundation:

#### 1. Create New Enums

**`app/Enums/EffortScale.php`** (for Feature 2)
```php
<?php

namespace App\Enums;

enum EffortScale: string
{
    case SMALL = 'small';          // ≤5 days
    case MEDIUM = 'medium';        // >5 ≤15 days
    case LARGE = 'large';          // >15 ≤30 days
    case X_LARGE = 'xlarge';       // >30 ≤50 days
    case XX_LARGE = 'xxlarge';     // >50 days

    public function label(): string
    {
        return match($this) {
            self::SMALL => 'Small (≤5 days)',
            self::MEDIUM => 'Medium (6-15 days)',
            self::LARGE => 'Large (16-30 days)',
            self::X_LARGE => 'X-Large (31-50 days)',
            self::XX_LARGE => 'XX-Large (>50 days)',
        };
    }
}
```

**`app/Enums/ChangeBoardOutcome.php`** (for Feature 3)
```php
<?php

namespace App\Enums;

enum ChangeBoardOutcome: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DEFERRED = 'deferred';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
```

#### 2. Create Database Migrations

Run: `lando artisan make:migration add_feasibility_approval_fields --table=feasibilities`

**Migration 1: Feasibility fields**
```php
Schema::table('feasibilities', function (Blueprint $table) {
    $table->text('existing_solution')->nullable()->after('alternative_proposal');
    $table->text('off_the_shelf_solution')->nullable()->after('existing_solution');
    $table->text('reject_reason')->nullable()->after('off_the_shelf_solution');
    $table->string('approval_status')->default('pending')->after('reject_reason');
    $table->timestamp('approved_at')->nullable()->after('approval_status');
});
```

**Migration 2: Scoping effort & DCGG**
```php
Schema::table('scopings', function (Blueprint $table) {
    // Change estimated_effort to enum-compatible string
    $table->string('estimated_effort')->nullable()->change();
    $table->string('dcgg_status')->default('pending')->after('skills_required');
    $table->timestamp('submitted_to_dcgg_at')->nullable()->after('dcgg_status');
    $table->timestamp('scheduled_at')->nullable()->after('submitted_to_dcgg_at');
});
```

**Migration 3: Scheduling triage fields**
```php
Schema::table('schedulings', function (Blueprint $table) {
    $table->foreignId('technical_lead_id')->nullable()->constrained('users')->after('assigned_to');
    $table->foreignId('change_champion_id')->nullable()->constrained('users')->after('technical_lead_id');
    $table->string('change_board_outcome')->nullable()->after('change_champion_id');
    $table->boolean('fields_locked')->default(false)->after('change_board_outcome');
});
```

**Migration 4: Testing UAT & approvals**
```php
Schema::table('testings', function (Blueprint $table) {
    $table->foreignId('uat_tester_id')->nullable()->constrained('users')->after('test_lead');
    $table->string('uat_approval_status')->default('pending')->after('service_resilience_sign_off');
    $table->timestamp('uat_approved_at')->nullable()->after('uat_approval_status');
    $table->string('service_acceptance_status')->default('pending')->after('uat_approved_at');
    $table->timestamp('service_accepted_at')->nullable()->after('service_acceptance_status');
});
```

**Migration 5: Deployment acceptance**
```php
Schema::table('deployeds', function (Blueprint $table) {
    $table->string('service_function')->nullable()->after('deployed_by');
    $table->string('service_acceptance_status')->default('pending')->after('change_advisory_sign_off');
    $table->timestamp('service_accepted_at')->nullable()->after('service_acceptance_status');
    $table->string('deployment_approved_status')->default('pending')->after('service_accepted_at');
    $table->timestamp('deployment_approved_at')->nullable()->after('deployment_approved_status');
});
```

**Migration 6: User service function**
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('service_function')->nullable()->after('is_admin');
});
```

Run: `lando artisan migrate`

#### 3. Seed Required Roles

Update `database/seeders/TestDataSeeder.php` to ensure these roles exist:
```php
Role::firstOrCreate(['name' => 'Work Package Assessors']);
Role::firstOrCreate(['name' => 'Service Leads']);
```

Run: `lando db:seed --class=TestDataSeeder`

---

### Feature 1: Feasibility Approvals & Rejection Workflow (Days 3-4)

**Current files:**
- Model: `app/Models/Feasibility.php`
- Form: `app/Livewire/Forms/FeasibilityForm.php`
- Component: `app/Livewire/ProjectEditor.php`
- View: `resources/views/livewire/project-editor.blade.php` (lines 72-122)

**Changes Required:**

#### 1.1 Update Model (`app/Models/Feasibility.php`)
Add to `$fillable`:
```php
protected $fillable = [
    // ... existing fields
    'existing_solution',
    'off_the_shelf_solution',
    'reject_reason',
    'approval_status',
    'approved_at',
];

protected $casts = [
    // ... existing casts
    'approved_at' => 'datetime',
];
```

#### 1.2 Update Form (`app/Livewire/Forms/FeasibilityForm.php`)
Add properties:
```php
public ?string $existingSolution = null;
public ?string $offTheShelfSolution = null;
public ?string $rejectReason = null;
public string $approvalStatus = 'pending';
```

Update `rules()`:
```php
'existingSolution' => 'nullable|string|max:10000',
'offTheShelfSolution' => 'nullable|string|max:10000',
'rejectReason' => 'nullable|string|max:5000',
'approvalStatus' => 'in:pending,approved,rejected',
```

Update `setProject()`:
```php
$this->existingSolution = $feasibility->existing_solution;
$this->offTheShelfSolution = $feasibility->off_the_shelf_solution;
$this->rejectReason = $feasibility->reject_reason;
$this->approvalStatus = $feasibility->approval_status;
```

Update `save()`:
```php
$feasibility->update([
    // ... existing fields
    'existing_solution' => $this->existingSolution,
    'off_the_shelf_solution' => $this->offTheShelfSolution,
    'reject_reason' => $this->rejectReason,
    'approval_status' => $this->approvalStatus,
]);
```

#### 1.3 Add Component Actions (`app/Livewire/ProjectEditor.php`)
```php
public function approveFeasibility(): void
{
    // Validate existing solution doesn't exist
    if (!empty($this->feasibilityForm->existingSolution)) {
        $this->addError('feasibility', 'Cannot approve when an existing solution is identified. Please reject instead.');
        return;
    }

    $this->feasibilityForm->validate();

    $this->feasibilityForm->approvalStatus = 'approved';
    $this->feasibilityForm->save($this->project->feasibility);

    $this->project->feasibility->update([
        'approved_at' => now(),
    ]);

    // Notify Work Package Assessors
    event(new \App\Events\FeasibilityApproved($this->project));

    $this->dispatch('feasibility-approved');
}

public function rejectFeasibility(): void
{
    $this->validate([
        'feasibilityForm.rejectReason' => 'required|string|max:5000',
    ]);

    $this->feasibilityForm->approvalStatus = 'rejected';
    $this->feasibilityForm->save($this->project->feasibility);

    // Notify submitter
    event(new \App\Events\FeasibilityRejected($this->project));

    $this->dispatch('feasibility-rejected');
}
```

#### 1.4 Create Events

**`app/Events/FeasibilityApproved.php`**
```php
<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeasibilityApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public Project $project) {}
}
```

**`app/Events/FeasibilityRejected.php`** (similar structure)

#### 1.5 Update Listener (`app/Listeners/ProjectEventsListener.php`)
Add methods to handle new events:
```php
public function handleFeasibilityApproved(FeasibilityApproved $event): void
{
    $this->notifyRoles($event->project, ['Work Package Assessors'], 'feasibility_approved');
}

public function handleFeasibilityRejected(FeasibilityRejected $event): void
{
    $this->notifyUser($event->project->user, $event->project, 'feasibility_rejected');
}
```

Register in `EventServiceProvider`:
```php
FeasibilityApproved::class => [ProjectEventsListener::class],
FeasibilityRejected::class => [ProjectEventsListener::class],
```

#### 1.6 Update View (`resources/views/livewire/project-editor.blade.php`)
Replace Feasibility section (lines 72-122):
```blade
<!-- Existing Solution -->
<flux:field>
    <flux:label>Is there an existing solution that meets the need?</flux:label>
    <flux:textarea wire:model="feasibilityForm.existingSolution" rows="4" />
    <flux:error name="feasibilityForm.existingSolution" />
</flux:field>

<!-- Off-the-Shelf Solution -->
<flux:field>
    <flux:label>Is there an off-the-shelf solution available?</flux:label>
    <flux:textarea wire:model="feasibilityForm.offTheShelfSolution" rows="4" />
    <flux:error name="feasibilityForm.offTheShelfSolution" />
</flux:field>

<!-- Status Badge -->
@if($feasibilityForm->approvalStatus !== 'pending')
    <flux:badge :variant="$feasibilityForm->approvalStatus === 'approved' ? 'success' : 'danger'">
        {{ ucfirst($feasibilityForm->approvalStatus) }}
    </flux:badge>
@endif

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('feasibility')" variant="primary">Update</flux:button>

    @if($feasibilityForm->approvalStatus === 'pending')
        <flux:button
            wire:click="approveFeasibility"
            variant="success"
            :disabled="!empty($feasibilityForm->existingSolution)">
            Approve
        </flux:button>
        <flux:button wire:click="$dispatch('open-reject-modal')" variant="danger">
            Reject
        </flux:button>
    @endif
</div>

<!-- Reject Modal -->
<flux:modal name="reject-modal" wire:key="reject-feasibility-modal">
    <flux:heading>Reject Feasibility</flux:heading>
    <flux:field>
        <flux:label>Reason for Rejection</flux:label>
        <flux:textarea wire:model="feasibilityForm.rejectReason" rows="6" required />
        <flux:error name="feasibilityForm.rejectReason" />
    </flux:field>
    <flux:button wire:click="rejectFeasibility" variant="danger">Confirm Rejection</flux:button>
</flux:modal>
```

#### 1.7 Write Tests (`tests/Feature/FeasibilityApprovalTest.php`)
```php
it('approves feasibility when no existing solution exists', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectEditor::class, ['project' => $project])
        ->set('feasibilityForm.existingSolution', null)
        ->call('approveFeasibility')
        ->assertHasNoErrors()
        ->assertDispatched('feasibility-approved');

    expect($project->fresh()->feasibility->approval_status)->toBe('approved');
});

it('prevents approval when existing solution is identified', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectEditor::class, ['project' => $project])
        ->set('feasibilityForm.existingSolution', 'We already have System X')
        ->call('approveFeasibility')
        ->assertHasErrors('feasibility');
});

it('requires reject reason when rejecting', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectEditor::class, ['project' => $project])
        ->set('feasibilityForm.rejectReason', null)
        ->call('rejectFeasibility')
        ->assertHasErrors('feasibilityForm.rejectReason');
});

it('notifies Work Package Assessors on approval', function () {
    Event::fake([FeasibilityApproved::class]);

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectEditor::class, ['project' => $project])
        ->call('approveFeasibility');

    Event::assertDispatched(FeasibilityApproved::class);
});
```

Run: `lando artisan test --filter=FeasibilityApproval`

---

### Feature 2: Scoping Effort Scale & DCGG Workflow (Days 5-6)

**Current files:**
- Model: `app/Models/Scoping.php`
- Form: `app/Livewire/Forms/ScopingForm.php`
- View: `resources/views/livewire/project-editor.blade.php` (lines 374-416)
- HeatMap: `app/Livewire/HeatMapViewer.php`

**Changes Required:**

#### 2.1 Update Model (`app/Models/Scoping.php`)
Add to `$fillable`:
```php
'dcgg_status',
'submitted_to_dcgg_at',
'scheduled_at',
```

Add casts:
```php
protected function casts(): array
{
    return [
        // ... existing
        'estimated_effort' => EffortScale::class,
        'submitted_to_dcgg_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];
}
```

#### 2.2 Update Form (`app/Livewire/Forms/ScopingForm.php`)
Change property type:
```php
use App\Enums\EffortScale;

public ?EffortScale $estimatedEffort = null;
public string $dcggStatus = 'pending';
public ?Carbon $submittedToDcggAt = null;
public ?Carbon $scheduledAt = null;
```

Update validation:
```php
'estimatedEffort' => ['required', Rule::enum(EffortScale::class)],
'dcggStatus' => 'in:pending,submitted,approved',
```

#### 2.3 Add Component Actions (`app/Livewire/ProjectEditor.php`)
```php
public function modelScoping(): void
{
    // Validate assigned_to exists in scheduling stage
    if (empty($this->project->scheduling?->assigned_to)) {
        $this->addError('scoping', 'Please assign team members in Scheduling before modeling.');
        return;
    }

    // Redirect to heatmap with context
    return redirect()->route('staff.heatmap', ['highlight' => $this->project->id]);
}

public function submitToDCGG(): void
{
    $this->scopingForm->validate();

    $this->project->scoping->update([
        'dcgg_status' => 'submitted',
        'submitted_to_dcgg_at' => now(),
    ]);

    event(new \App\Events\ScopingSubmittedToDCGG($this->project));

    $this->dispatch('scoping-submitted');
}

public function scheduleScoping(): void
{
    // Validate Change Board date exists
    if (empty($this->project->scheduling?->change_board_date)) {
        $this->addError('scoping', 'Change Board date must be set in Scheduling stage.');
        return;
    }

    $this->project->scoping->update([
        'dcgg_status' => 'approved',
        'scheduled_at' => now(),
    ]);

    event(new \App\Events\ScopingScheduled($this->project));

    $this->dispatch('scoping-scheduled');
}
```

#### 2.4 Update View (Scoping section)
Replace estimated_effort textarea with dropdown:
```blade
<flux:field>
    <flux:label>Estimated Effort</flux:label>
    <flux:select wire:model="scopingForm.estimatedEffort" required>
        <option value="">Select effort scale...</option>
        @foreach(\App\Enums\EffortScale::cases() as $scale)
            <option value="{{ $scale->value }}">{{ $scale->label() }}</option>
        @endforeach
    </flux:select>
    <flux:error name="scopingForm.estimatedEffort" />
</flux:field>

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('scoping')" variant="primary">Update</flux:button>
    <flux:button wire:click="modelScoping" variant="secondary">Model</flux:button>

    @if($scopingForm->dcggStatus === 'pending')
        <flux:button wire:click="submitToDCGG" variant="info">Submit to DCGG</flux:button>
    @endif

    @if($scopingForm->dcggStatus === 'submitted')
        <flux:button wire:click="scheduleScoping" variant="success">Schedule</flux:button>
    @endif
</div>
```

#### 2.5 Create Events and Tests
Similar pattern to Feature 1 - create events, update listener, write comprehensive tests.

Run: `lando artisan test --filter=Scoping`

---

### Feature 3: Scheduling Stage Triage Inputs (Days 7-8)

**Changes Required:**

#### 3.1 Update Model (`app/Models/Scheduling.php`)
Add relationships:
```php
public function technicalLead(): BelongsTo
{
    return $this->belongsTo(User::class, 'technical_lead_id');
}

public function changeChampion(): BelongsTo
{
    return $this->belongsTo(User::class, 'change_champion_id');
}
```

Add to casts:
```php
'change_board_outcome' => ChangeBoardOutcome::class,
'fields_locked' => 'boolean',
```

#### 3.2 Update Form (`app/Livewire/Forms/SchedulingForm.php`)
Restore and add properties:
```php
public ?string $keySkills = null;  // RESTORE THIS (Phase 2 fix)
public ?int $technicalLeadId = null;
public ?int $changeChampionId = null;
public ?ChangeBoardOutcome $changeBoardOutcome = null;
public bool $fieldsLocked = false;
```

Update validation:
```php
'keySkills' => 'required|string|max:1024',  // RESTORED
'technicalLeadId' => 'nullable|exists:users,id',
'changeChampionId' => 'nullable|exists:users,id',
'changeBoardOutcome' => ['nullable', Rule::enum(ChangeBoardOutcome::class)],
```

#### 3.3 Update View
Uncomment key_skills field (line 279) and add new fields:
```blade
<!-- RESTORE Key Skills -->
<flux:field>
    <flux:label>Key Skills Required</flux:label>
    <flux:textarea
        wire:model="schedulingForm.keySkills"
        rows="3"
        :disabled="$schedulingForm->fieldsLocked"
        required />
    <flux:error name="schedulingForm.keySkills" />
</flux:field>

<!-- Technical Lead -->
<flux:field>
    <flux:label>Technical Lead</flux:label>
    <flux:select
        wire:model="schedulingForm.technicalLeadId"
        :disabled="$schedulingForm->fieldsLocked">
        <option value="">Select technical lead...</option>
        @foreach($staffUsers as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
        @endforeach
    </flux:select>
</flux:field>

<!-- Change Champion -->
<flux:field>
    <flux:label>Change Champion</flux:label>
    <flux:select
        wire:model="schedulingForm.changeChampionId"
        :disabled="$schedulingForm->fieldsLocked">
        <option value="">Select change champion...</option>
        @foreach($staffUsers as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
        @endforeach
    </flux:select>
</flux:field>

<!-- Change Board Outcome -->
<flux:field>
    <flux:label>Change Board Outcome</flux:label>
    <flux:select
        wire:model="schedulingForm.changeBoardOutcome"
        :disabled="$schedulingForm->fieldsLocked">
        <option value="">Pending...</option>
        @foreach(\App\Enums\ChangeBoardOutcome::cases() as $outcome)
            <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
        @endforeach
    </flux:select>
</flux:field>
```

#### 3.4 Add Field Locking
In component actions (Model/Submit/Schedule), lock fields:
```php
$this->project->scheduling->update(['fields_locked' => true]);
```

#### 3.5 Write Tests
Test key_skills restoration, new field persistence, field locking behavior.

Run: `lando artisan test --filter=Scheduling`

---

### Feature 4: Testing Approvals & UAT Capture (Day 9)

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

#### 4.3 Add Component Actions
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

#### 4.4 Update View
Add UAT Tester field and new action buttons:
```blade
<flux:field>
    <flux:label>UAT Tester</flux:label>
    <flux:select wire:model="testingForm.uatTesterId">
        <option value="">Select UAT tester...</option>
        @foreach($staffUsers as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
        @endforeach
    </flux:select>
</flux:field>

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('testing')">Update</flux:button>

    @if($testingForm->uatApprovalStatus === 'pending')
        <flux:button wire:click="approveUAT" variant="info">UAT Approval</flux:button>
    @endif

    @if($testingForm->uatApprovalStatus === 'approved')
        <flux:button wire:click="acceptService" variant="success">Service Acceptance</flux:button>
    @endif
</div>
```

#### 4.5 Write Tests
Test UAT approval workflow, service acceptance gating, email notifications.

Run: `lando artisan test --filter=Testing`

---

### Feature 5: Deployment Acceptance Workflow (Day 10)

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

#### 5.3 Add Component Actions
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

#### 5.4 Update View
Add service function display and new buttons:
```blade
<!-- Auto-populated Service/Function -->
<flux:field>
    <flux:label>Service / Function</flux:label>
    <flux:text>{{ $deployedForm->serviceFunction }}</flux:text>
</flux:field>

<!-- Action Buttons -->
<div class="flex gap-2">
    <flux:button wire:click="save('deployed')">Update</flux:button>

    @if($deployedForm->serviceAcceptanceStatus === 'pending')
        <flux:button wire:click="acceptDeploymentService" variant="info">
            Service Acceptance
        </flux:button>
    @endif

    @if($deployedForm->serviceAcceptanceStatus === 'approved')
        <flux:button wire:click="approveDeployment" variant="success">
            Approved (Complete Project)
        </flux:button>
    @endif
</div>
```

#### 5.5 Write Tests
Test completeness validation, sign-off gating, project completion.

Run: `lando artisan test --filter=Deployed`

---

### Feature 6: Portfolio Outputs (Weeks 3-4)

**New Components to Create:**

#### 6.1 Backlog List Component

**Create:** `lando artisan make:livewire BacklogList`

**`app/Livewire/BacklogList.php`:**
```php
<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
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

### Phase 2 Items Bundled Into Phase 1

Several Phase 2 fixes are naturally bundled into Phase 1 implementation:

1. **Scheduling key_skills restoration** - Completed in Feature 3
2. **Button label changes** - All "Save" → "Update" changes applied throughout Features 1-5
3. **Date validation relaxation** - Can be addressed when updating forms (change `after:today` to `nullable|after:today` or `before_or_equal:today` for historical dates)

---

### Testing Strategy

After completing each feature:
1. Run feature-specific tests: `lando artisan test --filter=FeatureName`
2. Run full test suite: `lando artisan test`
3. Test in browser with seeded data: `lando db:seed --class=TestDataSeeder`
4. Verify emails in Mailhog/mail trap
5. Check notification dispatch in Horizon (if using queues)

---

### Dependencies Summary

**Critical Path:**
1. Infrastructure (Day 1-2) → BLOCKS ALL
2. Feature 1 (Day 3-4) → Sets pattern for others
3. Feature 2 (Day 5-6) → Parallel with Feature 1 after infrastructure
4. Features 3, 4, 5 (Day 7-10) → Can be parallel after Features 1-2 establish patterns
5. Feature 6 (Week 3-4) → BLOCKED BY Features 1-5 (needs their data)

**No Blockers Between:**
- Features 1 & 2 (can be parallel)
- Features 3, 4, 5 (can be parallel after 1-2 done)

**Strongly Recommended Order:**
Infrastructure → Feasibility → Scoping → Scheduling → Testing → Deployed → Portfolio Outputs

This ensures consistent patterns and allows early feedback on the governance workflow before building dashboards.
