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
