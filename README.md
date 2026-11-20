# POS Application

A modern, web-based Point of Sale (POS) and business management system built with Laravel. Designed to simplify business operations through sales processing, inventory management, purchasing, staff administration and business reporting.

The application uses a multi-tenant architecture, allowing multiple businesses to operate independently with separate business data and tenant environments.

## Table of Contents

- Overview
- Features
- Technology Stack
- System Requirements
- Installation
- Environment Configuration
- Database Setup
- Running the Application
- Multi-Tenancy Setup
- Development Commands
- Git Workflow
- Security
- Troubleshooting
- Repository

## Features

### 1. Point of Sale
- Modern cashier interface.
- Product browsing and selection.
- Shopping cart and order management.
- Product quantity adjustments.
- Order discounts.
- Payment processing.
- Cash payment support.
- M-Pesa integration where configured.
- Receipt generation and printing.
- Held and resumed orders.
- Sales transaction management.

### 2. Inventory Management
- Product management.
- Product categories.
- Stock monitoring.
- Stock adjustments.
- Inventory movement tracking.
- Purchase management.
- Stock availability monitoring.

### 3. Business Management
- Business registration and provisioning.
- Company-specific settings.
- Business information management.
- Tenant domain management.
- Independent tenant databases.
- Central administration capabilities.

### 4. Staff Management
- Staff account management.
- Role-based access control.
- Permission management.
- User activation and access management.
- Different access levels for business employees.

### 5. Dashboard and Reporting
- Sales overview.
- Daily business activity.
- Payment information.
- Inventory information.
- Business management dashboard.

## Technology Stack

- Backend: Laravel
- Language: PHP
- Database: MySQL / MariaDB
- Frontend: Blade, HTML, CSS and JavaScript
- Asset bundler: Vite
- Package manager: Composer
- JavaScript package manager: npm
- Authentication: Laravel authentication
- Permissions: Spatie Laravel Permission
- Multi-tenancy: Stancl Tenancy

## System Requirements

Install the following before running the project:

| Software | Requirement |
|---|---|
| PHP | 8.3 or later, compatible with composer.json |
| Composer | Latest stable version |
| MySQL | MySQL 8 or compatible MariaDB |
| Node.js | Current supported LTS |
| npm | Included with Node.js |
| Git | Latest stable version |
| XAMPP | Optional local PHP and MySQL environment |

Required PHP extensions should be enabled according to Laravel and the installed Composer dependencies.

## Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/jacksonmuoki/pos-app.git
```

### Step 2: Enter the project directory

```bash
cd pos-app
```

### Step 3: Install PHP dependencies

```bash
composer install
```

### Step 4: Install frontend dependencies

```bash
npm install
```

### Step 5: Create the environment file

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux / macOS:

```bash
cp .env.example .env
```

### Step 6: Generate the application key

```bash
php artisan key:generate
```

## Environment Configuration

Open the `.env` file and configure your local application settings.

Example:

```env
APP_NAME="POS Application"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_central
DB_USERNAME=root
DB_PASSWORD=
```

Update the database credentials to match your local MySQL installation.

Do not commit your `.env` file to GitHub.

## Database Setup

### Step 1: Start MySQL

If using XAMPP, open the XAMPP Control Panel and start MySQL.

### Step 2: Create the central database

Open phpMyAdmin or MySQL and execute:

```sql
CREATE DATABASE pos_central;
```

The database name must match `DB_DATABASE` in your `.env` file.

### Step 3: Run central migrations

```bash
php artisan migrate
```

If the project provides central seeders, run them where appropriate:

```bash
php artisan db:seed
```

### Step 4: Tenant migrations

Tenant databases are managed separately by the multi-tenancy system.

After tenant provisioning, run:

```bash
php artisan tenants:migrate
```

Tenant databases must exist and be configured correctly before tenant migrations can run.

## Running the Application Locally

### Option A: Run Laravel

```bash
php artisan serve
```

Open:

http://127.0.0.1:8000

### Option B: Run frontend development assets

Open another terminal in the project directory:

```bash
npm run dev
```

Keep this terminal running while developing.

Alternatively, build the frontend assets:

```bash
npm run build
```

## Multi-Tenancy

The application uses Stancl Tenancy to provide isolated environments for different businesses.

Each tenant represents an individual business with its own domain and database.

The central application manages business registration and tenant provisioning. Tenant requests are identified using their configured domains.

Typical setup process:

1. Start the central application.
2. Access the central administration area.
3. Register or provision a business.
4. Configure its tenant domain.
5. Allow the application to create the tenant database.
6. Run tenant migrations.
7. Access the business using its registered tenant domain.

Example:

```text
http://demo.localhost:8000
```

Actual tenant domains depend on the configured domains in your local installation.

For local tenant domains, configure the appropriate hosts entries if your local environment requires them. Ensure the domain also points to your local web server.

## Useful Artisan Commands

Clear application caches:

```bash
php artisan optimize:clear
```

List routes:

```bash
php artisan route:list
```

Run migrations:

```bash
php artisan migrate
```

Run tenant migrations:

```bash
php artisan tenants:migrate
```

Display application information:

```bash
php artisan about
```

Start the local server:

```bash
php artisan serve
```

## Development Workflow

Pull the latest changes:

```bash
git pull origin main
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Build frontend assets:

```bash
npm run build
```

Clear cached configuration:

```bash
php artisan optimize:clear
```

## GitHub Repository

Repository:

https://github.com/jacksonmuoki/pos-app

Main branch:

```text
main
```

To contribute changes:

```bash
git add .
git commit -m "Describe your changes"
git push origin main
```

## Security Recommendations

- Never commit `.env` files.
- Keep database credentials private.
- Never publish application secrets or API keys.
- Configure payment provider credentials securely.
- Use HTTPS in production.
- Set `APP_DEBUG=false` in production.
- Use strong administrator credentials.
- Back up databases regularly.
- Restrict access to administrative functionality.

## Troubleshooting

### Composer dependency errors

Run:

```bash
composer install
```

Check your PHP version:

```bash
php -v
```

### Database connection errors

Check:

- MySQL is running.
- Database exists.
- Database name matches `.env`.
- Username and password are correct.
- PHP MySQL extensions are enabled.

### Application configuration problems

Run:

```bash
php artisan optimize:clear
php artisan key:generate
```

Only generate a new application key when setting up a fresh local installation or when intentionally rotating the key.

### Frontend assets are missing

Run:

```bash
npm install
npm run build
```

### Tenant cannot be identified

Check:

- Tenant domain is registered.
- Domain resolves to your local machine.
- Tenant database exists.
- Tenant migrations have been executed.
- Central domain and tenant domain configurations are correct.

## License

This project is maintained by its repository owner. Contact the repository owner for licensing and usage permissions.

---

**POS Application**

Modern Point of Sale and Business Management Software.
