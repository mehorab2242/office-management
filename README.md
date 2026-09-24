# Office Cost Management

Office Cost Management is a Laravel 13 and Vue 3 application for recording and reviewing office expenses in BDT. It supports role based access, expense ownership, category management, dashboard summaries, administrator reporting, workbook import, exports, attachments, and audit history.

## Application workflow

1. Sign in with an active account.
2. Use the Dashboard to review spending totals, transaction count, average expense, largest expense, category totals, monthly trends, and recent expenses.
3. Create expenses with the expense date, category, description, amount, payment method, reference, notes, and optional payer allocations. The expense date defaults to today and is the source of truth for monthly and yearly reporting.
4. Filter and sort expenses by description, category, payment method, date range, amount range, and date or amount order.
5. Attach private receipts to expenses when needed.
6. Use categories to classify expenses. Super Admins can create, edit, and deactivate categories.
7. Super Admins can review reports, import workbooks, export data, manage staff, and inspect the audit log.

## Roles and access

### Super Admin

Super Admins can see all users' expenses and dashboard data. They can access expenses, categories, reports, imports, staff management, audit logs, exports, and all existing administrative actions.

### Staff

Staff users can access only:

- Dashboard
- Expenses
- Categories

Staff expense lists, dashboard totals, trends, category totals, recent expenses, and monthly selections are scoped on the backend to expenses created by the signed-in staff member. Staff cannot access administrator reports, imports, staff management, audit logs, or other users' expenses.

## Requirements

- PHP 8.4 or newer with `bcmath`, `fileinfo`, `pdo_mysql`, and `pdo_sqlite`
- Composer
- MySQL 8 or compatible
- Node.js 20 or newer and npm

## Local setup

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create the environment file and configure the database:

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

   Set `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

3. Run migrations:

   ```bash
   php artisan migrate
   ```

4. Seed development data when needed:

   ```bash
   php artisan db:seed
   ```

   The development seeder provides:

   - Super Admin: `admin@example.test` / `OfficeDemo123!`
   - Staff: `staff@example.test` / `OfficeDemo123!`

5. Install frontend dependencies:

   ```bash
   npm install
   ```

6. Start the application:

   ```bash
   php artisan serve
   npm run dev
   ```

   For a production asset bundle, use `npm run build` instead of `npm run dev`.

## Main API endpoints

All endpoints below require an authenticated, active Sanctum user unless stated otherwise.

- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`
- `GET|POST /api/expenses`
- `GET|PUT|DELETE /api/expenses/{expense}`
- `POST /api/expenses/{expense}/attachments`
- `GET|DELETE /api/expenses/{expense}/attachments/{attachment}`
- `GET /api/dashboard`
- `GET|POST /api/categories`
- `GET|PUT|DELETE /api/categories/{category}`
- `GET /api/reports/monthly`
- `GET /api/reports/yearly`
- `GET /api/reports/custom`
- `GET /api/exports/expenses.xlsx`
- `GET /api/exports/monthly.xlsx`
- `GET /api/exports/yearly.xlsx`
- `GET /api/exports/custom.xlsx`
- `GET /api/exports/monthly.pdf`
- `GET /api/exports/custom.pdf`
- `POST /api/imports/analyze`
- `POST /api/imports/{batch}/preview`
- `POST /api/imports/{batch}/commit`
- `GET /api/imports/{batch}`
- `GET|POST /api/users`
- `GET|PUT /api/users/{user}`
- `GET /api/audit-logs`

## Expense and reporting rules

- `expense_date` is the single source of truth for monthly and yearly reporting.
- Expense totals are calculated from the database and are not stored as summary values.
- Amounts use `DECIMAL(12,2)` and are displayed in Bangladeshi taka.
- Staff ownership is enforced through the existing `created_by` field in backend queries and authorization checks.
- Date-only values are displayed without timezone conversion. Display timezone defaults to `Asia/Dhaka` through `APP_DISPLAY_TIMEZONE`.
- Receipt files are stored on Laravel's private local disk and are served only through authorized routes. PDF, JPEG, PNG, and WebP files up to 10 MB are accepted.
- Workbook imports use an analyze, preview, and commit workflow with validation, duplicate detection, category mapping, and transaction safety.

## Frontend stack

The frontend uses Vue 3, TypeScript, Vue Router, Pinia, Axios, Tailwind CSS, Flatpickr, Reka UI primitives, and Lucide icons. Expense filtering and pagination are server driven.

## Useful commands

```bash
php artisan route:list --path=api
php artisan migrate:status
npm run typecheck
npm run test:frontend
```

Keep `.env`, uploaded workbooks, receipt storage, and database credentials private. Use `php artisan migrate --force` and `npm run build` as part of a production deployment.
