# NGH CRM v2

A customer relationship management (CRM) application built with Laravel and Inertia.js + Vue 3. It manages companies, customers, contacts, leads, tasks, notes, and messages, with real-time updates powered by Laravel Reverb.

## Overview

NGH CRM v2 is a server-driven single-page application. The backend is a Laravel 13 API rendered through Inertia, and the frontend is a Vue 3 SPA styled with Tailwind CSS v4. Core domain entities include:

- **Companies & Company Types** — organizations you do business with
- **Customers & Customer Roles** — people associated with companies
- **Contacts** — points of contact
- **Leads** — sales opportunities in the pipeline
- **Tasks** — scheduled work, surfaced on a FullCalendar view
- **Notes & Messages** — activity and communication history

### Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Inertia.js v3, Vue 3, Tailwind CSS v4, Vite |
| Real-time | Laravel Reverb (WebSockets) via Laravel Echo |
| Database | MariaDB / MySQL |
| Architecture | [lorisleiva/laravel-actions](https://laravelactions.com/) (thin controllers, logic in Actions) |
| Key packages | Spatie Permission, Spatie Activitylog, Spatie Medialibrary, Ziggy |
| Testing | Pest v4 |
| Formatting | Laravel Pint |

## Requirements

Before you begin, make sure you have the following installed:

- **PHP 8.3+** (8.4 recommended) with common extensions: `mbstring`, `xml`, `curl`, `pdo_mysql`, `bcmath`, `gd`, `zip`
- **Composer 2**
- **Node.js 20+** (24 recommended) and **npm**
- **MariaDB 10.6+** or **MySQL 8+**
- **Git**

---

## Local Setup

Clone the repository first, then follow the platform-specific instructions below.

### 1. Clone the project

```bash
git clone git@github.com:charles-chadwick/ngh-CRMv2.git ngh-CRMv2
cd ngh-CRMv2
```

### 2. Install prerequisites

<details>
<summary><strong>Linux (Ubuntu / Debian)</strong></summary>

```bash
# PHP 8.4 + extensions (via ondrej/php PPA)
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4 php8.4-cli php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-mysql php8.4-bcmath php8.4-gd php8.4-zip unzip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 24 (via nvm)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
nvm install 24

# MariaDB
sudo apt install -y mariadb-server
sudo systemctl start mariadb
```

</details>

<details>
<summary><strong>macOS (Homebrew)</strong></summary>

```bash
# Install Homebrew if you don't have it
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# PHP, Composer, Node, MariaDB
brew install php@8.4 composer node mariadb

# Start the database
brew services start mariadb
```

</details>

<details>
<summary><strong>Windows</strong></summary>

The smoothest path on Windows is **WSL2** (Windows Subsystem for Linux) — install Ubuntu from the Microsoft Store and follow the Linux instructions above.

To run natively on Windows instead, install [Laravel Herd](https://herd.laravel.com/windows) (bundles PHP, Composer, and Node), plus a MariaDB/MySQL server such as [MariaDB for Windows](https://mariadb.org/download/) or [XAMPP](https://www.apachefriends.org/). Run the commands below in **PowerShell** or the Herd terminal.

```powershell
# With Herd installed, PHP, Composer and Node are already available.
# Install Node separately if needed: https://nodejs.org (LTS)
```

</details>

### 3. Create the database

Create an empty database named `ngh_crmv2` (the default in `.env.example`):

```bash
# Linux / macOS
sudo mysql -e "CREATE DATABASE ngh_crmv2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```powershell
# Windows (adjust path to mysql.exe as needed)
mysql -u root -e "CREATE DATABASE ngh_crmv2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Update `DB_USERNAME` / `DB_PASSWORD` in your `.env` (created in the next step) if your database credentials differ from the defaults.

### 4. Install dependencies and configure

Install PHP and JavaScript dependencies, copy the environment file, generate the app key, migrate, and build the frontend:

```bash
composer install
cp .env.example .env      # Windows PowerShell: copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Or run the bundled shortcut, which does all of the above in one step:

```bash
composer setup
```

### 5. Seed sample data (optional)

Populate the database with sample companies, customers, leads, tasks, and users:

```bash
php artisan db:seed
```

> **Note:** Activity-log causers on seeded records only populate when seeders are run individually rather than through the full `db:seed`.

---

## Running the App

Start the full development environment (app server, queue worker, log viewer, and Vite) with a single command:

```bash
composer dev
```

This runs, concurrently:

- `php artisan serve` — the application at http://localhost:8000
- `php artisan queue:listen` — background job processing
- `php artisan pail` — live log tailing
- `npm run dev` — the Vite dev server with hot module replacement

### Real-time broadcasting (Reverb)

If you're working with real-time features, start the WebSocket server in a separate terminal:

```bash
php artisan reverb:start
```

Set the `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` values in your `.env` first.

---

## Testing & Code Style

```bash
# Run the test suite (Pest)
php artisan test --compact

# Run a single test file or filter
php artisan test --compact --filter=CustomerTest

# Format PHP to the project standard (run before committing)
vendor/bin/pint
```

---

## Useful Commands

| Command | Description |
|---------|-------------|
| `php artisan migrate:fresh --seed` | Rebuild the database from scratch with sample data |
| `php artisan route:list --except-vendor` | List application routes |
| `npm run build` | Build production frontend assets |
| `php artisan tinker` | Interactive REPL in application context |

## License

This project is proprietary. All rights reserved.
