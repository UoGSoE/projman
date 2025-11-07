# Review Findings

## P1 – Unauthorized Project Editing
- **File**: `routes/web.php:9-16`, `app/Livewire/ProjectEditor.php:64-123`
- **Issue**: `/project/{project}/edit` only uses the `auth` middleware and `ProjectEditor::mount()` never authorizes against a policy. Any logged-in user can load another user’s project editor and trigger Livewire actions that mutate the project.
- **Impact**: Users can modify any project, including admin-only stages, which is a severe privilege escalation.
- **Fix**: Protect the route with `can:update,project` (or similar) and/or call `$this->authorize('update', $project)` inside `mount()` so unauthorized users are rejected server-side.

## P1 – Hidden Tabs Still Executable
- **File**: `resources/views/livewire/project-editor.blade.php:8-571`, `app/Livewire/ProjectEditor.php:96-123`
- **Issue**: The `@admin` directive hides certain `<flux:tab>` headers, but their `<flux:tab.panel>` blocks and Livewire handlers (`save`, `advanceToNextStage`) remain active. Because `$tab` is bound via `#[Url]` and `save($formType)` trusts user input, non-admins can hit `/project-editor?tab=development` or call `save('development')` directly to edit admin stages.
- **Impact**: Security-through-obscurity; unauthorized users can still invoke all actions that were meant to be admin-only.
- **Fix**: Mirror the `@admin` guard on the panel markup and, more importantly, enforce authorization within the Livewire component (e.g., validate `$formType` against allowed stages for the current user and abort if unauthorized).

## P2 – Scheduling Stage Cannot Be Saved
- **File**: `app/Livewire/Forms/SchedulingForm.php:32-34`, `resources/views/livewire/project-editor.blade.php:279`
- **Issue**: `schedulingForm.keySkills` is validated as `required|string|max:1024`, yet the corresponding field in the Blade template has been commented out. Users cannot provide a value, so validation always fails when saving the scheduling form.
- **Impact**: Scheduling data cannot be persisted, blocking the project workflow.
- **Fix**: Restore the key-skills input (or seed a default) or relax the validation rule so the form can be submitted.

## P2 – Feasibility “Date Assessed” Must Be in the Future
- **File**: `app/Livewire/Forms/FeasibilityForm.php:31-36`
- **Issue**: The `dateAssessed` field uses the rule `required|date|after:today`. Once an actual assessment date (today or earlier) is stored, subsequent saves fail validation, preventing any further edits to feasibility data.
- **Impact**: Real-world assessments cannot be recorded or updated because they are inherently historic.
- **Fix**: Change the rule to `before_or_equal:today` (or remove the temporal constraint) so historic dates remain valid.

---

## P1 – Missing Project-Level Authorization
- **Files**: `routes/web.php:7-22`, `app/Livewire/ProjectViewer.php:12-20`, `app/Livewire/ProjectEditor.php:96-123`, `app/Livewire/ProjectStatusTable.php:45-77`
- **Issue**: Every project route is only behind `auth`, and none of the Livewire components call `$this->authorize()` or policies. Any authenticated user can view, edit, advance, or cancel someone else’s project by visiting the URLs or invoking the Livewire actions directly.
- **Impact**: Full privilege escalation—non-owners and non-admins can tamper with senior leadership projects.
- **Fix**: Introduce `ProjectPolicy` with `view`, `update`, `cancel`, etc., gate the routes with `can:*` middleware, and authorize inside component `mount()`/actions.

## P1 – Admin Tabs Are Only Hidden, Not Protected
- **Files**: `resources/views/livewire/project-editor.blade.php:10-570`, `app/Livewire/ProjectEditor.php:96-123`
- **Issue**: The `@admin` directive hides tab headers, but panels are still rendered and `save($formType)` / `advanceToNextStage()` accept any string from the client. Non-admins can post `save('development')` or `advanceToNextStage` calls and mutate restricted stages.
- **Impact**: Security-through-obscurity—any user can perform admin operations by crafting Livewire requests.
- **Fix**: Mirror the guard on panels or, preferably, check the authenticated user inside `save()`/`advanceToNextStage()` (e.g., whitelist allowed forms per role) and reject unauthorized requests server-side.

## P1 – Cancel Action Exposed to All Users
- **Files**: `app/Livewire/ProjectStatusTable.php:45-77`, `resources/views/livewire/project-status-table.blade.php:82-104`
- **Issue**: The “All Projects” view is accessible to every authenticated user, and its dropdown invokes `cancelProject()` without authorization checks.
- **Impact**: Anyone can cancel any project—including executive initiatives—by triggering the Livewire action.
- **Fix**: Authorize cancellation (owner/admin only), hide controls when unauthorized, and add policy-backed tests.

## P2 – Date Rules Block Real-World Updates
- **Files**: `app/Livewire/Forms/FeasibilityForm.php:31-36`, `app/Livewire/Forms/SchedulingForm.php:37-44`
- **Issue**: `dateAssessed`, `estimatedStartDate`, and `changeBoardDate` must always be in the future (`after:today`). Once the date passes, validation fails and the form can never be saved again.
- **Impact**: Feasibility and scheduling data become read-only immediately after the relevant milestone.
- **Fix**: Allow historical dates (`before_or_equal:today` / `after_or_equal:today`) or drop the constraint entirely.

## P2 – Required Fields Missing in the UI
- **Scheduling Key Skills**: `app/Livewire/Forms/SchedulingForm.php:31-33` still marks `keySkills` required, but the corresponding input is commented out in the Blade (`resources/views/livewire/project-editor.blade.php:279-287`). Saves always fail.
- **Testing Titles**: `app/Livewire/Forms/TestingForm.php:19-29` requires `functionalTestingTitle` and `nonFunctionalTestingTitle`, yet the view never renders inputs for them. Users cannot satisfy validation.
- **Fix**: Reintroduce the fields or relax the validation rules to match what the UI actually collects.

## P2 – Notification Rules Cannot Target Recipients
- **Files**: `resources/views/livewire/notification-rules.blade.php:71-104`, `resources/views/livewire/notification-rules-table.blade.php:185-209`, `app/Livewire/NotificationRules.php:115-135`, `app/Livewire/NotificationRulesTable.php:176-210`
- **Issue**: Role and user pillboxes are rendered with `:disabled="true"`, so the selection controls are inert. Saving results in empty `recipients` arrays, and `SendEmailJob` therefore sends nothing.
- **Impact**: The notification system is unusable via the UI; only database seeding or Tinker could add recipients.
- **Fix**: Enable the inputs, validate selections, and ensure the backend rejects empty payloads.

## P2 – Sign-Off Fields Store IDs but UI Offers Text Labels
- **Files**: `tests/Feature/ProjectCreationTest.php:283-454`, `resources/views/livewire/project-editor.blade.php:232-262`, `resources/views/livewire/project-editor.blade.php:524-558`
- **Issue**: Feature tests expect sign-off fields (`approval_delivery`, `testing_sign_off`, etc.) to hold user IDs, but the UI only exposes static status options (Pending/Approved/Rejected). Users can’t pick people, so production data will fail existing tests or vice versa.
- **Impact**: The domain model and UI disagree; QA cannot rely on either.
- **Fix**: Decide whether these fields store people or states and update both validation and UI accordingly. For people, show user pickers; for states, adjust tests/models to store enums instead of IDs.

## P2 – User Pickers Silently Truncate Options
- **Files**: `app/Livewire/ProjectEditor.php:125-148`, `resources/views/livewire/project-editor.blade.php` (various selectors)
- **Issue**: `availableUsers()` caps results at 20 and only filters when `$userSearch` has 2+ characters, but no search box is wired up. Large departments cannot assign users outside the first 20 records.
- **Impact**: Project fields (assessed by, designers, test leads, etc.) become unusable once the user table grows.
- **Fix**: Add a `wire:model.live="userSearch"` input and/or paginate results so every user remains selectable.

## P2 – Seeder Duplicates Notification Rules
- **File**: `database/seeders/TestDataSeeder.php:851-884`
- **Issue**: `updateOrCreate` matches on `'event->class' => 'project.created'` while the stored value is the FQCN. The where clause never matches, so every seed run creates a new “Project Created” rule.
- **Impact**: Environments seeded multiple times end up with dozens of duplicate rules and redundant emails.
- **Fix**: Match against the FQCN (`\App\Events\ProjectCreated::class`) or restructure the column into discrete fields that can be indexed.

## P2 – Advance Buttons Double-Submit
- **File**: `resources/views/livewire/project-editor.blade.php:57-68` (and repeated in every panel)
- **Issue**: “Advance to Next Stage” buttons live inside forms without `type="button"`. Clicking them fires both the form submission (running `save`) and `wire:click="advanceToNextStage"`, so projects advance even when validation fails.
- **Impact**: Users can accidentally push projects forward with invalid data; Livewire sees two conflicting actions.
- **Fix**: Mark the advance buttons as `type="button"` and gate `advanceToNextStage()` behind successful validation/authorization.

## P3 – UX/Complexity & Simplicity Guidelines
- **Filters Don’t Work**: `ProjectStatusTable` exposes search/status/school filters but ignores them in `getProjects()`. Either implement the scopes or remove the dead UI to keep things simple and predictable.
- **“Export” Mislabelled**: `/projects` shows an “Export” button that points to `project.create`. Rename/remove to avoid confusion.
- **MyProjects Hard Codes an Admin**: `App\Livewire\MyProjects` loads the first admin’s projects instead of the authenticated user, which is surprising and violates “obvious Laravel code” expectations.
- **Notification UI Complexity**: Two separate Livewire components share state (modal in `NotificationRules`, table in `NotificationRulesTable`). Because the table handles edit/delete, but creation lives elsewhere, state synchronization is brittle. Consider collapsing into a single component or using events so the codebase stays maintainable.
- **Forms Rely on Magic Strings**: `save($formType)` converts arbitrary input into enum cases without guarding unknown values or tying them to policies. Refactoring toward explicit methods per stage (or a strategy map keyed by enum cases) would be clearer and easier to audit.

## Testing / Observability Gaps
- No policy/authorization coverage around viewing/editing/canceling projects or accessing admin stages.
- Notification-rule tests only exercise backend validation; there are no UI tests proving recipient selection works.
- Seeder behavior isn’t tested at all, so the duplicate-rule bug would have gone unnoticed.
