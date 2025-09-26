# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains Laravel services, Livewire features, and jobs; share helpers under `App\\Support` when reuse emerges.
- `routes/web.php` and `routes/api.php` map HTTP entry points; pair new controllers with request classes and policies.
- `resources/js`, `resources/views`, and `resources/css` hold the Vite + Tailwind front end; builds land in `public/build`.
- Database migrations and seeds live under `database/`; rerun `lando mfs` after schema or seed changes to rebuild the stack.
- Container config resides in `docker/`, `.lando.yml`, and stack YAML files; edit alongside infrastructure-impacting work.

## Build, Test, and Development Commands
- `lando start` / `lando stop` manage the local container stack.
- `composer dev` launches `php artisan serve`, the queue listener, Pail, and Vite in one terminal.
- `npm run dev` watches assets and `npm run build` emits production bundles when running outside Lando.
- `lando composer test` or `php artisan test` run the Pest suite; add `--filter` or `--coverage` for focused runs.
- `vendor/bin/pint` enforces PHP style before commits.

## Coding Style & Naming Conventions
- Honor `.editorconfig`: LF endings, UTF-8, four-space indentation (two for YAML), and a final newline.
- Follow PSR-12; classes use StudlyCase, config keys snake_case, and Livewire components end in `Component`.
- Tests live in `tests/Feature` with descriptive `*Test.php` names that mirror the behavior under test.
- When returning views, pass data with explicit short array syntax (`return view('...', ['key' => $value])`) instead of `compact()`.
- Avoid Blade `@php` blocks; move logic into components, view models, or dedicated helpers before rendering.

## Testing Guidelines
- Pest with Laravel helpers backs the suite; favor factories and database refresh traits over manual fixtures.
- Keep tests deterministic and fast, faking queues with `Bus::fake()` when jobs would slow feedback loops.
- CI (`.github/workflows/main.yml`) runs phpunit through `phpunit.github.xml`; migrations and seeds must pass in a clean container.

## Commit & Pull Request Guidelines
- History favors `wip - <summary>` while iterating; squash to an imperative final message such as `feat: add busyness heatmap` before merge.
- Reference issue IDs, flag schema or queue changes, and call out Docker or Lando adjustments.
- PRs should include purpose, screenshots for UI updates, test results (`php artisan test` or coverage snippet), and rollout notes for Horizon queues.

## Environment & Configuration Notes
- Copy `.env.example` or rely on `lando mfs` to rebuild the database; never commit secrets.
- Dockerfile stages feed `prod-stack.yml` and `qa-stack.yml`; keep variables in sync when they change.
- Horizon manages queues in production, so document expected retries and timeouts when introducing new jobs.
