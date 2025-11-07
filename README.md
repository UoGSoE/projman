# Projman

> Demand tracking, skill-aware staffing, and delivery governance for a small IT team.

Projman keeps incoming work visible from the first idea through deployment. It couples multi-stage project forms with skill tracking, staff heatmaps, and configurable notifications so managers can see demand, route requests, and keep everyone in the loop.

## Features
- **Work package intake to deployment** – Livewire forms capture ideation, feasibility, scoping, scheduling, detailed design, development, testing, and deployment data in one place, complete with history and stage progression.
- **Skill-aware staffing** – Skill inventories, proficiency levels, and per-project skill requirements feed the project editor so you can shortlist the best-fit people before committing.
- **Staff heatmap & busyness tracking** – A ten-day working calendar uses `Busyness` enums plus current assignments to highlight overloaded teammates and at-risk deadlines.
- **Role & access administration** – Dedicated screens let admins search, sort, and edit users, their roles, and admin flag status with guardrails enforced via middleware and policies.
- **Notification rules engine** – Business users can describe who should be emailed for each event or stage change; rules feed an event-driven queue job so alerts scale with demand.
- **Audit-ready history** – Every save or status transition records who made the change and when, so project reviews and RCA work never start from scratch.
- **Keycloak-friendly authentication** – Optional SSO login with fallbacks to local credentials for development, plus feature flags to restrict student access or admin-only modes.

## Stack
- **Laravel 12** with Livewire v3 and Flux UI Pro for server-driven, reactive interfaces.
- **MySQL + Redis** via `lando` for persistence, queueing, and cache.
- **Queued mail** powered by Laravel Horizon-ready jobs and Markdown mailables.
- **Tailwind CSS & Vite** for styling and asset bundling.
- **Pest** for feature-focused test coverage.

## Core Screens & Flows
- **Home** – Personal greeting with a filtered Project Status Table and shortcut to start a new work package.
- **Project lifecycle** – `ProjectCreator`, `ProjectEditor`, and `ProjectViewer` manage creation, per-stage editing, advancing workflow states, and reviewing the audit log.
- **Portfolio view** – `/projects` lists every work package with progress badges, action menus, and filters.
- **Staff heatmap** – `/staff/heatmap` combines busyness enums with active project assignments and links straight to user profiles.
- **Skills & roles management** – Admin-only Flux-powered tables provide search, pagination, modal edit flows, and validations for both skills and roles.
- **Notification rules** – Configurable recipient lists (users or roles), optional per-stage filters, and modal CRUD actions feed the notification job.
- **Profile** – Each teammate can self-report skills, toggle proficiency levels, and update their two-week busyness forecast.

## Architecture Highlights
- **Enums everywhere** – `ProjectStatus`, `Busyness`, and `SkillLevel` keep UI badges and validation consistent between PHP and Blade.
- **Stage models per project** – Each lifecycle phase owns its table/model and Livewire `Form`, allowing focused validation, eager loading, and clean auditing.
- **Event-driven alerts** – `ProjectCreated` and `ProjectStageChange` raise events consumed by listeners that hydrate `NotificationRule` recipients before dispatching `SendEmailJob`.
- **Skill matching helper** – The project editor queries users by required skills, scores proficiency numerically, and sorts candidates for scheduling.
- **Seed data for demos** – `TestDataSeeder` provisions roles, skills, staff, projects, and sample notification rules so `lando mfs` yields a realistic sandbox with `admin2x / secret`.

## Testing
- Feature coverage is implemented with Pest (`tests/Feature/*`). To run the suite inside Lando use:

```bash
lando test
# or outside Lando
php artisan test
```

The queue-driven notifications, Livewire components, and mailables all have dedicated tests. Add or update tests alongside any new behavior.

## Local Development
- We rely on [Lando](https://lando.dev) for the full stack (PHP, MySQL, Redis, Mailhog, Node). A detailed quick-start lives in `DEVELOPMENT.md`, but the short version is:
  1. Copy `.env.example` to `.env`.
  2. `lando start` to boot the containers.
  3. `lando mfs` to drop/migrate/seed with rich demo data.
  4. Sign in with `admin2x / secret` or toggle SSO via `SSO_ENABLED`.

## Project Links
- GitHub: <https://github.com/UoGSoE/projman>
- Default Herd URL: <http://projman.test> (served via Laravel Herd on macOS).
