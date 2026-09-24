# Office Cost Management

Laravel 13 API backend for recording and reporting office costs in BDT. Phase 2 includes authentication, users, categories, expenses, dashboard summaries, monthly reports, private receipts, and audit history. The Vue interface and Excel import/export are later phases.

## Requirements

- PHP 8.4 or newer with `bcmath`, `fileinfo`, `pdo_mysql`, and `pdo_sqlite` for tests
- Composer
- MySQL 8 or compatible
- Node.js and npm for the later frontend

## Local setup

1. Run `composer install`.
2. Copy `.env.example` to `.env` and set `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL database.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Run `php artisan db:seed` for development accounts and sample data.
6. Run `php artisan serve`.

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
- `GET|POST /api/users`; `GET|PUT /api/users/{id}`
- `GET /api/audit-logs`

Expense lists support `search`, `category_id`, `payment_status`, `payment_method`, `date_from`, `date_to`, `year`, `month`, `amount_min`, `amount_max`, `sort`, `direction`, and `per_page` (maximum 100). Responses use `success` and `data`; list responses include `meta`. Laravel validation responses use HTTP 422 with field-level `errors`.

Admins can manage users and categories, delete expenses, and read audit history. Staff can create expenses and edit expenses they created. Both roles can view expenses, the dashboard, and monthly reports. Deactivating a category preserves its historical expense links.

## Data rules

All expense amounts use `DECIMAL(12,2)`; calculated totals are queried, never stored. `expense_date` may be null for legacy costs without a known day. `period_month` always stores the first day of the known month and drives monthly reporting, including undated costs. New dated expenses derive it automatically. Payment status is nullable because the workbook has none; new records may use `paid`, `unpaid`, or `pending`. Payment method is optional text because the workbook has no method list. Payers are separate allocations and may split an expense. Their amounts must equal the expense amount.

Marketing costs, cash summaries, and item-list data have separate schema structures for a later reviewed import. No workbook rows are imported in Phase 2, and the separate marketing sheet is not added to September's main ledger total. Stored timestamps remain UTC; `APP_DISPLAY_TIMEZONE` defaults to `Asia/Dhaka` for date-sensitive summaries and future UI display.

Receipt files use Laravel's private `local` disk and are downloadable only through an authorized API route. Uploads accept PDF, JPEG, PNG, and WebP up to 10 MB. Audit records exclude passwords and tokens.

## Tests and deployment

Run `php artisan test --compact`, `php artisan route:list --path=api`, and `php artisan migrate:status`. Tests use in-memory SQLite; the normal application uses MySQL from `.env`. For an existing database, use forward-only `php artisan migrate`; `migrate:fresh` deletes data and is only suitable for disposable test databases.

For deployment, install PHP dependencies with `composer install --no-dev --optimize-autoloader`, configure a private MySQL database and HTTPS, set `APP_ENV=production` and `APP_DEBUG=false`, provide a unique `APP_KEY`, then run `php artisan migrate --force`. Run `php artisan office:create-admin` in an interactive terminal to provision the first admin; the password is prompted without a command-line argument. The development seeder intentionally does not create demo accounts in production. Keep `.env` and receipt storage private.
