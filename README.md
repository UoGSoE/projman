# Projman

Projman is a demand tracking and delivery governance tool designed for small IT teams. If you've ever struggled to figure out where work requests go, who has the skills to handle them, or whether people are getting overloaded, this tool is for you.

At its core, Projman helps you visualize work from ideation all the way through to deployment. It tracks projects through a structured lifecycle, matches required skills with available staff, and ensures nobody misses critical deadlines through configurable notifications. Everything is audited, so you always have a history of who did what and when. It's particularly useful for teams that need to balance incoming requests against limited resources and make sure the right people are assigned to the right work.

## Features

- **Project lifecycle management** - Track projects through multiple stages: ideation, feasibility, scoping, scheduling, detailed design, development, testing, deployment, and completion
- **Skills-based staffing** - Match project requirements with staff members' skills and expertise levels
- **Team workload visualization** - Heat map view showing team busyness across upcoming weeks
- **Role-based assignments** - Assign stage-specific managers (Feasibility Manager, Development Manager, etc.)
- **Configurable notifications** - Rule-based email notifications for project creation and stage transitions
- **Audit trail** - Complete project history tracking all changes and transitions
- **Staff management** - Admin interface for managing users, roles, skills, and team capabilities
- **SSO integration** - Single sign-on support via Shibboleth/SAML

## Tech Stack

- **Laravel 12** - PHP framework
- **Livewire 3** - Dynamic interfaces
- **Flux UI Pro** - Component library (Tailwind CSS 4 + Vite)
- **Lando** - Local development environment
- **Pest** - Testing framework
- **Laravel Horizon** - Queue management

## Getting Started

### Prerequisites

- [Lando](https://lando.dev/) installed on your machine
- Git

### Installation

1. Clone the repository:
```bash
git clone git@github.com:UoGSoE/projman.git
cd projman
```

2. Set up environment and dependencies:
```bash
cp .env.example .env
lando composer install
lando npm install
lando npm run build
```

3. Start Lando and set up the database:
```bash
lando start
# If this is your first run, lando start may error due to missing DB tables
lando mfs  # Migrate and seed the database
```

4. Access the application at the URL shown by `lando info` (typically https://projman.lndo.site)
- Default admin login: `admin2x` / `secret`
- Default staff login: `staff2x` / `secret`
- Test user (for email testing): `testuser` / `password` (test@mailhog.local)

### Development

- **Start Lando**: `lando start`
- **Run migrations**: `lando artisan migrate`
- **Seed database**: `lando mfs`
- **Install dependencies**: `lando composer install` / `lando npm install`
- **Build assets**: `lando npm run dev` (for development) or `lando npm run build` (for production)
- **Run tests**: `lando artisan test`
- **Format code**: `lando vendor/bin/pint`
- **Queue worker**: `lando artisan horizon` (for processing notification emails)

### Common Lando Commands

- `lando artisan [command]` - Run Laravel artisan commands
- `lando composer [command]` - Run Composer commands
- `lando npm [command]` - Run npm commands
- `lando mysql` - Access MySQL shell
- `lando ssh` - SSH into the application container
- `lando mfs` - Custom command to migrate fresh and seed

## Project Structure

- `app/` - Application code
  - `Enums/` - Project status, skill levels, and busyness enums
  - `Events/` - Project lifecycle events
  - `Http/Middleware/` - Custom middleware (admin, staff, dev-only)
  - `Jobs/` - Queue jobs for email notifications
  - `Livewire/` - Livewire components for all UI interactions
  - `Mail/` - Email templates and mailables
  - `Models/` - Eloquent models for projects, users, skills, roles, and stage data
- `resources/views/` - Blade templates and Livewire views
- `routes/` - Application routes (web.php and sso-auth.php)
- `database/` - Migrations, factories, and seeders
- `tests/Feature/` - Feature tests covering project creation, editing, notifications, skills, roles, and user management

## Key Concepts

### Project Stages

Projects move through a defined lifecycle with stage-specific data collection:

1. **Ideation** - Initial project concept and business case
2. **Feasibility** - Technical and cost-benefit assessment
3. **Scoping** - Define scope, requirements, and skills needed
4. **Scheduling** - Timeline planning and team assignment
5. **Detailed Design** - Architecture and requirements documentation
6. **Development** - Implementation with team tracking
7. **Testing** - Quality assurance and sign-offs
8. **Deployed** - Production deployment and monitoring
9. **Completed** - Project closure

Projects can also be marked as **Cancelled** at any stage.

### Skills Management

Skills are tracked at three levels (beginner, intermediate, expert) and can be assigned to staff members. Projects specify required skills during the scoping stage, which helps identify the right team members during scheduling.

### Roles

Role-based notifications ensure the right people are alerted when projects enter their area of responsibility (e.g., Development Manager notified when a project reaches development stage).

### Busyness Tracking

Staff members have a two-week busyness indicator (low/medium/high/unknown) that helps visualize team capacity in the heat map view.

## License

This project is licensed under the MIT License.
