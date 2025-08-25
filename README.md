# ProjMan

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Local Development Setup with Lando

This project uses [Lando](https://lando.dev/) for local development, which provides a consistent development environment across different operating systems.

### Prerequisites

- [Lando](https://docs.lando.dev/getting-started/installation.html) installed on your system
- [Git](https://git-scm.com/) for version control
- [Composer](https://getcomposer.org/) for PHP dependency management (optional, Lando handles this)
- [Node.js](https://nodejs.org/) and [npm](https://www.npmjs.com/) (optional, Lando handles this)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd projman
   ```

2. **Start Lando**
   ```bash
   lando start
   ```

3. **Install dependencies and setup the application**
   ```bash
   lando composer install
   lando npm install
   lando mfs  # This runs migrations and seeds the database
   ```

4. **Access your application**
   - **Main App**: [http://projman.lndo.site:8000](http://projman.lndo.site:8000) or [https://projman.lndo.site:4433](https://projman.lndo.site:4433)
   - **MailHog**: [http://localhost:56087](http://localhost:56087) (for email testing)

## Testing

This project uses Pest for simple & expressive testing. [Pest](https://pestphp.com/).

### Running Tests

#### Using Lando

```bash
# Run all tests using Pest (working command)
lando composer test

# Run specific test file (use artisan directly)
lando artisan test tests/Feature/RolesListTest.php

# Run multiple test files (use artisan directly)
lando artisan test tests/Feature/RolesListTest.php tests/Feature/UserRoleManagementTest.php

# Run tests with specific filter (use artisan directly)
lando artisan test --filter="Role Display with User Counts"

# Run tests with coverage (if configured)
lando artisan test --coverage
```

#### Using PHP Artisan Directly

```bash
# Run all tests using Laravel's test command
php artisan test

# Run specific test file
php artisan test tests/Feature/RolesListTest.php

# Run tests in specific directory
php artisan test tests/Feature/

# Run tests with specific filter
php artisan test --filter="Role Display with User Counts"

# Run tests with verbose output
php artisan test --verbose
```

#### Using Composer Directly

```bash
# Run all tests (no arguments supported)
composer test

# Note: The composer test script only runs all tests.
# For specific tests, use 'php artisan test' directly.
```

### Test Best Practices

The test suite follows these best practices:

- **Isolation**: Each test has its own data setup
- **Cleanup**: Database is refreshed between tests
- **Realistic Scenarios**: Tests cover actual user workflows
- **Edge Cases**: Handles unusual but possible scenarios
- **Integration**: Tests component interactions, not just individual functionality
- **Performance**: Tests complete in under 7 seconds total

#### Available Lando Commands

Lando provides several custom commands for common development tasks:

```bash
# Database operations
lando mfs                    # Drop database, run migrations and seed with test data

# Testing
lando test
or
lando composer test         # Run all tests
lando artisan test          # Run all tests with artisan
lando artisan test --filter="Role"  # Run tests with specific filter

# Node.js operations
lando npm                   # Run npm commands
```

#### 5. Troubleshooting

**Common Issues:**

- **Port conflicts**: If ports are already in use, Lando will automatically find available alternatives
- **Database connection**: Ensure the database service is running with `lando status`
- **Cache issues**: Clear Laravel cache with `lando artisan cache:clear`
- **Composer issues**: Run `lando composer install --no-dev` if you encounter dependency issues
- **Frontend build errors**: If you get "Vite manifest not found" errors, run `lando npm run build` to compile assets
- **Node.js version issues**: The project requires Node.js 18+ for modern JavaScript syntax support

**Reset environment:**
```bash
lando rebuild
```

**View logs:**
```bash
lando logs
```

### Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

### Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

#### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

### Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

### Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

### Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

### License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
