# GymRAVANA Wellness Platform

GymRAVANA is an undergraduate software engineering project built with Laravel, Blade, Tailwind CSS, Alpine.js, MySQL, and Spatie Laravel Permission. It combines a public fitness website with role-based member, trainer, therapist, and administrator workflows.

## Implemented features

- Public landing, About, Programs, Group Programs, Other Events, Notice Board, Contact, membership, trainer, therapy-services, and fitness-store pages
- Guest/member group-class joining requests and database-backed contact enquiries with validation and rate limiting
- Guest and signed-in shopping cart with mock checkout, inventory reduction, order records, and confirmation pages
- Member registration with membership selection and optional email verification
- Trainer applications with administrator approval before public listing
- Searchable trainer directory with specialization/gender filters, detailed profiles, experience, and program-specific booking requests
- Trainer scheduling with pending-request review, confirmed start time, duration, required arrival time, preparation instructions, member messages, agenda filters, and a monthly calendar
- Trainer/member collision protection so two accepted sessions cannot overlap for the same trainer or client
- Therapist login accounts linked to public specialist profiles, with a private dashboard restricted to each therapist's own appointments
- Therapy appointment scheduling with confirmed time, duration, required arrival, preparation instructions, client messages, filters, and therapist/client collision protection
- Automatic database and email notifications for trainer and therapy confirmations or schedule updates, plus manually triggered reminders
- In-app notification centre, administrator notification activity, five-minute reminder cooldowns, and optional WhatsApp click-to-chat links
- Role-specific dashboards for members, trainers, therapists, and administrators
- Three-section member dashboard for upcoming sessions, read-only assigned workout/meal plans, monthly progress, booking shortcuts, and library resources
- Trainer plan builder for assigned clients with structured workout/meal items, start/end dates, draft/active/completed states, and preserved version history
- Private monthly trainer tracker using existing workout, wellness, attendance, points, consistency, goal, rating, and assessment data
- Trainer-recorded progression-readiness labels with a required evidence rationale, providing ground truth for the future local AI without making automated gate decisions
- Transparent member XP, levels, automatic ranks and activity streaks derived from existing completion records without calling deterministic rules AI
- Member-opted quests, time-limited challenges, permanent achievements, and one-time auditable mission XP rewards
- Administrator-configurable game levels and exercise goals with live member requirements, sequential unlocking, and a configurable Master Gate milestone
- Explainable Master Gate application requirements, immutable eligibility snapshots, administrator human review, mandatory override reasons, and revocable approval history
- Shared privacy-safe readiness feature generation, a disabled-by-default loopback-only Laravel client, and an admin-controlled idempotent prediction workflow that stores nothing without a valid reviewed model response
- Member-controlled permission for sharing monthly weight/waist trends with assigned trainers; raw measurement notes are never shown in the tracker
- Read-only administrator oversight of the latest trainer plans and private monthly reviews
- Centrally configured external Google Drive books/movie library with URL validation, an external-link warning, and a safe unconfigured state
- Member workout, measurement, wellness activity, points, therapy, service enrolment, booking, and order information
- Four-area trainer dashboard for plans, booking sessions, the shared library, and monthly tracking, plus public profile editing
- Administrator management for users, trainer applications, therapist accounts, memberships, services, events, notices, products, orders, schedules, notifications, and therapy requests
- Searchable Notice Board management for announcements, linked upcoming events, achievements, monthly highlights, and manually selected monthly clients
- Scheduled notice publication, validated image uploads, explicit client-photo consent records, and administrator-authored public statistics
- Admin-only finance ledger with configurable income/expense categories, manual entries, date/type/category filters, financial summaries, source/programme breakdowns, and monthly trends
- Idempotent product revenue synchronization when an administrator marks an existing order completed, with automatic reversal if that status is changed
- Genuine four-sheet `.xlsx` finance exports with formatted summary, income, expense, and income-by-source worksheets
- Seeded demonstration content and automated feature tests

The therapy material is educational and is not medical diagnosis or emergency care. Checkout is intentionally a mock workflow; no real payment gateway is connected.

## Requirements

- PHP 8.4.1 or a version compatible with `composer.lock`
- Composer 2
- MySQL 8 or a compatible MariaDB server
- Node.js 22 or a version supported by Vite 8
- npm
- Python 3.12 for the isolated Phase 7 Jupyter environment
- PHP DOM, XMLReader, and ZIP extensions for real Excel workbook generation

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

To show the optional external member library, add the approved Google Drive URL in `.env`:

```dotenv
GYMRAVANA_LIBRARY_URL="https://drive.google.com/drive/folders/your-approved-folder"
GYMRAVANA_LIBRARY_LABEL="GymRAVANA books and movies"
```

The URL is read only from `config/gymravana.php`. Google Drive permissions still control who can open the folder; GymRAVANA does not bypass them.

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

If the first page load after editing a Blade file is slow in the OneDrive project folder, warm the compiled views before refreshing:

```bash
php artisan view:cache
```

The built-in development server allows up to 120 seconds for this cold local compilation. This safeguard applies only to `php artisan serve`; production PHP limits are unchanged.

## Development behaviour

- Email verification is optional and does not block login or dashboards.
- `MAIL_MAILER=log` records verification and session emails in `storage/logs/laravel.log`; it does not deliver real email. Configure SMTP only in the later client-deployment phase.
- Public registration permits only member registration or a trainer application. Admin access cannot be self-assigned.
- New trainer applications remain hidden from the public directory until approved by an administrator.
- Trainer booking requests begin as `pending`. A trainer or administrator must provide the confirmed start, duration, and required arrival time before accepting a session.
- Members see the trainer's confirmed schedule, preparation instructions, and message on their own dashboard. They cannot view or modify another member's booking.
- Therapy appointment requests also begin as `pending`. A linked therapist can see only appointments assigned to their specialist profile; administrators can manage all appointments.
- Admins create or link therapist accounts under **Admin dashboard → Therapist accounts**. Public registration never offers therapist or administrator roles.
- Confirming or changing a scheduled trainer/therapy session creates an in-app notification for a registered client and uses the configured mail channel. Guest therapy clients receive email when they supplied an email address.
- The reminder button uses the same notification service and records the last reminder time/count. Repeated reminders are blocked for five minutes.
- WhatsApp buttons are click-to-chat links only. They open a prefilled message when a valid phone number exists, but GymRAVANA does not send the message or claim delivery.
- The member dashboard shows only that signed-in member's confirmed future sessions, non-draft assigned plans, plan items, and private monthly activity summary.
- Assigned workout and meal plans remain read-only for members. Trainers create plans only for clients linked through an accepted or completed booking.
- Saving a plan update creates a new version and archives the preceding version instead of overwriting history. Drafts remain trainer-only; active and completed versions are visible to the owning member.
- Trainers open **Dashboard → Plans** to create/update plans and **Dashboard → Monthly tracker** to review existing activity and record monthly goals, completion, ratings, assessments, private notes, and next-month goals.
- The monthly tracker can also record `ready`, `not ready yet`, or `not assessed` for progression readiness. Trainers can filter the selected month by pending/assessed status, while the trainer dashboard counts ordinary review records separately from usable readiness labels. Creation, decision changes, rationale changes and clearing are retained in an administrator-only revision trail; repeated unchanged saves do not create false history. The admin AI-readiness screen blocks contradictory member/month decisions and reports rationale, revision, trainer-concentration and class-balance quality signals. This is private trainer-supplied ground truth; it is not an AI prediction and does not unlock Master Gate.
- Member XP is calculated from published rules in `config/gamification.php`. Therapy use, measurements, purchases and readiness labels never award XP, and automatic ranks never grant Master status.
- Monthly consistency is a transparent calculation: distinct active days divided by days elapsed in the current month (or all days in a completed month). It is not AI and is not a medical assessment.
- Body-measurement trends are hidden from trainers by default. A member can enable or revoke sharing under **Account → Profile Information**; only monthly weight/waist change is shown, never raw measurement notes.
- Administrators can inspect latest plans and monthly reviews under **Admin dashboard → Trainer plans & reviews**. This oversight page is read-only.
- If `GYMRAVANA_LIBRARY_URL` is empty or unsafe, the dashboard shows a truthful “not configured” state instead of a broken or unsafe link.
- Existing accepted bookings created before the scheduling migration are preserved. They appear in the booking agenda and can be completed once their confirmation details are added.
- Store checkout records an order and reduces stock but does not charge a card.
- Prices are stored and displayed in Sri Lankan rupees (LKR).
- Notice images are stored on the `public` Laravel disk under `storage/app/public/notice-board`; run `php artisan storage:link` once so they are available to the website.
- A monthly client photograph cannot be published unless an administrator explicitly confirms the member's consent. The public Notice Board never reads private body measurements automatically.
- Finance reports are restricted to administrators and all values use LKR. Manual ledger entries should only be recorded after payment or an expense is confirmed.
- A completed product order creates exactly one automatic income entry. Changing it away from `completed` voids that entry; completing it again reactivates the same record instead of duplicating revenue.
- Memberships, group programmes, yoga, personal training, and therapy currently have no payment transaction in their existing workflows. Their confirmed payments are therefore entered manually under the appropriate seeded category rather than inferred from registrations or bookings.

## Finance and Excel reports

Open **Admin dashboard → Finance & reports** to record transactions, review totals and apply report filters. Automatic product-sale entries are read-only in Finance; correct them by changing the related order status so the order and ledger remain consistent.

The **Export filtered .xlsx** button applies the same visible filters and downloads a real Excel workbook containing Summary, Income, Expenses, and Income by Source sheets. Workbook generation uses OpenSpout and stores only a temporary private report file, which Laravel deletes after download.

The default finance categories are created by `FinanceSeeder`. Run this after pulling the phase if the database already exists:

```powershell
php artisan migrate
php artisan db:seed --class=FinanceSeeder
```

Design and implementation decisions made where the brief was ambiguous are recorded in [ASSUMPTIONS.md](ASSUMPTIONS.md).

## Roadmap status

Phases 1–6 are implemented locally, completing the normal non-AI platform described in the current project brief. Phase 7 currently includes transparent XP/levels/ranks/streaks, quests/challenges/achievements, trainer readiness-label collection, a privacy-safe Laravel dataset exporter with a versioned SHA-256 integrity contract, a reproducible Python 3.12/Jupyter environment, guarded EDA/model-comparison/explainability notebooks, a fail-closed local FastAPI inference boundary, a loopback-only Laravel client using the exporter's shared feature calculations, an admin-controlled idempotent prediction workflow, an admin-only data-sufficiency checkpoint aligned with Notebook 02, and an auditable Master Gate application/human-review workflow. No model has been trained because genuine labels are still absent, so the prediction action remains unavailable and the gate's AI criterion remains visibly `Not evaluated`. See the [gamification rules](docs/gamification.md), [Master Gate workflow](docs/master-gate.md), [Phase 7 AI data readiness](docs/ai/phase-7-data-readiness.md), [local inference-service guide](docs/ai/local-inference-service.md), the [public dataset suitability audit](docs/ai/public-dataset-suitability.md), and [the local AI workspace](ai/README.md).

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

Production hosting, HTTPS, SMTP delivery, mandatory verification, a real payment provider, media optimisation, backups, monitoring, and a formal privacy/legal review remain separate later phases.

The genuinely trained progression-readiness model remains unfinished Phase 7 work. The guarded notebooks, local service, Laravel client and controlled persistence workflow are implemented, but they correctly produce and store no real prediction until genuine supervised-learning evidence passes every gate and an artifact is reviewed. The Master Gate interface therefore records transparent eligibility and human decisions without claiming that AI is active.
