# GymRaavana Lifestyle System

GymRaavana is an undergraduate software engineering project built with Laravel. It demonstrates authentication, optional email verification, role-based access control, database-backed wellness modules, validation, responsive Blade interfaces, and automated feature tests.

## Implemented roles

- **Member** — records workouts and measurements, completes wellness activities, earns points, and submits therapy requests.
- **Trainer** — views operational statistics and reviews therapy requests.
- **Master** — sees members who reach the configured points threshold.
- **Admin** — manages user roles and therapy requests.

Public registration creates members only. Privileged roles must be assigned by an administrator.

## Implemented MVP modules

- Role-aware dashboards
- Workout plans and daily completion tracking
- Body measurement history
- Meditation, breathing, and lifestyle activity tracking
- Points and level calculation
- Non-emergency yoga therapy request workflow
- Admin role management
- Profile and password management

The wellness and therapy content is educational and must not be treated as medical diagnosis or emergency care.

## Requirements

- PHP 8.4.1 or newer for the currently locked dependencies
- Composer 2
- MySQL 8 or MariaDB-compatible MySQL server
- Node.js 22 or another version supported by Vite 8
- npm

Check the PHP command before starting:

```powershell
php -v
Get-Command php
```

On the original development computer, the correct PHP executable is `C:\php\php.exe`. The older XAMPP PHP 8.2 executable is not compatible with the current lock file.

## First-time setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database named `gymravana`, then update the `DB_*` values in `.env`.

```powershell
php artisan migrate --seed
npm install
npm run build
```

To optionally create a local demo administrator, set these values in `.env` before seeding:

```dotenv
DEMO_ADMIN_EMAIL=your-admin@example.com
DEMO_ADMIN_PASSWORD=choose-a-unique-local-password
```

Never commit `.env` or a real password.

## Run locally

Use two terminals:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Open `http://127.0.0.1:8000`.

XAMPP may continue to run MySQL. Apache is not required when using `php artisan serve`.

## Email verification during development

Email verification is optional and does not block registration, login, dashboards, or member modules during the assignment phase. Laravel still creates verification links so the feature can be completed later without rebuilding authentication.

The local environment uses `MAIL_MAILER=log`, so messages are written to `storage/logs/laravel.log` instead of being sent to a real inbox. A production SMTP or transactional email provider will be configured during the client-ready phase.

## Run tests

Tests use an in-memory SQLite database and do not modify the local MySQL database.

```powershell
composer test
npm run build
```

## Important directories

- `app/Models` — database-backed entities and relationships
- `app/Http/Controllers` — request validation and application actions
- `database/migrations` — database structure
- `database/seeders` — roles and safe sample content
- `resources/views` — Blade user interface
- `routes/web.php` — protected web routes
- `tests/Feature` — automated behaviour and security checks

## Future phases

The proposal's student role, verified indigenous-doctor role, payments, staff-to-member assignments, content management, consultations, and AI integration are intentionally deferred until the undergraduate MVP is stable and reviewed.
