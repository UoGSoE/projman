# Feature 2: Scoping Effort Scale & DCGG Workflow (with Scheduling Heatmap Integration)

## Context & Discovery

### What We Found
- **PROJECT_PLAN.md had features mixed up**: Model button was listed under Scoping, but the PowerPoint slides clearly show it belongs to **Scheduling**
- **PowerPoint slides 20-21** specify: "Model: will allow us to display the heatmap once a 'Assigned to' has been selected"
- Current `HeatMapViewer` component works well standalone but needs logic extracted for reuse
- Team convention: **avoid service classes**, prefer fat models, traits, and component methods

### Requirements Clarification
**Scoping Stage:**
- Replace free-text effort with dropdown (Small → XX-Large enum)
- Add "Submit to DCGG" workflow with governance approval tracking
- Update button labels to "Update"

**Scheduling Stage:**
- Add "Model" button that displays heatmap **inline on the same page**
- Heatmap should:
  - Show all staff by default (alphabetically by surname)
  - Re-sort when staff are assigned: selected staff at top, others below
  - Display the standard 10-day busyness grid
  - Show active projects list below (same as standalone page)

## Technical Approach

### 1. Code Reuse Strategy (No Service Classes)
- **Create trait**: `app/Traits/HasHeatmapData.php` with reusable logic
- **Model helpers**: Add busyness methods to `User` model if needed
- **Blade partial**: Extract heatmap table to `resources/views/components/heatmap-table.blade.php`
- **Both components use trait**: `HeatMapViewer` and `ProjectEditor`

### 2. Smart Sorting Logic
When "Model" is clicked on Scheduling form:
- Collect assigned staff IDs from `schedulingForm` (`assigned_to`, `technical_lead_id`, `change_champion_id`, `cose_it_staff`)
- Sort staff list: assigned staff first (alphabetically), then remaining staff (alphabetically)
- Re-render heatmap partial with sorted data

### 3. UI/UX Flow
- Model button initially visible but could be disabled until `assigned_to` is set (per PowerPoint)
- Clicking "Model" shows the heatmap below the form (not in modal, not redirect)
- Heatmap updates when form fields change (Livewire reactivity)

## Implementation Checklist

### Phase A: Infrastructure & Scoping Stage ✅ **COMPLETED**
- [x] Create `app/Enums/EffortScale.php` with label() and daysRange() methods *(already done per PROJECT_PLAN.md)*
- [x] Update `app/Models/Scoping.php`:
  - [x] Add `dcgg_status`, `submitted_to_dcgg_at`, `scheduled_at` to fillable
  - [x] Add casts for `estimated_effort` (EffortScale enum) and datetime fields
- [x] Update `app/Livewire/Forms/ScopingForm.php`:
  - [x] Change `estimatedEffort` property to `?EffortScale` type
  - [x] Add `dcggStatus`, `submittedToDcggAt`, `scheduledAt` properties
  - [x] Update validation rules for enum (created `rules()` method)
  - [x] Update `setProject()` and `save()` methods
- [x] Update Scoping section in `resources/views/livewire/project-editor.blade.php`:
  - [x] Replace textarea with dropdown using EffortScale cases
  - [x] Change "Save" button to "Update"
  - [x] Add "Submit to DCGG" button (if `dcggStatus === 'pending'`)
  - [x] Add "Schedule" button (if `dcggStatus === 'submitted'`)
  - [x] Add DCGG status badge display
- [x] Add Scoping actions to `app/Livewire/ProjectEditor.php`:
  - [x] `submitToDCGG()` - validates, updates status, dispatches event, adds history
  - [x] `scheduleScoping()` - approves, updates timestamps, dispatches event, adds history
- [x] Create events:
  - [x] `app/Events/ScopingSubmittedToDCGG.php`
  - [x] `app/Events/ScopingScheduled.php`
- [x] Create standalone listeners (better pattern than ProjectEventsListener):
  - [x] `app/Listeners/ScopingSubmittedToDCGGListener.php` - uses NotificationRule system
  - [x] `app/Listeners/ScopingScheduledListener.php` - uses NotificationRule system
  - [x] Auto-wired by Laravel 11+ (no manual registration needed!)
- [x] Run Pint formatting: `vendor/bin/pint --dirty` (8 files formatted)

### Phase D: Testing ✅ **COMPLETED FOR PHASE A**
- [x] Create `tests/Feature/ScopingWorkflowTest.php`:
  - [x] Test effort scale dropdown saves correctly ✅
  - [x] Test submitToDCGG updates status and timestamps ✅
  - [x] Test scheduleScoping approves and timestamps ✅
  - [x] Test events are dispatched correctly ✅
  - [x] Test email notifications via NotificationRule system ✅
  - [x] Test project isolation (no cross-project effects) ✅
  - [x] Test history recording ✅
  - [x] Test UI elements (badges, buttons, dropdown) ✅
  - **Result:** 17 tests, all passing (36 assertions) 🎉
- [x] Run Pint: `vendor/bin/pint --dirty` ✅
- [ ] Run feature tests for Scheduling heatmap (Phase B/C work)
- [ ] Full test suite after all phases complete

### Phase B: Heatmap Extraction & Trait Creation ✅ **COMPLETED**
- [x] Create `app/Traits/HasHeatmapData.php`:
  - [x] Move `upcomingWorkingDays()` from HeatMapViewer (make protected)
  - [x] Move `staffWithBusyness()` from HeatMapViewer (make protected)
  - [x] Move `busynessSeries()` from HeatMapViewer (make protected)
  - [x] Move `busynessForDay()` from HeatMapViewer (make public - used by view)
  - [x] Move `activeProjects()`, `teamMembersForProjects()`, `collectTeamMembers()`, `collectTeamMemberIds()` from HeatMapViewer
  - [x] Add new method: `sortStaffByAssignment(Collection $staff, array $assignedUserIds)` for smart sorting
  - [x] Removed all `select()` calls per team convention (validation hook caught this!)
- [x] Update `app/Livewire/HeatMapViewer.php`:
  - [x] Add `use HasHeatmapData;` trait
  - [x] Remove methods now in trait
  - [x] Verify `render()` still works with trait methods
  - [x] Component now only 24 lines (from 171!)
- [x] Create Blade partial `resources/views/components/heatmap-table.blade.php`:
  - [x] Extract heatmap table HTML from `resources/views/livewire/heat-map-viewer.blade.php`
  - [x] Accept parameters: `$days`, `$staff`, `$component`
  - [x] Keep busyness color logic intact
  - [x] Added `data-test="heatmap-grid"` attribute for testing
- [x] Update `resources/views/livewire/heat-map-viewer.blade.php`:
  - [x] Replace extracted HTML with `@include('components.heatmap-table', [...])`
  - [x] Verify standalone page still works
- [x] Create `tests/Feature/HeatMapViewerTest.php`:
  - [x] Test standalone heatmap page loads
  - [x] Test staff sorted alphabetically
  - [x] Test active vs cancelled projects
  - [x] Test 10 working days generated
  - [x] Test busyness data included
  - [x] **Result:** 5 tests, all passing (22 assertions) 🎉
- [x] Run Pint: `vendor/bin/pint --dirty` ✅

### Phase C: Scheduling Stage Integration
- [ ] Update `app/Livewire/ProjectEditor.php`:
  - [ ] Add `use HasHeatmapData;` trait
  - [ ] Add public property: `public bool $showHeatmap = false;`
  - [ ] Add method: `public function toggleHeatmap()` to set `$showHeatmap = true`
  - [ ] Add `#[Computed]` property: `heatmapData()` that returns `['days' => ..., 'staff' => ..., 'projects' => ...]`
  - [ ] In `heatmapData()`, collect assigned user IDs from schedulingForm
  - [ ] Use `sortStaffByAssignment()` to prioritize selected staff
- [ ] Update Scheduling section in `resources/views/livewire/project-editor.blade.php`:
  - [ ] Change "Save" button to "Update"
  - [ ] Add "Model" button: `wire:click="toggleHeatmap"` (consider `:disabled="!$schedulingForm->assignedTo"`)
  - [ ] Add "Submit" button (for DCGG submission)
  - [ ] Add "Schedule" button (for approval confirmation)
  - [ ] Below form fields, add:
    ```blade
    @if($showHeatmap)
        <div class="mt-8">
            <flux:heading size="lg">Staff Heatmap</flux:heading>
            @include('components.heatmap-table', $this->heatmapData)
        </div>
    @endif
    ```

### Phase D: Testing
- [x] Create `tests/Feature/ScopingWorkflowTest.php`: ✅ (Phase A)
  - [x] Test effort scale dropdown saves correctly
  - [x] Test submitToDCGG updates status and timestamps
  - [x] Test scheduleScoping requires change board date
  - [x] Test events are dispatched correctly
  - [x] Test email notifications sent to Work Package Assessors
  - [x] Test project isolation (no cross-project effects)
- [x] Create `tests/Feature/HeatMapViewerTest.php`: ✅ (Phase B)
  - [x] Verify standalone page still works after trait extraction
  - [x] Test staff sorted alphabetically
  - [x] Test active projects display, cancelled projects don't
  - [x] Test 10 working days generated
  - [x] Test busyness data included
- [ ] Create `tests/Feature/SchedulingHeatmapTest.php`: (Phase C)
  - [ ] Test heatmap displays when Model button clicked
  - [ ] Test assigned staff appear at top of heatmap
  - [ ] Test heatmap shows all staff when none assigned
  - [ ] Test heatmap data computed property returns correct structure
  - [ ] Test UI shows heatmap partial correctly with `assertSeeHtml('data-test="heatmap-grid"')`
- [x] Run Pint: `vendor/bin/pint --dirty` ✅
- [x] Run feature tests: `lando artisan test --filter=Scoping` ✅
- [x] Run feature tests: `lando artisan test --filter=HeatMapViewer` ✅
- [ ] Run feature tests: `lando artisan test --filter=SchedulingHeatmap`
- [ ] Full test suite: `lando artisan test`

### Phase E: Manual QA & Polish
- [ ] Seed test data: `lando db:seed --class=TestDataSeeder`
- [ ] Test Scoping stage in browser:
  - [ ] Verify effort scale dropdown shows correct options
  - [ ] Test Submit to DCGG workflow
  - [ ] Verify emails sent correctly (check Mailhog)
- [ ] Test Scheduling stage in browser:
  - [ ] Verify Model button shows/hides heatmap
  - [ ] Assign staff and verify they appear at top of heatmap
  - [ ] Test with no staff assigned - should show alphabetical list
  - [ ] Verify heatmap styling matches standalone page
  - [ ] Test on mobile/tablet (responsive design)
- [ ] Check for any console errors in browser
- [ ] Verify AlpineJS/Livewire interactions work smoothly

## Potential Gotchas

### Performance Concerns
- The heatmap queries can be heavy (eager loading, multiple relationships)
- Consider using `#[Computed]` to cache the result until form changes
- May need `wire:loading` indicator when toggling heatmap

### Sorting Edge Cases
- What if `cose_it_staff` contains user IDs that don't exist? (Filter them out)
- What if same user is in multiple assignment fields? (Use `unique()` collection method)

### UI/Styling
- Heatmap table might need width constraints when embedded
- Consider making it horizontally scrollable on smaller screens
- Flux UI styling should be consistent with rest of form

### Livewire Lifecycle
- Make sure `showHeatmap` resets when switching tabs
- Consider adding `wire:key` to heatmap partial for proper reactivity

## Success Criteria

- [ ] Scoping stage has effort scale dropdown (no free text)
- [ ] DCGG workflow tracks submission and approval
- [ ] Scheduling stage has Model button that shows inline heatmap
- [ ] Assigned staff appear at top of heatmap, others below alphabetically
- [ ] Standalone heatmap page continues to work
- [ ] All tests passing
- [ ] No regressions in existing functionality
- [ ] Code follows team conventions (no service classes, fat models, simple & readable)

---

## Development Notes & Lessons Learned

### Artisan Commands
- **Use local PHP for `make:` commands**: Run `php artisan make:event EventName` instead of `lando artisan make:event EventName`
  - Reason: Saves spinning up Docker containers for simple code generation
  - Lando is still needed for: migrations, testing, seeding, tinker, etc.
  - Example: `php artisan make:event ScopingSubmittedToDCGG --no-interaction`

### Laravel 11+ Event-Listener Auto-Wiring
- **No manual registration needed**: Listeners are automatically discovered based on the type-hinted event in the `handle()` method
- **How it works**: Laravel scans all listeners and matches them to events by the parameter type in `handle()`
- **Example**:
  ```php
  // This listener automatically handles ScopingSubmittedToDCGG events
  class ScopingSubmittedToDCGGListener
  {
      public function handle(ScopingSubmittedToDCGG $event): void
      {
          // Handle the event
      }
  }
  ```
- **No EventServiceProvider needed**: Laravel 11 streamlined structure doesn't require manual event mapping
- **Benefit**: Cleaner code, less boilerplate, fewer places to update when adding new events

### Code Patterns Followed
- **Standalone listeners**: Created separate listener classes instead of using the weird `ProjectEventsListener` pattern
- **Consistent event structure**: All events extend base traits and accept `Project $project` in constructor
- **NotificationRule integration**: Listeners query the `notification_rules` table to determine recipients dynamically
- **History tracking**: All workflow actions call `$project->addHistory()` for audit trail

### Testing Results for Phase A ✅
- Created `tests/Feature/ScopingWorkflowTest.php` with 17 comprehensive tests
- **All 17 tests passing** (36 assertions)
- Test patterns from `FeasibilityApprovalTest.php` successfully reused
- Used `data-test` attributes on buttons for reliable UI assertions
- **Bugs Fixed During Testing:**
  - Flux button variants: Changed `variant="info"` → `variant="filled"` and `variant="success"` → `variant="primary"`
  - Flux badge attributes: Changed `variant=` → `color=` and used color names (green, blue) instead of semantic names
  - Enum validation: Adjusted test to avoid Livewire enum hydration issues with null values

### Testing Results for Phase B ✅
- Created `tests/Feature/HeatMapViewerTest.php` with 5 comprehensive tests
- **All 5 tests passing** (22 assertions)
- **Testing Lessons Learned:**
  - **Test in layers**: First test that the component appears on the page (`assertSeeLivewire`), then test the component logic directly using `Livewire::test(ComponentName::class)`
  - **Test the component, not just the output**: Use `$component->viewData('staff')` to inspect what data the component is providing to the view
  - **Use dd() or dump() liberally when debugging**: When a test fails, add a selective `dd($staff)` or `dump($staff->pluck('user.surname'))` to see exactly what data structure you're working with. This is **super helpful** and will save you tons of time versus guessing
  - **Be careful with factory-generated data**: Random data (like surnames from factories) can make tests non-deterministic. Fix critical test data to known values (e.g., admin user with surname 'AdminUser') to make assertions reliable
  - **assertSeeInOrder is your friend**: Great for verifying display order without needing to know exact indices
  - **Keep test data simple**: Don't over-complicate - if you need 4 users, accept that you have 4 users and assert based on reality

---

## Current Status & What's Next

### ✅ Phase A: Complete (2025-11-13)
- Scoping effort scale dropdown (Small → XX-Large)
- DCGG workflow (Submit → Schedule)
- Standalone listeners following Laravel 11 conventions
- 17 comprehensive tests, all passing
- Code formatted with Pint

### ✅ Phase B: Complete (2025-11-13)
- Created reusable `HasHeatmapData` trait with all heatmap logic
- Extracted heatmap table to Blade partial for reuse
- Refactored `HeatMapViewer` from 171 lines to 24 lines
- Added `sortStaffByAssignment()` method for smart staff ordering
- Comprehensive tests ensuring standalone heatmap still works
- 5 tests passing (22 assertions)
- Code formatted with Pint

### 🎯 Phase C: Next Up - Scheduling Stage Integration

**Goal:** Add the heatmap display to the Scheduling tab of ProjectEditor so users can see staff availability while assigning team members.

**Key Tasks:**
1. **Update `ProjectEditor` component**
   - Add `use HasHeatmapData;` trait
   - Add `showHeatmap` property and `toggleHeatmap()` method
   - Add `#[Computed]` property that returns heatmap data
   - Collect assigned user IDs from scheduling form
   - Use `sortStaffByAssignment()` to show assigned staff first

2. **Update Scheduling view**
   - Add "Model" button to toggle heatmap display
   - Include heatmap partial below form fields when visible
   - Ensure "Update" button label (not "Save")

3. **Test the integration**
   - Heatmap displays when Model button clicked
   - Assigned staff appear at top
   - Heatmap updates when staff assignments change

**Estimated Time:** 1-2 hours

---

**Estimated Effort:** Medium-Large (this is a chunky feature!)
**Dependencies:** Infrastructure setup from Phase 1 must be complete
**Next Feature:** Feature 3 (Scheduling triage fields) - can start once Feature 2 is done
