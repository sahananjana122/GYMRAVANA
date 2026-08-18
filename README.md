# GymRAVANA Wellness Platform

GymRAVANA is an undergraduate software engineering project built with Laravel, Blade, Tailwind CSS, Alpine.js, MySQL, and Spatie Laravel Permission. It combines a public fitness website with role-based member, trainer, and administrator workflows.

## Implemented features

- Public landing, About, Programs, Group Programs, Contact, membership, trainer, yoga therapy, and fitness-store pages
- Guest/member group-class joining requests and database-backed contact enquiries with validation and rate limiting
- Guest and signed-in shopping cart with mock checkout, inventory reduction, order records, and confirmation pages
- Member registration with membership selection and optional email verification
- Trainer applications with administrator approval before public listing
- Searchable trainer directory with specialization/gender filters, detailed profiles, experience, and program-specific booking requests
- Role-specific dashboards for members, trainers, and administrators
- Member workout, measurement, wellness activity, points, therapy, service enrolment, booking, and order information
- Trainer profile editing and booking status management
- Administrator management for users, trainer applications, memberships, services, products, orders, bookings, and therapy requests
- Seeded demonstration content and automated feature tests

The therapy material is educational and is not medical diagnosis or emergency care. Checkout is intentionally a mock workflow; no real payment gateway is connected.

## Requirements

- PHP 8.4.1 or a version compatible with `composer.lock`
- Composer 2
- MySQL 8 or a compatible MariaDB server
- Node.js 22 or a version supported by Vite 8
- npm

On the original development computer, use `C:\php\php.exe`. The older XAMPP PHP 8.2 executable does not satisfy the current dependency lock file. XAMPP can still provide MySQL.

## First-time setup

From the `ravana-app` directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
```

Create a MySQL database named `gymravana`, put its credentials in `.env`, and run:

```powershell
php artisan migrate --seed
php artisan storage:link
npm run build
```

To create a local administrator while seeding, add unique development credentials to `.env`:

```dotenv
DEMO_ADMIN_EMAIL=admin@example.test
DEMO_ADMIN_PASSWORD=replace-with-a-private-password
```

Never commit `.env`, database exports, passwords, API keys, or generated cache files.

## Run locally

Use two terminals from the project directory:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Then open `http://127.0.0.1:8000`. Apache is not required when using `php artisan serve`; starting MySQL from XAMPP is sufficient.

## Development behaviour

- Email verification is optional and does not block login or dashboards.
- `MAIL_MAILER=log` records verification links in `storage/logs/laravel.log`; it does not deliver real email.
- Public registration permits only member registration or a trainer application. Admin access cannot be self-assigned.
- New trainer applications remain hidden from the public directory until approved by an administrator.
- Store checkout records an order and reduces stock but does not charge a card.
- Prices are stored and displayed in Sri Lankan rupees (LKR).

Design and implementation decisions made where the brief was ambiguous are recorded in [ASSUMPTIONS.md](ASSUMPTIONS.md).

## Validation

Tests use an in-memory SQLite database and do not modify local MySQL data.

```powershell
composer test
composer validate --strict
npm run build
php artisan route:list
php artisan migrate:status
git diff --check
```

## Important directories

- `app/Models` — data entities and relationships
- `app/Http/Controllers` — validation and application workflows
- `database/migrations` — database structure
- `database/seeders` — roles and demonstration content
- `resources/views` — Blade pages and reusable components
- `resources/css/app.css` — Tailwind component styles
- `routes/web.php` — public and protected routes
- `tests/Feature` — behavioural and access-control tests

## Deferred client-ready work

Production hosting, HTTPS, SMTP delivery, mandatory verification, a real payment provider, media optimisation, backups, monitoring, privacy/legal review, and the locally trained Hugging Face/Kaggle AI feature remain separate later phases. The current codebase establishes the functional and testable foundation for those additions.
