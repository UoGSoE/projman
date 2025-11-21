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

## Implementation Progress

### Completed: Infrastructure Setup ✓

**Date Completed:** 2025-11-10

**Enums Created:**
- ✅ `app/Enums/EffortScale.php` - Effort scale enum (Small through XX-Large) with `label()` and `daysRange()` methods
- ✅ `app/Enums/ChangeBoardOutcome.php` - Change board outcome enum (Pending, Approved, Deferred, Rejected)

**Database Migrations Created & Run:**
1. ✅ `2025_11_10_124600_add_feasibility_approval_fields.php`
   - Fields: `existing_solution`, `off_the_shelf_solution`, `reject_reason`, `approval_status`, `approved_at`, `actioned_by` (foreign key to users)
   - **Note:** Added `actioned_by` field for audit trail (not in original plan)

2. ✅ `2025_11_10_125118_add_scoping_effort_and_dcgg_fields.php`
   - Changed `estimated_effort` from text to string for enum compatibility
   - Fields: `dcgg_status`, `submitted_to_dcgg_at`, `scheduled_at`

3. ✅ `2025_11_10_125223_add_scheduling_triage_fields.php`
   - Fields: `technical_lead_id`, `change_champion_id`, `change_board_outcome`, `fields_locked`

4. ✅ `2025_11_10_125305_add_testing_uat_and_approvals.php`
   - Fields: `uat_tester_id`, `uat_approval_status`, `uat_approved_at`, `service_acceptance_status`, `service_accepted_at`

5. ✅ `2025_11_10_125342_add_deployment_acceptance_fields.php`
   - Fields: `service_function`, `service_acceptance_status`, `service_accepted_at`, `deployment_approved_status`, `deployment_approved_at`

6. ✅ `2025_11_10_125431_add_service_function_to_users.php`
   - Field: `service_function` on users table

**Seeder Updates:**
- ✅ Updated `database/seeders/TestDataSeeder.php` to include:
  - **"Work Package Assessor"** role (singular)
  - **"Service Lead"** role (singular)

**Important Notes:**
- Role names in database are **singular**: "Work Package Assessor" and "Service Lead" (not "Assessors" or "Leads")
- All migrations have proper `down()` methods with `dropConstrainedForeignId()` for foreign keys
- `actioned_by` field added to feasibility for better audit trail of who approved/rejected

---

### Completed: Feature 1 - Feasibility Approvals & Rejection Workflow ✓

**Date Completed:** 2025-11-11

**Models & Database:**
- ✅ Updated `app/Models/Feasibility.php` with new fillable fields and casts
- ✅ Added `actionedBy()` relationship to track who approved/rejected

**Forms & Validation:**
- ✅ Extended `app/Livewire/Forms/FeasibilityForm.php` with:
  - `existingSolution`, `offTheShelfSolution`, `rejectReason` properties
  - `approvalStatus` property (pending/approved/rejected)
  - Validation rules for new fields (nullable text fields up to 10,000 chars)
  - Sanitization for all new text inputs

**Livewire Component Actions:**
- ✅ Added `approveFeasibility()` method to `app/Livewire/ProjectEditor.php`
  - Blocks approval if existing solution is identified
  - Updates approval status and timestamps
  - Records actioned_by for audit trail
  - Dispatches FeasibilityApproved event
- ✅ Added `rejectFeasibility()` method to `app/Livewire/ProjectEditor.php`
  - Requires rejection reason (validated)
  - Updates rejection status and reason
  - Closes modal after successful rejection
  - Dispatches FeasibilityRejected event

**Events & Notifications:**
- ✅ Created `app/Events/FeasibilityApproved.php`
- ✅ Created `app/Events/FeasibilityRejected.php`
- ✅ Created `app/Mail/FeasibilityApprovedMail.php` with email template
- ✅ Created `app/Mail/FeasibilityRejectedMail.php` with email template
- ✅ Registered both events in `config/notifiable_events.php`
- ✅ Updated `app/Listeners/ProjectEventsListener.php` with handler methods
- ✅ Integrated with existing NotificationRule system for flexible recipient targeting

**UI Updates:**
- ✅ Updated `resources/views/livewire/project-editor.blade.php` feasibility panel:
  - Added "Existing Solution" textarea field
  - Added "Off-the-Shelf Solution" textarea field
  - Added approval status badge (shows "Approved" or "Rejected" when not pending)
  - Changed "Save" button to "Update" button
  - Added "Approve" button (disabled when existing solution identified)
  - Added "Reject" button that triggers modal
- ✅ Created rejection modal using Flux components:
  - Clean modal with heading and description
  - Textarea for rejection reason (required)
  - Cancel and Confirm buttons

**Testing:**
- ✅ Created `tests/Feature/FeasibilityApprovalTest.php` with 14 comprehensive tests:
  - Approval workflow (happy path)
  - Prevention when existing solution exists
  - Rejection validation (requires reason)
  - Successful rejection with reason
  - Event dispatching for both actions
  - Email notifications to correct roles
  - History recording for both actions
  - Project isolation (no cross-project effects)
  - UI badge visibility for approved/rejected states
  - Field persistence through save action
- ✅ All 14 tests passing (31 assertions)

**Code Quality:**
- ✅ Ran Laravel Pint for code formatting compliance
- ✅ Followed existing codebase conventions
- ✅ Used Flux UI components correctly (no invalid variants)
- ✅ Proper modal handling with `$this->modal()->close()`

**Key Implementation Notes:**
- Approval status defaults to 'pending' for all records
- Business rule enforced: cannot approve if existing solution is identified
- Rejection requires a reason to be provided
- Both actions record who performed them via `actioned_by` field
- Integrated with project history tracking
- Works with existing notification rules for flexible email routing
- Email subjects include project title for easy identification
- Added `isReadyForApproval()` helper method to gate approve/reject buttons until form is complete

**Testing Best Practices Learned:**
- **Use `data-test` attributes for UI testing**: Added `data-test="approve-feasibility-button"` and `data-test="reject-feasibility-button"` to make tests resilient to UI changes. Testing with `assertSeeHtml('data-test="..."')` is more reliable than `assertSee('Button Text')` because:
  - Button text might appear elsewhere in the page (e.g., in status badges like "Approved")
  - Data attributes are specifically for testing and won't accidentally match other content
  - They're less likely to change when developers modify the UI styling or wording
- **Use pre-assertions to show state transitions**: In tests that verify a state change, assert the initial state first, then perform the action, then assert the final state. Example:
  ```php
  // Assert - initially not ready
  expect($project->feasibility->isReadyForApproval())->toBeFalse();

  // Act - fill all required fields
  $project->feasibility->update([...]);
  $project->feasibility->refresh();

  // Assert - now ready
  expect($project->feasibility->isReadyForApproval())->toBeTrue();
  ```
  This makes it crystal clear what the test is verifying and that the action is what triggers the change.

**Next Steps:**
- Feature 2: Scoping Effort Scale & DCGG Workflow

---

### Completed: Notification System Refactor ✓

**Date Completed:** 2025-11-18

**Context:**
The application originally used a complex database-driven notification rules engine with admin UI, JSON configuration, and queue jobs. This was over-engineered for the actual use case: sending a fixed set of governance emails to predetermined roles. The refactor simplified the entire notification pipeline to use a config-driven approach.

**Problem Statement:**
- Notification system required database seeds, admin UI maintenance, and multiple query/dispatch layers
- Missing configuration for scoping events meant emails weren't being sent
- High complexity (700+ lines of code) for simple, fixed notification requirements
- Database dependency created failure points (missing seeds, inactive rules)

**Solution Implemented:**
Replaced the entire notification rules infrastructure with a simple config-based system in `config/projman.php`.

**Files Created:**
- ✅ Added `notifications` array to `config/projman.php` - Maps events → roles → mailables
- ✅ `app/Mail/ScopingSubmittedMail.php` - Markdown mailable for DCGG submission
- ✅ `app/Mail/ScopingScheduledMail.php` - Markdown mailable for scoping schedule
- ✅ `resources/views/emails/scoping_submitted.blade.php` - Email template
- ✅ `resources/views/emails/scoping_scheduled.blade.php` - Email template
- ✅ `tests/Feature/NotificationTest.php` - 8 comprehensive tests for config-based notifications

**Files Simplified:**
- ✅ `app/Listeners/ProjectEventsListener.php` - Direct config lookup, role-based recipient resolution
- ✅ `app/Listeners/ScopingSubmittedToDCGGListener.php` - Simplified to read from config
- ✅ `app/Listeners/ScopingScheduledListener.php` - Simplified to read from config
- ✅ Updated `app/Mail/*Mail.php` - Changed `protected` to `public` for Blade template access

**Files Deleted:**
- ✅ `app/Models/NotificationRule.php` - Entire model removed
- ✅ `database/factories/NotificationRuleFactory.php` - Factory removed
- ✅ `database/migrations/2025_10_15_161248_create_notification_rules_table.php` - Migration removed
- ✅ `app/Livewire/NotificationRules.php` - Admin CRUD component removed
- ✅ `app/Livewire/NotificationRulesTable.php` - Admin table component removed
- ✅ `resources/views/livewire/notification-rules.blade.php` - Blade view removed
- ✅ `resources/views/livewire/notification-rules-table.blade.php` - Blade view removed
- ✅ `app/Jobs/SendEmailJob.php` - Queue job removed (replaced with direct Mail::queue())
- ✅ `config/notifiable_events.php` - Old config file removed
- ✅ `tests/Feature/SendEmailJobTest.php` - Obsolete test file removed
- ✅ Removed notification rules route from `routes/web.php`
- ✅ Removed `seedNotificationRules()` method from `TestDataSeeder.php`

**Configuration Structure:**
```php
'notifications' => [
    \App\Events\ProjectCreated::class => [
        'roles' => ['Admin', 'Project Manager'],
        'include_project_owner' => false,
        'mailable' => \App\Mail\ProjectCreatedMail::class,
    ],
    \App\Events\ProjectStageChange::class => [
        'stage_roles' => [
            'ideation' => ['Ideation Manager'],
            'feasibility' => ['Feasibility Manager'],
            // ... etc
        ],
        'include_project_owner' => true,
        'mailable' => \App\Mail\ProjectStageChangeMail::class,
    ],
    // ... other events
]
```

**Testing:**
- ✅ Created 8 new tests covering all notification scenarios (23 assertions)
- ✅ Updated `FeasibilityApprovalTest.php` to remove NotificationRule dependencies
- ✅ Updated `ScopingWorkflowTest.php` to remove NotificationRule dependencies
- ✅ All 333 tests passing (1,154 assertions)
- ✅ Code formatted with Laravel Pint

**Key Benefits:**
1. **Simpler**: One config file vs database + UI + jobs + JSON parsing
2. **Reliable**: No risk of missing seeds or inactive rules breaking notifications
3. **Maintainable**: Adding new events = update config + add mailable + write test
4. **Readable**: Clear, explicit mapping of "when X happens, notify Y roles"
5. **Version controlled**: All notification logic tracked in git
6. **10x complexity reduction**: ~700 lines of infrastructure → ~60 lines of config

**Impact:**
- Notifications now work out-of-the-box with no database configuration required
- Missing scoping notifications are now properly configured and functional
- System follows Laravel best practices with direct Mail facade usage
- Zero admin UI maintenance burden

**Lessons Learned:**
- Over-engineering flexibility for fixed requirements creates unnecessary complexity
- Config-driven approach is perfectly adequate when rules don't change per-environment
- Simpler code = fewer failure modes = higher reliability

---

### Completed: Listener Refactor & Test Infrastructure ✓

**Date Completed:** 2025-11-18

**Context:**
After the notification system refactor, we had a legacy "big massive listener" (`ProjectEventsListener`) that handled all notification events. This was refactored into discrete, Laravel 12 auto-discovery compliant listeners. However, the new fail-fast exception handling (throwing when no notification recipients exist) exposed that 84 tests were failing due to missing role setup.

**Problem Statement:**
- New listeners throw `RuntimeException` when no users have required notification roles (by design for Sentry integration)
- 84 tests were failing because they created projects (triggering `ProjectCreated` event) but no roles existed
- Tests needed explicit, discoverable way to set up notification roles
- Using traits or hidden magic would make debugging difficult for developers

**Refactoring Solution: RoleUserResolver Service**

Created a centralized service to resolve which users should receive notifications based on events:

**Files Created:**
- ✅ `app/Services/RoleUserResolver.php` - Service class with `forEvent(object $event): Collection` method
- ✅ `app/Listeners/FeasibilityApprovedListener.php` - Discrete listener (~17 lines)
- ✅ `app/Listeners/FeasibilityRejectedListener.php` - Discrete listener (~17 lines)
- ✅ `app/Listeners/ProjectCreatedListener.php` - Discrete listener (~17 lines)
- ✅ `app/Listeners/ProjectStageChangeListener.php` - Discrete listener (~17 lines)
- ✅ `REFACTOR_LISTENERS.md` - Complete documentation of architectural decisions

**Files Refactored:**
- ✅ `app/Listeners/ScopingSubmittedListener.php` - Reduced from 54 lines to 17 lines
- ✅ `app/Listeners/SchedulingScheduledListener.php` - Reduced from 54 lines to 17 lines

**Files Deleted:**
- ✅ `app/Listeners/ProjectEventsListener.php` - Removed 117-line mega-listener

**Key Pattern Established:**
All listeners follow consistent pattern:
```php
public function handle(EventClass $event): void
{
    $users = app(RoleUserResolver::class)->forEvent($event);

    if ($users->isEmpty()) {
        throw new \RuntimeException(
            'No recipients found for '.EventClass::class.
            ' notification (Project #'.$event->project->id.')'
        );
    }

    Mail::to($users->pluck('email'))->queue(new SomeMailClass($event->project));
}
```

**Benefits:**
- Separation of concerns: Service returns data, listeners apply business logic
- Fail-fast error handling with descriptive exceptions for Sentry
- ~400 lines of duplicated recipient resolution → ~60 lines in service + ~140 lines in listeners
- Easier to test, maintain, and extend

---

**Test Infrastructure Solution:**

Created helper methods in `tests/TestCase.php` for explicit, discoverable test setup:

**Helper Methods Added:**

1. **`setupBaseNotificationRoles()`** - Creates all 14 required notification roles with dummy users assigned
   - Used in tests that verify notification behavior or event dispatching
   - Explicitly called in test `beforeEach()` blocks for visibility

2. **`fakeNotifications()`** - Fakes notification events but allows `ProjectCreated` to run
   - Used in tests that don't verify notification behavior
   - Does NOT fake `ProjectCreated` because that's needed for `CreateRelatedForms` listener
   - Calls `ensureProjectCreatedRoles()` to create minimal role setup

3. **`ensureProjectCreatedRoles()`** - Creates minimal Admin and Project Manager roles
   - Lightweight version of `setupBaseNotificationRoles()` for tests using `fakeNotifications()`
   - Uses `firstOrCreate()` to avoid duplicates

**Files Modified:**
- ✅ `tests/TestCase.php` - Added 3 helper methods (72 lines)
- ✅ `tests/Feature/FeasibilityApprovalTest.php` - Added `setupBaseNotificationRoles()` call, changed `Role::factory()->create()` to `Role::firstOrCreate()`
- ✅ `tests/Feature/ProjectCreationTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/ScopingWorkflowTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/SchedulingWorkflowTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/SchedulingHeatmapTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/ProjectEditingTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/HeatMapViewerTest.php` - Added role attachment to prevent duplicate users
- ✅ `tests/Feature/SkillMatchingTest.php` - Added `fakeNotifications()` calls
- ✅ `tests/Feature/UserViewerTest.php` - Added `fakeNotifications()` calls

**Testing:**
- ✅ Reduced from 84 test failures to 0 failures
- ✅ All 342 tests passing (1,171 assertions)
- ✅ Code formatted with Laravel Pint

**Critical Learning: Test Setup Order Matters**

When using `fakeNotifications()`, you must create test users and attach roles **before** calling `fakeNotifications()`. Otherwise `ensureProjectCreatedRoles()` will create duplicate users.

**Incorrect Order (causes duplicate users):**
```php
beforeEach(function () {
    $this->fakeNotifications();  // ← Called first, creates admin user

    $this->user = User::factory()->create(['is_admin' => true]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->user->roles()->attach($adminRole);  // ← Too late, duplicate exists
});
```

**Correct Order:**
```php
beforeEach(function () {
    // Create user FIRST
    $this->user = User::factory()->create(['is_admin' => true]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->user->roles()->attach($adminRole);

    // THEN call fakeNotifications()
    // Now ensureProjectCreatedRoles() sees existing user and doesn't create duplicate
    $this->fakeNotifications();
});
```

**Why This Happens:**
1. `fakeNotifications()` calls `ensureProjectCreatedRoles()`
2. `ensureProjectCreatedRoles()` checks: "Does Admin role have any users?"
3. If NO users exist yet, it creates one
4. If you create your test user AFTER this, you now have 2 admin users
5. Tests that count users (like HeatMapViewerTest) will get unexpected results

**Key Benefits of This Approach:**
- **Explicit**: Developers see exactly what setup is happening in `beforeEach()` blocks
- **Discoverable**: Helper methods are IDE-clickable from test code
- **Flexible**: Choose `setupBaseNotificationRoles()` OR `fakeNotifications()` based on test needs
- **No Magic**: No hidden traits or auto-setup that developers have to discover through debugging
- **Clear Intent**: Method names clearly communicate what they do

**Impact:**
- 84 failing tests → 342 passing tests
- Clear pattern for future test development
- No runtime notification failures due to missing roles
- Developers can easily see and understand test setup requirements

---

### Completed: Staff Heatmap Sorting Fix ✓

**Date Completed:** 2025-11-19

**Context:**
Management requested the ability to allocate tasks to staff members who don't have matching skills, to support onboarding and skill development within the team. The `getUsersMatchedBySkills()` method was refactored to return ALL staff users (not just matched ones), sorted by skill score descending.

**Problem Statement:**
- Multi-column sort was incorrectly ordered, causing final results to only be sorted by forenames
- Tests expected old behavior (only returning users with matching skills)
- Browser testing showed incorrect sorting: users weren't properly grouped by skill score

**Solution Implemented:**

**Files Modified:**
- ✅ `app/Livewire/ProjectEditor.php` - Fixed sorting order in `getUsersMatchedBySkills()` method (lines 327-330)
- ✅ `tests/Feature/SkillMatchingTest.php` - Updated 4 tests to match new behavior

**Sorting Fix:**
Changed from incorrect order:
```php
->sortByDesc('total_skill_score')  // Applied first
->sortBy('surname')                // Overwrites previous
->sortBy('forenames')              // Final sort (wrong!)
```

To correct stable multi-column sort:
```php
->sortBy('forenames')              // Least important
->sortBy('surname')                // More important
->sortByDesc('total_skill_score')  // Most important (final)
```

**Test Updates:**
All 4 failing tests updated to expect new "return all staff" behavior:
1. `can get users matched by skills and sorted by score` - Now expects 4 users (all staff), not 3 (matched only)
2. `returns all staff sorted alphabetically when no required skills provided` - Returns all staff with score 0, not empty
3. `returns all staff with score 0 when no users have required skills` - Returns all staff with score 0, not empty
4. `returns all staff with matched users sorted first by skill score` - Returns all 4 users with matched first

**Testing:**
- ✅ All 344 tests passing (1,196 assertions)
- ✅ Code formatted with Laravel Pint
- ✅ Browser testing confirmed correct behavior

**Key Benefits:**
- Users with matching skills appear at the top (sorted by skill score)
- All staff remain available for selection (onboarding opportunities)
- Stable sort maintains alphabetical ordering within same skill level
- Management can now assign tasks to develop team member skills

---

### Completed: Feature 3 - Scheduling Stage Triage Inputs ✓

**Date Completed:** 2025-11-21

**Context:**
Feature 3 adds three new input fields to the Scheduling stage to support Change Board workflow tracking and team assignment. The database migrations were already run during infrastructure setup, and Feature 2 had partially implemented two of the fields (`technicalLeadId` and `changeChampionId`) in the backend but not the UI.

**What Was Missing Before Implementation:**
- Backend had `technicalLeadId` and `changeChampionId` properties but missing `changeBoardOutcome`
- UI had none of the three dropdowns visible
- Model missing relationship methods and enum cast
- No tests for these fields

**Files Modified:**

1. ✅ **`app/Models/Scheduling.php`**
   - Added `change_board_outcome` and `fields_locked` to `$fillable` array
   - Added enum cast for `change_board_outcome` (ChangeBoardOutcome::class)
   - Added boolean cast for `fields_locked`
   - Added `technicalLead()` BelongsTo relationship
   - Added `changeChampion()` BelongsTo relationship

2. ✅ **`app/Livewire/Forms/SchedulingForm.php`**
   - Added `changeBoardOutcome` property with empty `#[Validate]` attribute
   - Created `rules()` method with `Rule::enum(ChangeBoardOutcome::class)` validation
   - Updated `setProject()` to load `change_board_outcome` from model
   - Updated `save()` to persist `change_board_outcome` (using `?->value` for enum)

3. ✅ **`resources/views/livewire/forms/scheduling-form.blade.php`**
   - Added Technical Lead dropdown (foreign key to users)
   - Added Change Champion dropdown (foreign key to users)
   - Added Change Board Outcome dropdown (enum: Pending, Approved, Deferred, Rejected)
   - Used 3-column grid layout for visual consistency
   - Added `data-test` attributes for testing

**Testing:**
- ✅ Created `tests/Feature/SchedulingTriageTest.php` with 17 comprehensive tests:
  - 5 field persistence tests (save/load for each field, all together, and null values)
  - 4 validation tests (invalid user IDs, valid enum, invalid enum protection)
  - 5 UI display tests (dropdowns visible, options correct, selected values display)
  - 2 relationship tests (technicalLead and changeChampion relationships work)
  - 1 project isolation test (changes don't affect other projects)
- ✅ All 17 new tests passing (47 assertions)
- ✅ All 361 total tests passing (1,251 assertions) - no regressions
- ✅ Code formatted with Laravel Pint

**Manual QA:**
- ✅ All three dropdowns display correctly in Scheduling tab
- ✅ Values save and persist correctly
- ✅ Dropdowns populate with correct options
- ✅ Existing Model/DCGG buttons still work (no regressions)

**Key Implementation Patterns Established:**

1. **Livewire Enum Validation Pattern:**
   ```php
   #[Validate]  // Empty attribute triggers real-time validation
   public ?ChangeBoardOutcome $changeBoardOutcome = null;

   public function rules(): array {
       return [
           'changeBoardOutcome' => ['nullable', Rule::enum(ChangeBoardOutcome::class)],
       ];
   }
   ```
   - Empty `#[Validate]` attribute tells Livewire to validate on updates
   - Actual validation rule in `rules()` method because PHP attributes can't use Rule objects
   - This pattern should be used for all enum validations going forward

2. **Test Setup Helper for Complex Forms:**
   - Created `setupValidScheduling()` closure in `beforeEach()` to pre-populate required fields
   - Prevents validation errors when testing optional fields
   - Keeps tests focused and avoids duplicating setup code

3. **Enum Nullable Handling:**
   - Empty string from dropdown converts to `null` (Livewire EnumSynth behavior)
   - Save using `$this->changeBoardOutcome?->value` to handle null gracefully

**Implementation Time:**
- Estimated: ~2 hours
- Actual: ~2 hours ✅ (spot on!)

**Success Metrics:**
- ✅ All three fields display and function in Scheduling tab
- ✅ Fields save and persist correctly
- ✅ Relationships work correctly
- ✅ Comprehensive test coverage (17 tests)
- ✅ No regressions
- ✅ Manual QA passed

**Notes:**
- `fields_locked` field added to model/database but not implemented in UI yet (skipped for simplicity)
- Can add field locking behavior later once workflows are stable
- Feature 2's jump-ahead implementation of `technicalLeadId`/`changeChampionId` saved significant time

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
