# Zahira Ground Booking

A ground/resource booking system for Zahira College, built with **Laravel 11 + Inertia.js + Vue 3 + Tailwind CSS**.

## What's included

- **Public booking flow** — visitors pick a ground (currently *Zahira Green Ground*, but the `resources` table supports adding more grounds later), select one or more dates on a calendar, and submit their name, mobile number and purpose.
- **Offline payment** — after booking, the visitor sees bank deposit details and a "send on WhatsApp" button pre-filled with their reference number.
- **Admin panel** (`/admin/login`) —
  - Dashboard with pending/confirmed/income stats
  - Booking history with search, status/ground/date filters, pagination
  - Upload the deposit receipt image against a booking, then confirm it
  - Cancel a booking (with a reason) — frees the dates back up
  - Income reports: monthly / quarterly / yearly presets or a custom date range, broken down by period and by ground, with CSV export
  - Manage admin accounts (create/remove, no roles — all admins are equal)
- Email notification to all admin accounts whenever a new booking comes in
- Double-booking protection via a database transaction with row locking

This repo contains only the **application-specific code** (models, controllers, migrations, routes, and the resources/js frontend) — not a full vendor/Laravel install. You'll lay it over a fresh Laravel installation as described below. This is because your computer's package registries (Composer/Packagist) weren't reachable from the sandbox this was built in, so the code is written and ready but hasn't been `composer install`-ed or booted here.

## Setup with Laravel Herd (recommended for local dev)

[Herd](https://herd.laravel.com) bundles PHP, nginx-based routing for `.test` domains, and Mailpit (for catching outgoing email locally), so it removes most of the manual server/mail setup.

```bash
# 1. Open Herd, then create the project inside Herd's parked folder.
#    On macOS/Windows this defaults to ~/Herd — cd there first.
cd ~/Herd

# 2. Create a fresh Laravel 11 app (Herd's bundled PHP/Composer are already on your PATH)
composer create-project laravel/laravel zahira-ground-booking
cd zahira-ground-booking

# 3. Add Inertia's Laravel adapter
composer require inertiajs/inertia-laravel

# 4. Copy every file from this delivered project INTO the new app, overwriting the
#    matching files (bootstrap/app.php, routes/web.php, app/, resources/, config/booking.php,
#    database/, package.json, vite.config.js, tailwind.config.js, postcss.config.js, .env.example).
#    Do NOT overwrite vendor/, storage/, or an existing .env.

# 5. Install JS dependencies
npm install

# 6. Configure environment
cp .env.example .env
php artisan key:generate
```

Because the project sits in `~/Herd/zahira-ground-booking`, Herd automatically serves it at **https://zahira-ground-booking.test** — no `php artisan serve` needed. If it doesn't pick it up immediately, run `herd link` from inside the project folder, then `herd secure` to get a trusted HTTPS certificate.

Edit `.env`:

```
APP_URL=https://zahira-ground-booking.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zahira_ground_booking
DB_USERNAME=root
DB_PASSWORD=
```

For the database, use whichever MySQL you already have running locally (Herd Pro's *Services* tab can spin one up with one click if you don't have one; otherwise a Homebrew/Docker MySQL on port 3306 works fine — just create the `zahira_ground_booking` database first, e.g. via TablePlus or `mysql -u root -e "create database zahira_ground_booking"`).

For mail, point at Herd's bundled Mailpit so you can see the "new booking" emails without sending anything real:

```
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="bookings@zahirags.lk"
```

Mailpit's inbox is viewable from the Herd tray icon (or `http://localhost:8025` if you have it running standalone).

Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run dev     # Vite dev server, for hot-reloading while you work
```

Visit `https://zahira-ground-booking.test` for the public site and `https://zahira-ground-booking.test/admin/login` for the admin panel.

For a production-style build (minified assets, no Vite dev server): `npm run build`.

## Setup without Herd

You'll need PHP 8.2+, Composer, Node 18+, and MySQL.

```bash
composer create-project laravel/laravel zahira-ground-booking
cd zahira-ground-booking
composer require inertiajs/inertia-laravel
# copy this project's files over, as described above
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

npm run dev        # in one terminal (asset dev server)
php artisan serve  # in another terminal
```

## Default admin login

Seeded by `AdminUserSeeder`:

- **Email:** `admin@zahirags.lk`
- **Password:** `change-this-password`

**Change this password immediately** — either through a `php artisan tinker` session (`User::first()->update(['password' => Hash::make('...')])`) or by adding a password-change screen later.

## Configuring bank/WhatsApp details

Edit these in `.env` (defaults live in `config/booking.php`):

```
BOOKING_BANK_NAME="..."
BOOKING_BANK_ACCOUNT_NAME="..."
BOOKING_BANK_ACCOUNT_NUMBER="..."
BOOKING_BANK_BRANCH="..."
BOOKING_WHATSAPP_NUMBER="94XXXXXXXXX"   # country code, no + or spaces
BOOKING_NOTIFY_EXTRA_EMAILS="someone@example.com,another@example.com"
```

Admin notification emails always go to every registered admin account, plus any addresses listed in `BOOKING_NOTIFY_EXTRA_EMAILS`.

## Adding a second ground later

Since `resources` is a real table, adding another ground is just a row:

```php
Resource::create([
    'name' => 'Zahira Indoor Hall',
    'slug' => 'zahira-indoor-hall',
    'description' => '...',
    'price_per_day' => 3000,
    'is_active' => true,
]);
```

It'll automatically appear on the public home page and get its own booking calendar and reports breakdown.

## SMS notifications (future)

Not built yet, as requested. When you're ready, the natural place to add it is next to `BookingService::notifyAdmins()` (for admin alerts) — a similar hook can be added for the depositor once you've chosen an SMS gateway.

## Design notes

The visual identity is deliberately "match-day" — deep turf green, chalk white, floodlight amber accents, a condensed display face for headings, and a monospace face for reference numbers and dates (so bookings read a bit like scoreboard entries). Colours and fonts live in `tailwind.config.js` and `resources/views/app.blade.php` if you want to adjust the palette.

## Project structure quick reference

```
app/Models/            Resource, Booking, BookingDate, User
app/Services/          BookingService.php (availability + booking creation logic)
app/Http/Controllers/
  Public/              HomeController, BookingController (public booking flow)
  Auth/                AuthenticatedSessionController (admin login)
  Admin/               DashboardController, BookingController, ReportController, AdminUserController
database/migrations/   resources, bookings, booking_dates + standard Laravel tables
resources/js/Pages/    Public/... and Admin/... Inertia pages
resources/js/Components/ Calendar.vue, StatusBadge.vue, Modal.vue
```
