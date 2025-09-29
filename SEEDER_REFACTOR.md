# Seeder Refactor Notes

These are my current thoughts on how we could simplify the seeding story while keeping the data richness you need for demos and exploratory testing. The current seed file is `database/seeders/TestDataSeeder.php`; CI/CD assumes that file remains the entry point.

## Current Code Touchpoints
- `database/seeders/TestDataSeeder.php`
  - `seedProjectPortfolio()` drives per-user project creation and calls the helper stack.
  - `seedStageData()`, `ensureTeamForInFlightProject()`, and `ensureStagePlaceholders()` each mutate the same stage relations; the order they run in explains many of the unexpected overwrites.
  - Helpers like `projectTimeline()`, `randomStaffIds()`, `sampleSkillIds()`, and `skillNamesFor()` hold the timeline/team/skill decisions.
- Stage factories in `database/factories/` (e.g., `SchedulingFactory`, `DevelopmentFactory`) already scaffold fake data and are good homes for richer scenarios.
- UI dependencies to remember:
  - Project editor (`resources/views/livewire/project-editor.blade.php`) expects every stage relation to exist and `scoping.skills_required` to be an array of real skill IDs so it can compute skill scores.
  - Heatmap (`resources/views/livewire/heat-map-viewer.blade.php`) renders badges from `busyness_week_1/2` and the scheduling relation (`assigned_to`, `cose_it_staff`). Testing in the browser surfaces team-size issues quickly.

## Goals Recap
- Generate believable project portfolios quickly (`lando mfs` - resets the database and runs the seeder inside the lando dev docker containers).
- Keep every screen (heatmap, project editor, history views) populated with coherent data.
- Make the seeding code easy to reason about and tweak without cascading regressions.

## Pain Points In The Current Seeder
- One file is responsible for catalogues (roles/skills), staff creation, project scenarios, team assignment, stage placeholders, and history. That results in deeply nested logic and repeated writes to the same relations.
- Stage data is created in multiple passes (`seedStageData`, `ensureTeamForInFlightProject`, `ensureStagePlaceholders`), so it’s hard to know which step “wins”.
- The seeder operates on live Eloquent models, mutating them as it goes (using `setRelation`, `forceFill`, `firstOrCreate`). This hides the actual data shape and encourages accidental overwrites.
- Random decisions are scattered. Skills, teams, and scheduling are each re-randomized by different helpers, which is why subtle adjustments (e.g. team size) are painful.

## Proposed Structure (Top Level)
```
Database\Seeders\
  ├── CatalogueSeeder       # roles, skills, static lookup data
  ├── StaffSeeder           # core admin/staff accounts, role/skill assignments
  ├── ProjectScenarioSeeder # orchestrates project creation per staff member
  └── TestDataSeeder        # calls the three above
```

Each file should read like a story: "seed catalogue", "seed staff", "seed projects". No helper should reach across those boundaries.

## Project Scenario Design
- Create a `ProjectScenarioFactory` (or service class) that returns a `ProjectScenarioData` object. The object should describe:
  * owner user
  * status
  * timeline (created / updated / deadline)
  * stage payloads (arrays ready for `createMany`/`create`)
  * team roster (assigned + extras) and skill IDs
  * history entries (list of timestamps + descriptions)
- The seeder iterates over staff, asks the factory for N scenarios, and persists each scenario once. No subsequent mutation loops.
- This is purely developer code - so should live within the database/seeders (Database/Seeders namespace)

### Why
- We can unit test the scenario factory separately from seeding.
- All random choices (skills, staff, dates) live in one place.
- Persisting becomes mechanical: `Project::create([...])->ideation()->create($scenario->ideationData)`.

## Stage Factories
- Each stage model should expose factory states (`withAssessor`, `withLead`, etc.) or accept prepared arrays. Keep default fake content in the factory, not the seeder.
- For example, `Scheduling::factory()->for($project)->state(ProjectTeam::make($owner)->toSchedulingData())`.

## Team & Skill Helpers
- Introduce a `ProjectTeamBuilder` class that, given staff collection and required skill IDs, returns:
  * `assigned_user_id`
  * `team_member_ids` (excluding owner)
  * label string for `key_skills`
- Reuse it everywhere we need a team. No more improvising inside the seeder.

## History Generation
- Keep the feature—just encapsulate it. A `ProjectHistoryFactory::times($n)->between($start, $end)` utility can spit out the rows, and the seeder can call `$project->history()->createMany($historyRows)`.

## Busyness Calculation
- After projects are seeded, a dedicated `BusynessCalculator` service (or even a query) can update `busyness_week_1/2` in one shot. That keeps the main seeder from looping through all users again.

## Migration Path
1. Extract catalogue + staff seeding into separate files (no behavioural change).
2. Introduce a basic `ProjectScenario` object and migrate one status at a time into the new pipeline. Keep the old helper around for statuses you haven’t ported yet.
3. Once all statuses are handled by the scenario builder, delete the shell of the old helper functions.
4. Layer in the team builder and history builder to centralise logic.
5. Finally, simplify `TestDataSeeder` so it only orchestrates the three high-level seeders and triggers the busyness recalculation.

## Risks & Mitigations
- **Risk:** Breaking assumptions in Livewire forms that expect all stage records to exist.
  * Mitigation: The scenario builder should produce every stage row upfront (or we run a dedicated placeholder pass with static data). Either way, do it knowingly rather than implicitly inside helper functions.
- **Risk:** Losing the "richness" of the current data.
  * Mitigation: Keep the factories expressive—use Faker to produce diverse text, but drive structure from deterministic helpers (team size ranges, timeline ranges).
- **Risk:** Time to rewrite.
  * Mitigation: Incremental refactor—extract piece by piece instead of rewriting from scratch. The new scenario builder can coexist with the old logic until parity is reached.

## Quick Wins If Full Refactor Has To Wait
- Cut the placeholder pass (`ensureStagePlaceholders`) and instead create empty relations lazily when a form is first opened. That immediately removes a major source of duplicated logic.
- Collapse `seedStageData` + `ensureTeamForInFlightProject` into one function per stage (e.g. `seedSchedulingFor(Project $project)`).
- Store team sizes explicitly (`team_member_count`) during seeding so debugging is easier.
- Known outstanding bug: team size still trends toward five because later passes rewrite `cose_it_staff`. When testing, keep an eye on the heatmap and project editor badge rows to gauge progress.
