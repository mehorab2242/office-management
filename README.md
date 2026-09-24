# Office Cost Management

Laravel 13 and Vue 3 application for recording, importing, and reporting office costs in BDT. The responsive interface includes authentication, dashboard summaries, expense and attachment management, monthly, yearly, and custom reports, controlled workbook imports, user administration, and audit history.

## Features

- Sanctum authentication with active-account checks and administrator/staff authorization
- Expense CRUD, search, filters, sorting, pagination, payer allocations, and private receipt attachments
- Category and user management with activation controls
- Dashboard totals, monthly trend, category breakdown, and recent expenses
- Monthly, yearly, and custom reports using database aggregation
- Filter-aware Excel exports and printable PDF reports
- Controlled Excel import with sheet selection, editable column mapping, row preview, duplicate detection, category decisions, transaction safety, and reconciliation
- Filterable administrator audit log

## Requirements

- PHP 8.4 or newer with `bcmath`, `fileinfo`, `pdo_mysql`, and `pdo_sqlite` for tests
- Composer
- MySQL 8 or compatible
- Node.js 20 or newer and npm

## Local setup

1. Run `composer install`.
2. Copy `.env.example` to `.env` and set `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL database.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Run `php artisan db:seed` for development accounts and sample data.
6. Run `npm install`.
7. Run `npm run build` for production assets, or `npm run dev` during frontend development.
8. Run `php artisan serve`.

The development seeder runs only in local and testing environments. It creates:

- Admin: `admin@example.test` / `OfficeDemo123!`
- Staff: `staff@example.test` / `OfficeDemo123!`

These are demo credentials. Change them before exposing a local instance outside your machine. No real names or costs from the source workbook are in the seeder.

## Authentication and API

`POST /api/login` accepts `email` and `password` and returns a Sanctum bearer token. Pass it as `Authorization: Bearer <token>`. `POST /api/logout` revokes the current token. All other endpoints require an active account.

- `GET /api/me`
- `GET|POST /api/categories`; `GET|PUT|DELETE /api/categories/{id}`
- `GET|POST /api/expenses`; `GET|PUT|DELETE /api/expenses/{id}`
- `POST /api/expenses/{id}/attachments`; `GET|DELETE /api/expenses/{id}/attachments/{attachment}`
- `GET /api/dashboard`
- `GET /api/reports/monthly?year=2026&month=9`
- `GET /api/reports/yearly?year=2026`; `GET /api/reports/custom`
- `GET /api/exports/{expenses|monthly|yearly|custom}.xlsx`; `GET /api/exports/{monthly|custom}.pdf`
- `POST /api/imports/analyze`; `POST /api/imports/{batch}/preview`; `POST /api/imports/{batch}/commit`; `GET /api/imports/{batch}`
- `GET|POST /api/users`; `GET|PUT /api/users/{id}`
- `GET /api/audit-logs`

Expense lists support `search`, `category_id`, `payment_status`, `payment_method`, `date_from`, `date_to`, `year`, `month`, `amount_min`, `amount_max`, `sort`, `direction`, and `per_page` (maximum 100). Responses use `success` and `data`; list responses include `meta`. Laravel validation responses use HTTP 422 with field-level `errors`.

Admins can manage users and categories, delete expenses, and read audit history. Staff can create expenses and edit expenses they created. Both roles can view expenses, the dashboard, and monthly reports. Deactivating a category preserves its historical expense links.

## Frontend

Laravel serves the Vue application shell and Vue Router handles `/login`, `/dashboard`, expenses, reports, imports, users, categories, and audit history. Import, user, category, and audit screens are administrator only. The frontend uses TypeScript, Pinia, Axios, Tailwind CSS, Reka UI primitives, and Lucide icons. Bearer tokens are kept in session storage and cleared when the API rejects the session.

Run `npm run typecheck`, `npm run test:frontend`, and `npm run build` to verify the frontend. Expense filtering and pagination are server driven. Date-only values are formatted without timezone conversion.

## Data rules

All expense amounts use `DECIMAL(12,2)`; calculated totals are queried, never stored. `expense_date` may be null for legacy costs without a known day. `period_month` always stores the first day of the known month and drives monthly reporting, including undated costs. New dated expenses derive it automatically. Payment status is nullable because the workbook has none; new records may use `paid`, `unpaid`, or `pending`. Payment method is optional text because the workbook has no method list. Payers are separate allocations and may split an expense. Their amounts must equal the expense amount.

Workbook import is a three-step review: analyze sheets and detected headers, preview normalized rows and errors, then commit valid nonduplicate rows in one transaction. Formula cells retain their source formula while imports use the evaluated value. Unknown categories require an explicit choice to create them, map them to an existing category, or skip their rows. Marketing and item sheets are not silently added to the expense ledger. Stored timestamps remain UTC; `APP_DISPLAY_TIMEZONE` defaults to `Asia/Dhaka` for date-sensitive summaries and UI display.

Receipt files use Laravel's private `local` disk and are downloadable only through an authorized API route. Uploads accept PDF, JPEG, PNG, and WebP up to 10 MB. Audit records exclude passwords and tokens.

## Tests and deployment

Run `php artisan test --compact`, `npm run test:frontend`, `npm run typecheck`, `npm run build`, `php artisan route:list --path=api`, and `php artisan migrate:status`. Tests use in-memory SQLite; the normal application uses MySQL from `.env`. For an existing database, use forward-only `php artisan migrate`; `migrate:fresh` deletes data and is only suitable for disposable test databases.

For deployment, install PHP dependencies with `composer install --no-dev --optimize-autoloader`, configure a private MySQL database and HTTPS, set `APP_ENV=production` and `APP_DEBUG=false`, provide a unique `APP_KEY`, then run `php artisan migrate --force`, `php artisan storage:link` only if public assets require it, and `php artisan optimize`. Run `npm ci && npm run build` before release. Run `php artisan office:create-admin` in an interactive terminal to provision the first admin; the password is prompted without a command-line argument. The development seeder intentionally does not create demo accounts in production. Keep `.env`, receipt storage, and uploaded workbook sources private. Back up the database and `storage/app/private` together, and run the queue worker under a process monitor when queued work is enabled.
