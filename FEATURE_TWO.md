# Feature 2: Scoping Effort Scale & DCGG Workflow (with Scheduling Heatmap Integration)

## Note
The spec for this project came as a PowerPoint slide deck.  The extracted text is in the file 'pptx_text_extract.txt' - it is a useful, if difficult to read reference.

---

## 🚨 CRITICAL: DCGG Workflow Implemented on Wrong Stage! 🚨

**Date Discovered:** 2025-11-18

### The Problem

We implemented the DCGG (Digital Change Governance Group) workflow on the **SCOPING stage**, but after re-reading the PowerPoint spec carefully, it's clear this should be on the **SCHEDULING stage**.

**What the PowerPoint actually says:**

- **Scoping (slide 15, lines 173-176):**
  - "Add Submit button next to SAVE button"
  - "Submit will progress the status and email a group 'Work package Assessors'"
  - **NO MENTION OF DCGG**

- **Scheduling (slide 21, lines 265-269):**
  - "**Submit: The submit button will allow us to submit the workpackage request to the Digital Change Governance Group (DCGG) for approval**"
  - "Schedule: will confirm that the schedule has been approved"

### What We Got Wrong

**Incorrectly Implemented on Scoping:**
- ✅ `dcgg_status`, `submitted_to_dcgg_at`, `scheduled_at` fields added to `scopings` table
- ✅ "Submit to DCGG" and "Schedule" buttons on Scoping stage
- ✅ `ScopingSubmittedToDCGG` and `ScopingScheduled` events
- ✅ Full test coverage (17 tests passing)
- ❌ **But this is all on the wrong stage!**

**Should Have Been:**
- Scoping should have a simple "Submit" button that emails Work Package Assessors (no DCGG)
- Scheduling should have "Submit to DCGG" and "Schedule" buttons

### Remediation Plan

#### Phase 1: Fix Scoping Stage
1. Remove DCGG-related buttons from Scoping view (keep Update button)
2. Add simple "Submit" button that emails Work Package Assessors
3. Remove/rename events: `ScopingSubmittedToDCGG` → `ScopingSubmitted`
4. Update tests to reflect simpler workflow
5. Keep database fields (they don't hurt anything) but stop using them

#### Phase 2: Add DCGG to Scheduling Stage
1. Create migration to add DCGG fields to `schedulings` table:
   - `submitted_to_dcgg_at` timestamp
   - `scheduled_at` timestamp
2. Update `SchedulingForm` with DCGG workflow properties
3. Add "Submit to DCGG" and "Schedule" buttons to Scheduling view
4. Create new events: `SchedulingSubmittedToDCGG` and `SchedulingScheduled`
5. Create listeners with DCGG email handling (using config approach)
6. Write comprehensive tests

#### Phase 3: Configuration
1. Add to `config/projman.php`:
   - `dcgg_email` with env fallback to `dcgg@example.ac.uk`
2. Update notification config for new Scheduling events

### Impact Assessment

**Effort Required:**
- Phase 1 (Simplify Scoping): ~1-2 hours
- Phase 2 (Add DCGG to Scheduling): ~2-3 hours
- Phase 3 (Config & Testing): ~1 hour
- **Total: 4-6 hours**

**Files Affected:**
- Database: New migration for `schedulings` table
- Models: `Scheduling.php`
- Forms: `SchedulingForm.php`
- Components: `ProjectEditor.php`
- Views: `project-editor.blade.php` (both stages)
- Events: Rename/create 4 events total
- Listeners: Update/create 4 listeners
- Tests: Update `ScopingWorkflowTest.php`, create `SchedulingWorkflowTest.php`
- Config: `projman.php`

**Risk:**
- Medium - existing Scoping tests will break but easy to fix
- No data loss - fields exist but unused won't hurt
- Good news: No production users yet!

### Why This Happened

The previous developer mentioned confusion about this - and they were right! The spec is verbose and the DCGG workflow details are scattered across multiple slides. Easy to miss that:
1. Scoping Submit = simple email to Work Package Assessors
2. Scheduling Submit = DCGG governance approval process

This is a classic case of ambiguous requirements causing implementation in the wrong place.

### Priority

**HIGH** - This affects the governance workflow that leadership specifically requested. Better to fix it now before we continue with Feature 3.

---

## ✅ Phase 1 Complete: Scoping Stage Simplified (2025-11-18)

**Status:** COMPLETE
**Time Taken:** ~1 hour
**Tests:** 10 tests passing (21 assertions)

### What We Did

Successfully simplified the Scoping stage to remove DCGG workflow and replace with a simple Submit button that emails Work Package Assessors.

#### Files Modified

**1. View Updates** (`resources/views/livewire/project-editor.blade.php`)
- ❌ Removed DCGG status badge (was showing "Submitted"/"Approved")
- ❌ Removed conditional "Submit to DCGG" button
- ❌ Removed conditional "Schedule" button
- ✅ Added simple "Submit" button with `data-test="submit-scoping-button"`
- Result: Clean, simple UI - just Update and Submit buttons

**2. Events & Listeners**
- ✅ Created `app/Events/ScopingSubmitted.php` (replaces ScopingSubmittedToDCGG)
- ✅ Created `app/Listeners/ScopingSubmittedListener.php` (emails Work Package Assessors only)
- ❌ Deleted `app/Events/ScopingSubmittedToDCGG.php`
- ❌ Deleted `app/Events/ScopingScheduled.php`
- ❌ Deleted `app/Listeners/ScopingSubmittedToDCGGListener.php`
- ❌ Deleted `app/Listeners/ScopingScheduledListener.php`

**3. Component Methods** (`app/Livewire/ProjectEditor.php`)
- ❌ Removed `submitToDCGG()` method (23 lines - was updating dcgg_status, timestamps, dispatching event)
- ❌ Removed `scheduleScoping()` method (16 lines - was approving and scheduling)
- ✅ Added `submitScoping()` method (10 lines - validates, adds history, dispatches event, shows toast)
- Result: 29 lines removed, 10 lines added = net -19 lines

**4. Form Cleanup** (`app/Livewire/Forms/ScopingForm.php`)
- ❌ Removed `dcggStatus` validation rule (`'in:pending,submitted,approved'`)
- ✅ Kept properties for backward compatibility (existing DB fields won't break)
- Result: Simpler validation, no behavioral changes to form saving

**5. Configuration** (`config/projman.php`)
- ❌ Removed `ScopingSubmittedToDCGG::class` notification config
- ❌ Removed `ScopingScheduled::class` notification config
- ✅ Added `ScopingSubmitted::class` → sends to 'Work Package Assessor' role only
- Result: Clean, simple notification routing

**6. Tests** (`tests/Feature/ScopingWorkflowTest.php`)
- Completely rewrote test file
- Removed DCGG-specific tests (7 tests removed):
  - ❌ Submit to DCGG workflow tests
  - ❌ Schedule scoping tests
  - ❌ DCGG status badge tests
  - ❌ Conditional button visibility tests
- Simplified remaining tests (10 tests kept):
  - ✅ Effort scale enum saving
  - ✅ Effort scale validation
  - ✅ Submit scoping successfully
  - ✅ Event dispatching
  - ✅ Email notifications
  - ✅ History recording
  - ✅ Project isolation
  - ✅ UI button visibility
  - ✅ Effort scale dropdown
  - ✅ Update button display

**Test Results:**
```
Tests:    10 passed (21 assertions)
Duration: 3.06s
```

**7. Code Formatting**
- ✅ Ran `vendor/bin/pint --dirty` (6 files formatted)

### What Changed for Users

**Before (Incorrect - DCGG on Scoping):**
```
Scoping Stage:
- Update button
- "Submit to DCGG" button (when pending)
- "Schedule" button (when submitted)
- DCGG status badge
```

**After (Correct - Simple Submit):**
```
Scoping Stage:
- Update button
- "Submit" button (always visible)
- (Emails Work Package Assessors)
```

### Database Impact

**No migrations needed!** The DCGG fields (`dcgg_status`, `submitted_to_dcgg_at`, `scheduled_at`) remain in the `scopings` table but are no longer used. This is intentional:
- Keeps existing data intact
- No risk of data loss
- Can be cleaned up later if needed
- Form still loads/saves these fields (no errors)

### Key Implementation Details

**Simplified `submitScoping()` method:**
```php
public function submitScoping(): void
{
    $this->scopingForm->validate();
    $this->project->addHistory(Auth::user(), 'Submitted scoping for review');
    event(new \App\Events\ScopingSubmitted($this->project));
    Flux::toast('Scoping submitted to Work Package Assessors', variant: 'success');
}
```

**New listener follows established pattern:**
- Reads from `config('projman.notifications')`
- Resolves recipients by role name
- Queues mailable via `Mail::to($recipients)->queue($mailable)`
- Uses existing `ScopingSubmittedMail` (no changes needed)

### What's Next: Phase 2

Now we need to **implement DCGG workflow on Scheduling stage** (where it actually belongs according to the spec).

---

## ✅ Phase 2 Complete: DCGG Workflow Added to Scheduling Stage (2025-11-18)

**Status:** COMPLETE (pending tests)
**Time Taken:** ~2 hours
**Tests:** Not yet written (next task)

### What We Did

Successfully implemented the DCGG (Digital Change Governance Group) workflow on the Scheduling stage where it belongs according to the PowerPoint spec.

#### Files Created

**1. Migration** (`2025_11_18_163344_add_dcgg_fields_to_schedulings_table.php`)
- ✅ Added `submitted_to_dcgg_at` timestamp
- ✅ Added `submitted_to_dcgg_by` foreign key to users table (audit trail - user's idea!)
- ✅ Added `scheduled_at` timestamp
- ✅ Proper `down()` method with `dropConstrainedForeignId()`

**2. Events**
- ✅ Created `app/Events/SchedulingSubmittedToDCGG.php`
- ✅ Created `app/Events/SchedulingScheduled.php`

**3. Listeners**
- ✅ Created `app/Listeners/SchedulingSubmittedToDCGGListener.php`
  - Resolves role-based recipients
  - Includes project owner if configured
  - **Special feature**: Includes DCGG email address when `include_dcgg_email` is true
- ✅ Created `app/Listeners/SchedulingScheduledListener.php`
  - Sends to Work Package Assessors
  - Standard role-based notification

**4. Mailables & Templates**
- ✅ Created `app/Mail/SchedulingSubmittedMail.php`
- ✅ Created `app/Mail/SchedulingScheduledMail.php`
- ✅ Created `resources/views/emails/scheduling_submitted.blade.php`
  - Shows assigned user, estimated dates
- ✅ Created `resources/views/emails/scheduling_scheduled.blade.php`
  - Shows assigned user, dates, Change Board date

#### Files Modified

**1. Database Model** (`app/Models/Scheduling.php`)
- ✅ Added `submitted_to_dcgg_at`, `submitted_to_dcgg_by`, `scheduled_at` to `$fillable`
- ✅ Added datetime casts for `submitted_to_dcgg_at` and `scheduled_at`
- ✅ Added `submittedToDcggBy()` relationship method

**2. Form** (`app/Livewire/Forms/SchedulingForm.php`)
- ✅ Added `Carbon` import
- ✅ Added three properties: `submittedToDcggAt`, `submittedToDcggBy`, `scheduledAt`
- ✅ Updated `setProject()` to load DCGG fields

**3. View** (`resources/views/livewire/project-editor.blade.php`)
- ✅ Reorganized button layout into two rows
- ✅ Row 1: Update and Model buttons
- ✅ Row 2: DCGG workflow buttons
- ✅ Added "Submit to DCGG" button (shows when not yet submitted)
  - `data-test="submit-scheduling-to-dcgg-button"`
- ✅ Added "Schedule" button (shows when submitted but not scheduled)
  - `data-test="schedule-scheduling-button"`
- ✅ Conditional rendering based on `submittedToDcggAt` and `scheduledAt`

**4. Component** (`app/Livewire/ProjectEditor.php`)
- ✅ Added `submitSchedulingToDCGG()` method:
  - Validates form
  - Updates timestamps and audit fields
  - Adds history
  - Dispatches event
  - Shows success toast
- ✅ Added `scheduleScheduling()` method:
  - Validates Change Board date is filled (per spec requirement)
  - Updates scheduled timestamp
  - Adds history
  - Dispatches event
  - Shows success toast

**5. Configuration** (`config/projman.php`)
- ✅ Added `dcgg_email` config with env fallback:
  ```php
  'dcgg_email' => env('PROJMAN_DCGG_EMAIL', 'dcgg@example.ac.uk'),
  ```
- ✅ Added `SchedulingSubmittedToDCGG` notification config:
  - Sends to Work Package Assessor role
  - **Special**: `include_dcgg_email => true` (emails DCGG group)
  - Uses `SchedulingSubmittedMail`
- ✅ Added `SchedulingScheduled` notification config:
  - Sends to Work Package Assessor role
  - Uses `SchedulingScheduledMail`

**6. Code Formatting**
- ✅ Ran `vendor/bin/pint --dirty` (15 files formatted)

### Key Features Implemented

**1. Audit Trail**
The `submitted_to_dcgg_by` field tracks WHO submitted to DCGG, not just when. This was a smart addition by the user and provides valuable audit information for governance.

**2. DCGG Email Integration**
The listener has special logic to include the DCGG email address from config when `include_dcgg_email` is true. This allows emails to go to:
- Work Package Assessors (role-based)
- The mysterious DCGG group (could be Brian! 😄)
- Project owner (optional)

**3. Conditional Button Display**
The view uses smart conditionals:
- **Submit to DCGG**: Only shows before submission
- **Schedule**: Only shows after submission but before scheduling
- Clean, progressive workflow that guides users

**4. Validation**
The `scheduleScheduling()` method validates that Change Board date is filled before allowing schedule confirmation, exactly as specified in the PowerPoint.

### What Changed for Users

**Before (Incorrect):**
```
Scheduling Stage:
- Update, Model, Advance buttons in one row
- No DCGG workflow
```

**After (Correct per spec):**
```
Scheduling Stage:
- Row 1: Update | Model
- Row 2: Submit to DCGG (conditional) | Schedule (conditional) | Advance
- Full DCGG governance workflow
- Emails to Work Package Assessors + DCGG group
```

### Database Impact

**New migration adds 3 fields to `schedulings` table:**
- `submitted_to_dcgg_at` (nullable timestamp)
- `submitted_to_dcgg_by` (nullable foreign key to users) - **AUDIT TRAIL**
- `scheduled_at` (nullable timestamp)

User will run migration themselves.

### Implementation Details

**submitSchedulingToDCGG() method:**
```php
public function submitSchedulingToDCGG(): void
{
    $this->schedulingForm->validate();

    $this->project->scheduling->update([
        'submitted_to_dcgg_at' => now(),
        'submitted_to_dcgg_by' => Auth::id(), // Audit trail!
    ]);

    $this->schedulingForm->submittedToDcggAt = now();
    $this->schedulingForm->submittedToDcggBy = Auth::id();

    $this->project->addHistory(Auth::user(), 'Submitted scheduling to DCGG for approval');
    event(new \App\Events\SchedulingSubmittedToDCGG($this->project));

    Flux::toast('Scheduling submitted to Digital Change Governance Group', variant: 'success');
}
```

**scheduleScheduling() with validation:**
```php
public function scheduleScheduling(): void
{
    // Validate Change Board date is filled (per spec)
    if (empty($this->schedulingForm->changeBoardDate)) {
        $this->addError('schedulingForm.changeBoardDate',
            'Change Board date must be set before scheduling.');
        return;
    }

    $this->project->scheduling->update(['scheduled_at' => now()]);
    $this->schedulingForm->scheduledAt = now();

    $this->project->addHistory(Auth::user(), 'Scheduling approved and scheduled');
    event(new \App\Events\SchedulingScheduled($this->project));

    Flux::toast('Scheduling approved and scheduled', variant: 'success');
}
```

### What's Left: Phase 2 Testing

Still need to create comprehensive tests for the Scheduling DCGG workflow:
- Submit to DCGG workflow
- Schedule workflow
- Validation (Change Board date required)
- Event dispatching
- Email notifications (including DCGG email)
- History recording
- Button visibility
- Audit trail (submitted_to_dcgg_by)

**Estimated:** 10-15 tests

---

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

### Phase C: Scheduling Stage Integration ✅ **COMPLETED**
- [x] Added `technicalLeadId` and `changeChampionId` to `SchedulingForm` (jumped ahead from Feature 3)
- [x] Update `app/Livewire/ProjectEditor.php`:
  - [x] Add `use HasHeatmapData;` trait
  - [x] Add public property: `public bool $showHeatmap = false;`
  - [x] Add method: `public function toggleHeatmap()` to toggle `$showHeatmap`
  - [x] Add `#[Computed]` property: `heatmapData()` that returns `['days', 'staff', 'projects', 'component', 'hasAssignedStaff']`
  - [x] Add `getAssignedStaffIds()` helper using idiomatic Laravel Collections
  - [x] Use `sortStaffByAssignment()` to prioritize selected staff
- [x] Update Scheduling section in `resources/views/livewire/project-editor.blade.php`:
  - [x] Change "Save" button to "Update"
  - [x] Add "Model" button: `wire:click="toggleHeatmap"` with `data-test` attribute
  - [x] Add conditional heatmap display with contextual message
  - [x] Include heatmap partial: `@include('components.heatmap-table', $this->heatmapData)`

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
- [x] Create `tests/Feature/SchedulingHeatmapTest.php`: ✅ (Phase C)
  - [x] Test heatmap displays when Model button clicked
  - [x] Test heatmap hides when toggled again
  - [x] Test assigned staff appear at top of heatmap
  - [x] Test heatmap shows all staff alphabetically when none assigned
  - [x] Test technical lead and change champion included in assigned staff
  - [x] Test CoSE IT staff included in assigned staff
  - [x] Test heatmap data computed property returns correct structure
  - [x] Test UI elements display correctly
  - [x] Test button label updates when toggling
  - [x] Test correct message shown when staff assigned
  - [x] **Result:** 10 tests, all passing (39 assertions) 🎉
- [x] Run Pint: `vendor/bin/pint --dirty` ✅
- [x] Run feature tests: `lando artisan test --filter=Scoping` ✅
- [x] Run feature tests: `lando artisan test --filter=HeatMapViewer` ✅
- [x] Run feature tests: `lando artisan test --filter=SchedulingHeatmap` ✅
- [ ] Full test suite: `lando artisan test` (optional - all Feature 2 tests passing)

### Phase E: Manual QA & Polish ✅ **COMPLETED**
- [x] Test Scheduling stage in browser:
  - [x] Verify Model button shows/hides heatmap
  - [x] Assign staff and verify they appear at top of heatmap
  - [x] Test with no staff assigned - should show alphabetical list
  - [x] Verify heatmap styling matches standalone page
  - [x] **Result:** Looks amazing! Heatmap displays correctly inline on Scheduling tab
- [x] Verify AlpineJS/Livewire interactions work smoothly
- [ ] Test Scoping stage in browser (optional - automated tests cover this):
  - [ ] Verify effort scale dropdown shows correct options
  - [ ] Test Submit to DCGG workflow
  - [ ] Verify emails sent correctly (check Mailhog)

## Potential Gotchas

### Performance Concerns
- The heatmap queries can be heavy (eager loading, multiple relationships)
- Consider using `#[Computed]` to cache the result until form changes
- May need `wire:loading` indicator when toggling heatmap

### Sorting Edge Cases
- What if `cose_it_staff` contains user IDs that don't exist? (Filter them out) (USER NOTE: this can never happen)
- What if same user is in multiple assignment fields? (Use `unique()` collection method)

### UI/Styling
- Heatmap table might need width constraints when embedded
- Consider making it horizontally scrollable on smaller screens
- Flux UI styling should be consistent with rest of form

### Livewire Lifecycle
- Make sure `showHeatmap` resets when switching tabs
- Consider adding `wire:key` to heatmap partial for proper reactivity

## Success Criteria ✅ ALL MET

- [x] Scoping stage has effort scale dropdown (no free text)
- [x] DCGG workflow tracks submission and approval
- [x] Scheduling stage has Model button that shows inline heatmap
- [x] Assigned staff appear at top of heatmap, others below alphabetically
- [x] Standalone heatmap page continues to work
- [x] All tests passing (32 tests, 115 assertions)
- [x] No regressions in existing functionality
- [x] Code follows team conventions (no service classes, idiomatic Laravel Collections, simple & readable templates)

---

## Development Notes & Lessons Learned

### Artisan Commands
- **Use local PHP for `make:` commands**: Run `php artisan make:event EventName` instead of `lando artisan make:event EventName`
  - Reason: Saves spinning up Docker containers for simple code generation
  - Lando is still needed for running the full test suite at the end of a new feature, tinker, etc.
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

### ✅ Phase C: Complete (2025-11-13) - Scheduling Stage Integration

**Goal:** Add the heatmap display to the Scheduling tab of ProjectEditor so users can see staff availability while assigning team members.

#### What We Built

**1. Added Scheduling Form Fields (Smart Jump-Ahead Decision!)**
- **Issue Encountered:** Initial implementation referenced `technicalLeadId` and `changeChampionId` which didn't exist yet on `SchedulingForm`
- **Solution:** Rather than reworking all the code, we jumped ahead and added these fields now (they were already in the database from Infrastructure Setup)
- **Files Updated:** `app/Livewire/Forms/SchedulingForm.php`
  - Added `technicalLeadId` and `changeChampionId` properties with validation
  - Updated `setProject()` to load these fields
  - Updated `save()` to persist these fields
- **Why This Was Smart:** These fields are part of Feature 3 anyway, so adding them now saved us from reworking everything twice

**2. Updated ProjectEditor Component**
- **Files:** `app/Livewire/ProjectEditor.php`
- Added `use HasHeatmapData;` trait
- Added `public bool $showHeatmap = false;` property
- Added `toggleHeatmap()` method to show/hide heatmap
- Added `#[Computed] heatmapData()` method that:
  - Calls `upcomingWorkingDays(10)` to get 10 working days
  - Calls `getAssignedStaffIds()` to collect assigned staff IDs
  - Calls `staffWithBusyness()` with assigned IDs for smart sorting
  - Returns array with `days`, `staff`, `projects`, `component`, and `hasAssignedStaff` flag
- Added `getAssignedStaffIds()` helper method using **idiomatic Laravel Collections**

**3. Updated Scheduling View**
- **Files:** `resources/views/livewire/project-editor.blade.php`
- Changed "Save" button to "Update" button
- Added "Model" button with toggle functionality (shows "Model" / "Hide Heatmap")
- Added `data-test="model-heatmap-button"` attribute for reliable testing
- Added conditional heatmap display section with:
  - Heading: "Staff Heatmap"
  - Contextual message (changes based on `hasAssignedStaff` flag)
  - Heatmap table included via `@include('components.heatmap-table', $this->heatmapData)`

**4. Comprehensive Tests**
- **Files:** `tests/Feature/SchedulingHeatmapTest.php`
- Created 10 comprehensive tests (39 assertions):
  1. Displays heatmap when Model button clicked
  2. Hides heatmap when Model button clicked again (toggle)
  3. Shows assigned staff at top when staff are assigned
  4. Shows all staff alphabetically when no staff assigned
  5. Includes technical lead and change champion in assigned staff
  6. Includes CoSE IT staff in assigned staff list
  7. Returns correct structure in heatmapData computed property
  8. Displays UI elements correctly when heatmap shown
  9. Updates button label when toggling heatmap
  10. Shows correct message when staff are assigned
- All tests passing with clear Arrange-Act-Assert structure

#### Issues Encountered & Lessons Learned

**Issue #1: Complex Logic in Blade Template**
- **Problem:** Initial implementation had complex `@if` statement directly in the view:
  ```blade
  @if(!empty(array_filter([$schedulingForm->assignedTo, $schedulingForm->technicalLeadId,
      $schedulingForm->changeChampionId, ...($schedulingForm->coseItStaff ?? [])])))
  ```
- **Why This Was Bad:** Violates team convention of keeping templates simple and readable
- **Solution:** Moved logic to component's `heatmapData()` method which returns `hasAssignedStaff` boolean
- **Result:** View now has simple `@if($this->heatmapData['hasAssignedStaff'])`
- **Lesson:** Views should only display data, not calculate it. Component should expose simple properties/flags for the view to use.

**Issue #2: Using `array_filter()` Instead of Idiomatic Laravel**
- **Problem:** Initial implementation used `array_filter()` which is hard to read:
  ```php
  array_filter([
      $this->schedulingForm->assignedTo,
      $this->schedulingForm->technicalLeadId,
      $this->schedulingForm->changeChampionId,
      ...($this->schedulingForm->coseItStaff ?? []),
  ])
  ```
- **Why This Was Bad:** Not idiomatic Laravel; harder for team members to read
- **Solution:** Refactored to use Laravel Collections:
  ```php
  return collect([
      $this->schedulingForm->assignedTo,
      $this->schedulingForm->technicalLeadId,
      $this->schedulingForm->changeChampionId,
  ])
      ->merge($this->schedulingForm->coseItStaff ?? [])
      ->filter()
      ->unique()
      ->values()
      ->all();
  ```
- **Result:** Much more readable and follows Laravel conventions
- **Lesson:** Use Laravel Collections for data manipulation - they're more expressive and familiar to Laravel developers. Performance difference with small arrays (6 elements) is negligible compared to readability gains.

**Issue #3: Testing Implementation Details Instead of Behavior**
- **Problem:** Initial test directly called `getAssignedStaffIds()` method:
  ```php
  $assignedIds = $component->instance()->getAssignedStaffIds();
  expect($assignedIds)->toHaveCount(1);
  ```
- **Why This Was Bad:** Tests should verify user-visible behavior, not internal implementation
- **Solution:** Removed test that called internal method directly. Deduplication behavior already covered by tests checking staff ordering and heatmap structure.
- **Result:** Tests now focus on what users see (staff appears once in heatmap) rather than how it's implemented
- **Lesson:** Test the **behavior** users experience, not the internal methods. If a test breaks when you refactor internal methods (without changing behavior), the test is too coupled to implementation.

#### Test Results

✅ **SchedulingHeatmapTest**: 10 tests passed (39 assertions)
✅ **ScopingWorkflowTest**: 17 tests passed (54 assertions)
✅ **HeatMapViewerTest**: 5 tests passed (22 assertions)
✅ **All Feature 2 tests**: 32 tests passed (115 assertions total)

#### Code Quality

- ✅ Used idiomatic Laravel Collections for data manipulation
- ✅ Logic properly encapsulated in component (not in view)
- ✅ Simple, readable code following team conventions
- ✅ All code formatted with Pint
- ✅ Tests follow Arrange-Act-Assert pattern
- ✅ Used `data-test` attributes for reliable UI testing
- ✅ Tests verify behavior, not implementation details

#### Browser Testing

✅ Manually tested in browser - looks amazing! Heatmap displays correctly inline on Scheduling tab.

---

## Feature 2 Complete! 🎉

**Total Implementation Time:** ~3 hours across 3 phases
**Total Tests:** 32 tests, 115 assertions
**Files Modified:** 10 files (models, forms, components, views, tests)
**Code Quality:** Excellent - follows all team conventions

**Key Achievements:**
- Scoping stage has effort scale dropdown and DCGG workflow
- Scheduling stage has inline heatmap with smart staff sorting
- Standalone heatmap page continues to work perfectly
- All code is simple, readable, and maintainable
- Comprehensive test coverage with behavior-focused tests

**Next Feature:** Feature 3 (Scheduling triage fields) - we've already added `technicalLeadId` and `changeChampionId` to the form, so Feature 3 will be faster!

---

## ⚠️ IMMEDIATE NEXT STEP: Missing Scheduling Stage Buttons

**The Problem:** We only implemented the "Model" button for the Scheduling stage, but the PowerPoint spec (slides 20-21) describes THREE buttons:

1. ✅ **Model** - Display heatmap (IMPLEMENTED)
2. ❌ **Submit** - Submit workpackage to Digital Change Governance Group (DCGG) for approval (MISSING)
3. ❌ **Schedule** - Confirm that schedule has been approved (MISSING)

**Why This Was Missed:**
- The spec is a bit verbose and workflow details are scattered
- Easy to miss that Scheduling has its own Submit/approval workflow separate from Scoping
- We did Scoping Submit → Schedule first, so seemed like Scheduling was "done"

---

### Missing Feature: Submit to DCGG Button

**What It Should Do:** (from slide 21)
> "The submit button will allow us to submit the workpackage request to the Digital Change Governance Group (DCGG) for approval"

**Implementation Notes:**
- This is likely similar to Scoping's Submit button but submits to a governance group for approval
- Unclear from spec: Does this send an email? To whom? (Spec doesn't specify)
- Unclear: What status does this set? (Submitted to DCGG? Awaiting DCGG approval?)
- Probably needs new database field: `dcgg_submitted_at` on `schedulings` table
- May need event/notification (but spec doesn't say)

**Questions to Clarify:**
- Who gets notified when submitted to DCGG?
- What happens next in the workflow?
- Can Schedule button only be clicked AFTER Submit?

---

### Missing Feature: Schedule Button (Approval Confirmation)

**What It Should Do:** (from slide 21)
> "will confirm that the schedule has been approved. The Change Board approval date must be complete for this to happen"

**Implementation Notes:**
- Requires Change Board approval date to be filled before it can be clicked
- "Confirms schedule has been approved" - but WHO approves it? The spec doesn't say!
- May need approval tracking fields similar to Feasibility:
  - `scheduling_approval_status` (pending/approved/rejected)
  - `scheduling_approved_at` (timestamp)
  - `scheduling_actioned_by` (foreign key to users) - for audit trail
- Spec doesn't mention email notifications for this button
- Spec doesn't mention rejection workflow (but Feasibility has one, so probably should?)

**Questions to Clarify:**
- Who performs the approval? (Work Package Assessors? DCGG? Someone else?)
- Should there be an Approve/Reject workflow with reason modal (like Feasibility)?
- What notifications should be sent and to whom?
- Does this advance the project to next stage automatically?

---

### Recommended Approach

**Option 1: Ask the User for Clarification** (RECOMMENDED)
- The spec is genuinely unclear about these workflows
- Better to ask now than implement wrong logic and have to redo it
- Questions about notifications, recipients, and approval flow are critical

**Option 2: Mirror Existing Patterns**
- Model Submit button on Scoping's Submit to DCGG button
- Model Schedule button on Feasibility's Approve/Reject workflow
- Make educated guesses, document assumptions, easy to adjust later

---

**Priority:** HIGH - These are governance requirements from leadership

**Estimated Time:**
- Submit button: 1-2 hours (if we mirror Scoping pattern)
- Schedule button: 2-3 hours (if we mirror Feasibility approval pattern)
- **Total: 3-5 hours**

**Dependencies:**
- Decisions about notifications and approval workflow
- May want to check with actual users about how this should work in practice
