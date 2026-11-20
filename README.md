# POS-APP – Multi-Tenant Point of Sale System

A modern, scalable and multi-tenant Point of Sale (POS) application built with Laravel. Designed to help businesses manage sales, inventory, purchases, employees, branches and financial operations from one centralized platform.

## Overview

POS-APP is a web-based business management solution where multiple companies can operate independently using their own tenant environments and databases.

### Key Features

- **Multi-Tenant Architecture** – Independent company environments and databases.
- **Company Management** – Centralized super-admin company administration.
- **Point of Sale** – Product sales, order management and receipt generation.
- **Inventory Management** – Stock tracking and stock adjustments.
- **Product Management** – Products, categories and brands.
- **Purchase Management** – Supplier and purchase tracking.
- **Sales Management** – Sales transactions and payment records.
- **Payment Integration** – Payment processing architecture with M-Pesa integration support.
- **Staff Management** – Employee accounts and role-based permissions.
- **Reporting** – Business performance and transaction reports.
- **Company Settings** – Individual tenant configuration.
- **Responsive Interface** – Modern interface for business administration and cashier operations.

## Technology Stack

| Component | Technology |
|---|---|
| Backend | PHP / Laravel |
| Database | MySQL |
| Frontend | Blade, HTML, CSS, JavaScript |
| Multi-Tenancy | Stancl Tenancy |
| Authentication | Laravel Authentication |
| Permissions | Spatie Laravel Permission |
| Dependency Management | Composer |
| Frontend Dependencies | NPM |

## Requirements

- PHP 8.3 or newer (check composer.json for the exact requirement).
- Composer
- MySQL 8.0 or compatible MySQL/MariaDB version
- Node.js and NPM
- Apache or Nginx
- Git

## Installation Guide

### 1. Clone the Repository

```bash
git clone -b feature/multi-tenant https://github.com/jacksonmuoki/pos-app.git
```

Navigate into the project:

```bash
cd pos-app
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the example environment file:

Windows:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure Database

Create your central MySQL database.

Open `.env` and configure:

```env
APP_NAME="POS-APP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos
DB_USERNAME=root
DB_PASSWORD=
```

Update the database credentials to match your environment.

Configure tenant database credentials and central domains according to the tenancy configuration.

### 5. Run Database Migrations

Run the central database migrations:

```bash
php artisan migrate
```

Run tenant migrations:

```bash
php artisan tenants:migrate
```

### 6. Install Frontend Dependencies

```bash
npm install
```

Build frontend assets:

```bash
npm run build
```

For development:

```bash
npm run dev
```

### 7. Start the Application

```bash
php artisan serve
```

Open:

http://127.0.0.1:8000

## Multi-Tenant Configuration

The application uses Stancl Tenancy to provide separate company environments.

Each company can have:

- Its own tenant database.
- Its own domain or subdomain.
- Independent users and permissions.
- Independent products and inventory.
- Independent sales and purchases.
- Company-specific settings.

The central administration environment manages company provisioning and tenant administration.

For local development, configure your central and tenant domains appropriately. Ensure tenant domains resolve to your local web server.

## Project Structure

```text
pos-app/
├── app/
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Providers/
│   ├── Services/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   └── migrations/
│       └── tenant/
├── public/
├── resources/
│   └── views/
├── routes/
├── storage/
├── tests/
├── composer.json
└── README.md
```

## Security Notes

- Never commit your `.env` file.
- Keep database credentials private.
- Use HTTPS in production.
- Set `APP_DEBUG=false` in production.
- Protect payment gateway credentials.
- Configure appropriate database permissions.
- Regularly back up central and tenant databases.

## Development

Current development branch:

`feature/multi-tenant`

Contributions and improvements are welcome.

## License

All rights reserved unless otherwise specified by the repository owner.
