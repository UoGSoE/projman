# Repository Guidelines

## Project Purpose
- Stage-gates work packages from ideation through deployment with Flux-driven forms.
- Assigns owners, effort, and timelines while surfacing workload via the staff heatmap.
- Captures team skills so schedulers can match people to work.

## Project Structure & Module Organization
- `app/` holds domain logic, HTTP controllers, Livewire components, and queued jobs; group new code by feature namespaces.
- `resources/` houses Blade views, client helpers, and Tailwind layers; mirror backend naming for clarity.
- `routes/web.php` and `routes/api.php` define entry points; queue and Horizon routes live in `routes/console.php`.
- `database/migrations` and `database/seeders` maintain schema; keep factories in `database/factories` for test data.
- `tests/Feature` and `tests/Unit` host Pest suites that shadow application feature areas.

## Build, Test, and Development Commands
- `composer install && npm install` installs PHP and Vite dependencies.
- `composer dev` launches serve, queue, log tail, and Vite processes together.
- `php artisan serve` starts HTTP only; pair with `npm run dev` for HMR.
- `npm run build` emits production assets to `public/build`.
- `php artisan migrate --seed` applies schema changes; add `--graceful` for shared envs.
- `php artisan test` or `./vendor/bin/pest --coverage` runs the Pest suite.

## Coding Style & Naming Conventions
- Follow PSR-12 with 4-space indentation in PHP and Blade; use 2 spaces in `resources/js`.
- Run `./vendor/bin/pint` before pushing; keep Tailwind classes purposeful and grouped.
- Name Livewire components in `App\\Livewire` with StudlyCase classes and kebab-case Blade stubs (e.g., `user-profile.blade.php`).
- Use Laravel generators (`php artisan make:*`) to scaffold boilerplate and keep namespaces consistent.

## Testing Guidelines
- Prefer Pest `it()` blocks with descriptive sentences, stored alongside feature folders (`tests/Feature/<Area>Test.php`).
- Mock external services via fakes or factories; seed only what the spec needs.
- Run the full suite before PRs; add regression coverage for every bug fix.

## Commit & Pull Request Guidelines
- Use conventional, lowercase prefixes (`feat:`, `fix:`, `wip:`) mirroring current history; keep subjects imperative and under 72 chars.
- Squash noisy commits locally; separate migrations, seeds, and UI changes when practical.
- PRs should include a summary, linked issues, manual test notes, and UI screenshots; flag migrations or breaking changes.

## Environment & Configuration Tips
- Copy `.env.example` to `.env`, update database credentials, then run `php artisan key:generate`.
- Horizon and queues rely on a running worker (`php artisan queue:listen --tries=1`); ensure Redis is available locally or via Docker.
