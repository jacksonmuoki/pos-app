# POS Multi-Company System: Handover Guide

Author: Jackson Muoki. Repository: github.com/jacksonmuoki/pos, branch `feature/multi-tenant`.

This document explains the structure, setup, conventions and roadmap of the system so a new developer can continue the project without prior context.

## 1. What the system is

A Point of Sale (POS) application for retail businesses, being rebuilt from a single-company plain PHP app into a **multi-company (multi-tenant) Laravel application**. Each company gets:

- Its own **database**, created automatically (no manual SQL import).
- Its own **subdomain** (for example `acme.localhost`, later `acme.yourpos.com`).
- Its own users, products, orders and payments, fully isolated from other companies.

Planned features: role-based staff login, cashier and sales workflow, product and stock management, cash and M-Pesa payments (PayHero STK Push), receipts, reports and admin tools.

## 2. Current status

**Working and verified**

- Laravel app with stancl/tenancy (database-per-company).
- Two test companies (`acme`, `zawadi`), each with its own database and admin user.
- Tenant migrations that build the POS tables.
- Tenant-aware login and logout, with a role middleware and a basic dashboard.

**Not built yet**

- Products, categories and stock admin screens.
- Cashier screen (cart, checkout), cash payment, receipts.
- PayHero / M-Pesa STK Push and the tenant-specific callback.
- Reports, central super-admin area, company self-signup.
- Automated tests.

**Needs verification**

- The `company:create` command and the automatic database creation on `Tenant::create()` were not confirmed end to end. During setup the tenant databases had to be created manually with the `CreateDatabase` job. Verify with a throwaway company (section 9).
- The tenant schema is a generic POS layout. It has not yet been aligned with the columns of the old PHP app (see section 14, step 1).

## 3. Technology stack

- PHP 8.5.11, Laravel 13.40 (check package compatibility before upgrading).
- stancl/tenancy v3 for multi-tenancy.
- MySQL / MariaDB (XAMPP on Windows for development).
- Blade views with plain CSS (green theme).
- Composer for dependencies. Node and Vite are present but not yet used.
- Planned: PayHero (M-Pesa STK Push).

## 4. Architecture

### Two kinds of database

- **Central database** (named `pos-v2` in local `.env`): holds `tenants`, `domains`, `migrations`, and Laravel default tables (`users`, `cache`, `jobs`, `sessions`). The central `users` table is **unused by tenants**. It is reserved for a future super-admin.
- **Tenant databases**: one per company, named `tenant` + company id (for example `tenantacme`). They hold all business data.

### How a request is routed

1. Browser requests `http://acme.localhost:8000`.
2. Middleware `InitializeTenancyByDomain` looks up the host in the central `domains` table.
3. The `DatabaseTenancyBootstrapper` switches the default database connection to `tenantacme`.
4. The controller and Eloquent models now read and write that company's data only.
5. `PreventAccessFromCentralDomains` blocks tenant routes on `localhost` and `127.0.0.1` (the central domains).

### Company creation

`php artisan company:create <id>` creates the `Tenant` row, adds the domain, relies on the event pipeline in `TenancyServiceProvider` (`CreateDatabase` then `MigrateDatabase` on the `TenantCreated` event) to build and migrate the database, then creates the first admin user inside the tenant.

### Schema changes

Add a migration in `database/migrations/tenant/`, then run `php artisan tenants:migrate` to apply it to every company.

## 5. Repository layout

- `app/Models/Tenant.php`: company model (`HasDatabase`, `HasDomains`, string primary key).
- `app/Models/User.php`: staff user (fillable includes `role`, `active`).
- `app/Http/Controllers/Auth/LoginController.php`: login and logout.
- `app/Http/Middleware/RoleMiddleware.php`: alias `role`, used as `role:admin,manager`.
- `app/Console/Commands/CreateCompany.php`: the `company:create` command.
- `app/Providers/TenancyServiceProvider.php`: tenancy events and the database creation pipeline.
- `bootstrap/providers.php`: must list `TenancyServiceProvider`.
- `bootstrap/app.php`: registers the `role` middleware alias.
- `config/tenancy.php`: tenancy configuration.
- `routes/web.php`: central routes (future super-admin and signup).
- `routes/tenant.php`: all company routes (login, dashboard, admin).
- `database/migrations/`: central migrations.
- `database/migrations/tenant/`: company migrations (the POS schema).
- `resources/views/auth/login.blade.php` and `resources/views/dashboard.blade.php`: current views.
- `legacy/`: the original plain PHP POS, kept as reference for porting features. Do not extend it.

## 6. Tenant database schema

All tables live in each company database, created by the `create_pos_tables` migration.

- **users**: name, email (unique), password, role (`admin`, `manager`, `cashier`), active, remember token.
- **categories**: name (unique).
- **products**: category_id (nullable), name, sku (unique, nullable), barcode (indexed), price, cost, stock, reorder_level, active.
- **customers**: name, phone, email.
- **orders**: receipt_no (unique), user_id (cashier), customer_id (nullable), subtotal, discount, tax, total, status (`pending`, `paid`, `cancelled`, `refunded`).
- **order_items**: order_id, product_id, product_name (snapshot), quantity, unit_price, line_total.
- **payments**: order_id, method (`cash`, `mpesa`), amount, phone, status (`pending`, `success`, `failed`, `cancelled`), external_reference (unique, sent to PayHero), provider_reference (M-Pesa receipt), callback_payload (JSON), paid_at.
- **settings**: key (primary), value. Intended for per-company configuration such as encrypted PayHero credentials.

Money columns are `decimal(12,2)`. Never use floats for money.

## 7. Authentication and roles

- Login is per company, using email and password against that company's `users` table. Only `active = true` users can sign in.
- Roles: `admin`, `manager`, `cashier`. Route protection uses `->middleware('role:admin,manager')`.
- Sessions use the `cookie` driver in development, so no `sessions` table is needed in tenant databases.
- Passwords are hashed automatically through the `hashed` cast on the User model. Pass plain text to `User::create()`.
- Test accounts (change before any real use): `owner@acme.test` and `owner@zawadi.test`.

## 8. Environment configuration

Key `.env` values (never commit `.env`):

- `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=pos-v2` (the central database), `DB_USERNAME`, `DB_PASSWORD`.
- `SESSION_DRIVER=cookie`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`.
- `APP_URL=http://localhost:8000`.

Why these three drivers: the default `database` drivers look for `sessions`, `cache` and `jobs` tables inside each company database, which do not exist there. The `sync` queue is required so the database creation pipeline runs immediately.

`config/tenancy.php` notes:

- `tenant_model` is `App\Models\Tenant`.
- `id_generator` is `null`, so you must pass an id (`acme`), giving readable database names.
- `central_domains` is `127.0.0.1` and `localhost`.
- `CacheTenancyBootstrapper` is **commented out** because the array store does not support tags. Re-enable it when moving to Redis.

## 9. Setup from scratch (Windows and XAMPP)

1. Install XAMPP (start Apache and MySQL), PHP compatible with Laravel 13, and Composer.
2. Clone the repository and check out the branch:
   `git clone https://github.com/jacksonmuoki/pos.git` then `git switch feature/multi-tenant`
3. `composer install`
4. Copy `.env.example` to `.env`, apply the settings in section 8, then `php artisan key:generate`.
5. Create the central database (for example `pos-v2`) in phpMyAdmin.
6. `php artisan migrate`
7. Create a company: `php artisan company:create acme --email=owner@acme.test --password=Secret123!`
8. Verify the database exists and has tables:
   `& "C:\xampp\mysql\bin\mysql.exe" -u root tenantacme -e "SHOW TABLES;"`
9. If the database or tables are missing, run `php artisan tenants:migrate`. If the database itself is missing, create it with the `CreateDatabase` job from `php artisan tinker`, then migrate.
10. `php artisan serve --host=0.0.0.0 --port=8000`
11. Open `http://acme.localhost:8000`. Modern browsers resolve `*.localhost` automatically. If not, add `127.0.0.1 acme.localhost` to the Windows hosts file.

Always test the pipeline with a throwaway company (`company:create demo`), confirm `tenantdemo` has tables, then delete it with `Tenant::find('demo')->delete()` in tinker.

## 10. Everyday commands

- `php artisan tenants:list`: list companies and domains.
- `php artisan tenants:migrate`: apply new tenant migrations to all companies.
- `php artisan tenants:migrate-fresh`: wipe and rebuild all company databases (development only).
- `php artisan tenants:seed`: run seeders for all companies.
- `php artisan make:migration name --path=database/migrations/tenant`: new tenant migration.
- `php artisan optimize:clear`: clear cached config after editing `.env` or config files.
- `php artisan route:list`: check routes and middleware.

## 11. Lessons learned (known pitfalls)

- **Tenant model must use a string key.** `Tenant` needs `public $incrementing = false;` and `protected $keyType = 'string';`, otherwise the id is saved as `0` and domains fail on the foreign key.
- **Config must point at your model.** `config/tenancy.php` must import `App\Models\Tenant`, not the package base model.
- **`make:migration create_pos_tables` produces a stub** that creates a table named `pos_tables`. Always paste the real schema into the file and check it with `Select-String ... -Pattern "Schema::create"`.
- **Do not use `database` drivers** for session, cache or queue (section 8).
- **Leading spaces in `.env`** can stop values being read. Keep every line flush left.
- **`tenants:list` display:** shows each company as `id @ domain`. An empty domain after `@` means the company has no domain row.
- **Terminal versus code.** In PowerShell, run only commands (`php artisan`, `git`, `code`). PHP code such as `Schema::create(...)` or `protected $fillable = ...` goes inside `.php` files. Inside `php artisan tinker`, type only single PHP statements.
- **Git pager and editor.** Git was configured with `core.pager cat` and `core.editor "code --wait"` to avoid getting stuck in less or Vim.
- **BOM characters** in Markdown files caused a merge conflict once. Save files as UTF-8 without BOM.

## 12. Git and repository state

- `main`: the original plain PHP POS, history preserved, pushed.
- `feature/multi-tenant`: Laravel app in the repo root, old code moved to `legacy/`.
- `.gitignore` excludes `.env`, `vendor/`, `schema.sql`, `dtabase/` and local PayHero config files.
- A file named `schema.sql` was briefly committed on this branch and then untracked. It remains in branch history. If it contained real data, rotate any passwords and rewrite the branch history.
- A local-only edit to `legacy/app/includes/app.php` (BASE_URL set to localhost, display_errors on) must not be committed.
- Merge to `main` through a pull request once the main flows work.

## 13. Security rules

- Never commit `.env`, database passwords, PayHero credentials, API keys or tokens.
- Store each company's PayHero credentials in its `settings` table, **encrypted** (Laravel `encrypted` cast), never in files.
- Never mark an order paid from browser input. Only mark it paid after a verified provider callback or a status check against PayHero's API.
- Use Eloquent or the query builder only (parameterised queries). No raw SQL built from user input.
- Set `APP_DEBUG=false` and `APP_ENV=production` in production.
- In production, use a dedicated MySQL user with CREATE privilege instead of root.
- Change all test passwords.

## 14. Roadmap

Suggested order of work:

1. **Align the schema with the legacy app.** Export the old database structure only (`mysqldump --no-data`), compare with section 6, and add migrations for missing columns or tables. Do not edit the already-applied migration. Add new migrations instead.
2. **Products and categories admin** (admin and manager): list, create, edit, deactivate, stock adjustments, low-stock warnings.
3. **Cashier screen:** product search or barcode, cart, discounts, tax, order creation inside a database transaction (create order, items, payment, decrement stock together), cash payment and change calculation.
4. **Receipts:** printable receipt view with company name, receipt number, items, totals, payment method, cashier.
5. **PayHero M-Pesa:** per-company credentials in `settings`, STK Push request with a unique `external_reference`, a tenant-specific callback route (for example `https://acme.yourpos.com/payhero/callback`) that locates the payment, verifies it, updates `payments` and `orders`, and stores the payload. Exclude the callback route from CSRF. Add a polling status endpoint for the cashier screen.
6. **Reports:** daily sales, sales by cashier, by product, by payment method, low stock.
7. **Staff management** inside each company, optionally with spatie/laravel-permission for finer permissions.
8. **Central super-admin area** on the central domain: list and create companies, suspend a company, view usage.
9. **Company signup** page that provisions a company automatically.
10. **Tests** (PHPUnit) for tenancy isolation, login, order creation and payment callbacks.
11. **Production deployment:** wildcard DNS (`*.yourpos.com`), wildcard SSL, Redis for cache and sessions (then re-enable the cache bootstrapper), a queue worker, scheduled backups of every tenant database.

## 15. Conventions for contributors

- One migration per change, in `database/migrations/tenant/` for company data and `database/migrations/` for central data. Never edit a migration that has already run in shared environments.
- Company routes go in `routes/tenant.php` inside the tenancy middleware group. Central routes go in `routes/web.php`.
- Protect screens with `auth` and the `role:` middleware.
- Wrap multi-table writes (orders and payments) in `DB::transaction()`.
- Keep views in `resources/views`, using the existing green theme (primary colour `#1b7a3d`).
- Test every new feature on two companies to confirm data does not leak between them.
- Commit small, with clear messages, on feature branches.

## 16. Open items and risks

- Confirm automatic database creation works through `company:create` (section 2).
- Confirm whether `schema.sql` in git history contained real data (section 12).
- Decide on production session and cache drivers before launch.
- The system currently runs on PHP 8.5, which is very new. If a package misbehaves, test on PHP 8.3 or 8.4.

## 17. How to add a feature

Every company feature follows the same eight steps. The worked example below adds a **Suppliers** screen (list and create) that only admins and managers can use. Replace the names to build any other feature.

### Step 1: Branch

`git switch -c feature/suppliers`

### Step 2: Create the tenant migration

Run `php artisan make:migration create_suppliers_table --path=database/migrations/tenant`. Open the generated file and fill in `up()` and `down()`:

```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->timestamps();
});
```

The `down()` method should call `Schema::dropIfExists('suppliers');`. Check the file actually contains your columns (the generated stub is nearly empty), then apply it to every company with `php artisan tenants:migrate`. To change an existing table later, create a new migration using `Schema::table(...)`. Never edit a migration that has already run.

### Step 3: Create the model

Run `php artisan make:model Supplier`. Then list the columns that may be mass-assigned:

```php
class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'email'];
}
```

Models for company data need no tenancy code. The tenant connection is already active during a company request, so the model reads the current company's database automatically.

### Step 4: Create the controller

Run `php artisan make:controller SupplierController`. Keep validation in the controller:

```php
public function index()
{
    return view('suppliers.index', ['suppliers' => Supplier::orderBy('name')->get()]);
}

public function store(Request $request)
{
    $data = $request->validate([
        'name'  => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
        'email' => ['nullable', 'email'],
    ]);
    Supplier::create($data);
    return back()->with('status', 'Supplier added.');
}
```

### Step 5: Add routes

Open `routes/tenant.php` and add inside the existing `auth` group, so the feature is only reachable by signed-in users of that company:

```php
Route::middleware('role:admin,manager')->group(function () {
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
});
```

Remember to add `use App\Http\Controllers\SupplierController;` at the top of the file. Use `role:admin,manager` for management screens, `role:admin` for settings and staff, and plain `auth` for screens every cashier needs.

### Step 6: Create the view

Create `resources/views/suppliers/index.blade.php` with a form (it must include `@csrf`) and a table that loops over `$suppliers`. Always print values with `{{ }}` so they are escaped. Reuse the green theme from the login and dashboard views, and add a link to the new screen on the dashboard.

### Step 7: Test on two companies

1. Run `php artisan optimize:clear` and `php artisan route:list` to confirm the routes exist.
2. Sign in as an admin on `acme.localhost:8000`, add a supplier.
3. Sign in on `zawadi.localhost:8000` and confirm the supplier is **not** visible.
4. Sign in as a cashier and confirm `/suppliers` returns 403.

### Step 8: Commit and open a pull request

Run `git status`, confirm no `.env` or `vendor/` appears, then `git add .`, commit with a clear message, `git push -u origin feature/suppliers`, and open a pull request into `feature/multi-tenant`.

### Rules for specific kinds of feature

- **Anything that touches money or stock.** Wrap the writes in `DB::transaction(function () { ... })` so an order, its items, its payment and the stock decrease succeed or fail together. Use `decimal` columns and never floats.
- **Settings and secrets per company.** Store them in the `settings` table with an `encrypted` cast. Never put a company's credentials in `.env` or in code.
- **Incoming webhooks and callbacks (for example PayHero).** Put the route inside the tenant route group so the company is identified from the callback's domain. Exclude the route from CSRF protection, verify the payment with the provider before marking anything paid, and make the handler safe to run twice.
- **File uploads (product images, logos).** Use the `public` disk. The tenancy filesystem bootstrapper keeps each company's files in its own folder.
- **Reports.** Query through Eloquent or the query builder with date filters and indexes. Never build SQL by string concatenation.
- **Scheduled or queued work.** Jobs dispatched inside a company request run in that company's context. For commands that must touch every company, loop with `tenancy()->runForMultiple(Tenant::all(), function () { ... })` or use `php artisan tenants:run`.
- **Central features (super-admin, company signup).** Put routes in `routes/web.php`, models and migrations in the central folders, and never read company tables from there without running inside a tenant context.
- **New user roles or permissions.** Add the role to the `users.role` enum with a new migration, then use it in `role:` middleware. Move to spatie/laravel-permission if rules become finer than three roles.

### Feature checklist

- Migration is in `database/migrations/tenant/` and was applied with `tenants:migrate`.
- Routes sit inside the tenancy and `auth` middleware group, with the correct `role:` restriction.
- Input is validated, output is escaped, and forms include `@csrf`.
- Multi-table writes use a transaction.
- Tested on two companies for data isolation, and with at least one role that must be denied.
- No secrets in code, and `.env` is not committed.
- Handover guide updated (new tables in section 6, new routes or commands in the repository layout).