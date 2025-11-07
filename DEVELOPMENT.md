# Local Development Guide

This application is developed and tested entirely inside [Lando](https://lando.dev), so you get PHP, MySQL, Redis, Mailhog, and Node without polluting your host machine.

## Prerequisites
- Lando 3.20+ (with Docker Desktop or Lima as its runtime)
- Make sure `projman` is cloned locally and that you can run Docker containers.

## First-Time Setup
1. **Install dependencies**
   ```bash
   composer install
   npm install
   npm run build
   ```
   (You can run these commands either on your host or via `lando composer install` / `lando npm install`.)
2. **Copy the environment file**  
   `.env.example` ships with local-friendly defaults (MySQL `laravel/laravel`, Redis, Mailhog, Keycloak placeholders). Copy it straight across:
   ```bash
   cp .env.example .env
   ```
   If you do not have a Keycloak instance handy, set `SSO_ENABLED=false` to unlock the local username/password form.
3. **Boot the stack**
   ```bash
   lando start
   ```
   This creates the appserver (PHP 8.4), database, cache, mail, and node services defined in `.lando.yml`.
4. **Create/migrate/seed the database**
   ```bash
   lando mfs
   ```
   The custom `mfs` tooling command runs `php artisan migrate:fresh` followed by `php artisan db:seed --class=TestDataSeeder`, giving you a realistic dataset with roles, skills, projects, and notification rules.
5. **Log in**
   - Default admin: `admin2x / secret`
   - Default staff sample: `staff2x / secret`
   These credentials are only present locally via the `TestDataSeeder`.

### Testing & QA
- Run the Pest suite inside Lando: `lando test`
- Format PHP before commits: `lando composer exec vendor/bin/pint -- --dirty`
- Horizon is available with `lando horizon` if you need to watch queue processing.

### Databases & Seeding
- Need a clean slate? Re-run `lando mfs`.
- To only run outstanding migrations: `lando artisan migrate`.
- Mailhog is exposed on the forwarded port defined in `.lando.yml`, so you can verify notification rules visually.

### Toggling SSO
- `.env` controls all SSO behavior via `SSO_*` keys (see `config/sso.php`).
- Set `SSO_ENABLED=false` locally unless you have a Keycloak realm configured; the seeded credentials continue to work regardless.

With these steps you can go from clone to a fully seeded environment (including skills, roles, projects, notification rules, heatmap data, and default logins) in a couple of commands.
