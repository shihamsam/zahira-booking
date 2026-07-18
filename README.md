# Zahira Bookings

An online facility booking system for **Zahira College, Puttalam**, built with Laravel 13 · Inertia.js · Vue 3 · Tailwind CSS.

Two facilities are managed through a single system:

| Facility | Booking type | Pricing |
|---|---|---|
| **Zahira Green Ground** | Time-slot (daytime flat / nighttime per-hour with 2 or 4 lights) | Rs. 6,000 flat · Rs. 3,500/hr (4L) · Rs. 2,000/hr (2L) |
| **Azwar Hall** | Full-day event | Rs. 10,000/day + Rs. 10/chair |

---

## Features

### Public (no login required)

- Browse both facilities on the home page with descriptions and pricing
- Interactive month calendar — available dates in white, booked/blocked dates greyed out, active bookings shown in amber
- **Time-slot booking** — select Daytime, Night 4-Lights, or Night 2-Lights; hourly rate calculated automatically from start/end times
- **Azwar Hall add-ons** — chair count (Rs. 10 each) and sound-system request
- Booking form captures: Full name, NIC, Mobile, Email (optional), Purpose
- **Payment receipt upload** — optional at booking time; can be uploaded later via the Upload Receipt page
- **Upload Receipt page** (`/upload-receipt`) — enter a reference number, find the booking, upload or replace the receipt; notifies admins by email
- Booking confirmation page shows reference number, booking summary, bank details, and receipt status
- `/grounds/*` URLs redirect permanently to `/facilities/*`

### Admin panel (`/admin/login`)

**Dashboard**
- Today's bookings count and revenue
- This-month confirmed count and income
- Pending confirmations counter
- Today's bookings table
- "Needs confirmation" and "Upcoming bookings" panels

**Bookings**
- Paginated booking list with filters: status, facility, date range, keyword search (name / mobile / reference)
- Full booking detail: NIC, email, slot type, duration, dates, add-ons, receipt preview (image or PDF link)
- Admin receipt upload / replace (JPG, PNG, PDF, max 5 MB)
- **Confirm** — requires receipt; records who confirmed and when; emails booker; adds Google Calendar event
- **Reject** — for invalid receipts; frees dates; emails booker; removes Google Calendar event
- **Cancel** — frees dates; emails booker; removes Google Calendar event
- Status badge: Pending · Confirmed · Cancelled · Rejected

**Calendar**
- Week-view grid (Mon–Sun) with date-picker for instant week navigation
- Pending bookings shown in amber, confirmed in green, blocked dates in grey
- Click any booking to jump to its detail page

**Blocked Dates**
- Month calendar showing existing bookings (amber) and blocked dates (grey)
- Select one or multiple dates, choose a facility scope (all facilities or one specific), add a reason
- Blocked dates prevent new public bookings from being submitted
- Remove blocks individually from the list

**Facilities & Pricing**
- List both facilities with current slot rates
- Edit rates per slot in-browser; saved to the database (overrides the config defaults)
- Toggle facility active/inactive

**Reports**
- Date range presets: Weekly · Monthly · Quarterly · Yearly + custom range
- Summary: total confirmed income and booking count
- Breakdown by period and by facility
- **Export CSV** (UTF-8 BOM, opens cleanly in Excel) and **Export Excel** (SpreadsheetML `.xls`)

**Admin users**
- Create and delete admin accounts
- Self-delete and last-admin-delete are prevented

### Notifications

| Trigger | Recipients | Channel |
|---|---|---|
| New booking created | All admins + `BOOKING_NOTIFY_EXTRA_EMAILS` | Email |
| Receipt uploaded via Upload Receipt page | All admins | Email |
| Booking confirmed | Booker (if email provided) | Email |
| Booking cancelled | Booker (if email provided) | Email |
| Booking rejected | Booker (if email provided) | Email |
| Pending booking due within 2 days | All admins | Email (daily 08:00 scheduled command) |

### Google Calendar integration (Phase 3)

Code is fully implemented. Activates once credentials are configured in `.env`:

```
GOOGLE_CALENDAR_ID=your_calendar_id@group.calendar.google.com
GOOGLE_SERVICE_ACCOUNT_JSON=/absolute/path/to/google-credentials.json
GOOGLE_CALENDAR_TIMEZONE=Asia/Colombo
```

On confirm → event added. On cancel/reject → event removed. Failures are logged and never block the HTTP response.

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.2+) |
| Frontend | Vue 3 + Inertia.js (SPA) |
| Styling | Tailwind CSS 3 |
| Build | Vite 5 |
| Database | MySQL / MariaDB |
| Queues | Laravel sync (dev) / database or Redis (prod) |
| Email | Laravel Mail (queued mailables, SMTP) |

---

## Local setup (Laravel Herd)

[Herd](https://herd.laravel.com) is the simplest way to run the project locally — PHP, nginx `.test` routing, and Mailpit are all bundled.

```bash
# 1. Clone or place the project inside Herd's parked folder
cd ~/Herd
# project folder: zahira-ground-booking

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```
APP_URL=https://zahira-ground-booking.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zahira_ground_bookings
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="bookings@zahirags.lk"
```

Mailpit (bundled with Herd) catches all outgoing email at `http://localhost:8025`.

```bash
# 5. Migrate and seed
php artisan migrate --seed

# 6. Create the public storage symlink (for receipt images)
php artisan storage:link

# 7. Start the asset dev server
npm run dev
```

Herd serves the site automatically at **https://zahira-ground-booking.test**.

For a production-style minified build: `npm run build`.

---

## Local setup (without Herd)

Requires PHP 8.2+, Composer, Node 18+, MySQL.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# configure DB_* and MAIL_* in .env
php artisan migrate --seed
php artisan storage:link
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

---

## Default admin credentials

Seeded by `AdminUserSeeder`:

| | |
|---|---|
| **Email** | `admin@zahirags.lk` |
| **Password** | `change-this-password` |

Change the password immediately after first login via `php artisan tinker`:
```php
User::first()->update(['password' => Hash::make('your-new-password')]);
```

---

## Booking configuration

All values are set in `.env` (defaults in `config/booking.php`):

```
BOOKING_BANK_NAME="..."
BOOKING_BANK_ACCOUNT_NAME="..."
BOOKING_BANK_ACCOUNT_NUMBER="..."
BOOKING_BANK_BRANCH="..."
BOOKING_WHATSAPP_NUMBER="94XXXXXXXXX"
BOOKING_NOTIFY_EXTRA_EMAILS="ops@example.com,manager@example.com"
```

Pricing per slot is editable in-browser via **Admin → Facilities & Pricing**. Changes are stored in the database and override the config defaults.

---

## Scheduled tasks

Register Laravel's scheduler with a single system cron entry:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:

| Schedule | Command | What it does |
|---|---|---|
| Daily 08:00 | `bookings:alert-pending` | Emails all admins a list of pending bookings whose earliest date is within 2 days |

---

## Automated testing

The test suite runs **70 feature tests** (269 assertions) against a real MariaDB instance in Docker. No real mailbox or Google credentials needed.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) running
- PHP 8.2+ and Composer on your PATH

### Quick start

```powershell
# Full suite (PowerShell from project root):
.\scripts\run-tests.ps1

# One test file only:
.\scripts\run-tests.ps1 -Filter BookingFlowTest

# Leave the container running after tests (useful for debugging):
.\scripts\run-tests.ps1 -KeepAlive

# Stop the container manually:
docker compose -f docker-compose.test.yml down
```

The script starts a disposable MariaDB 11 container on port **3307** (data in RAM, discarded on stop), migrates the schema, runs PHPUnit, and tears down.

### Manual run

```powershell
docker compose -f docker-compose.test.yml up -d
php artisan migrate:fresh --force --env=testing
php vendor/bin/phpunit --colors=always --testdox
docker compose -f docker-compose.test.yml down
```

### Test suites

| Suite | Tests | Covers |
|---|---|---|
| `BookingFlowTest` | 15 | Home page, facility page, booking creation with/without receipt, validation, double-booking, slot isolation, hourly pricing, Azwar Hall |
| `AdminBookingTest` | 25 | Login, dashboard, list/filter/detail, receipt upload, confirm/cancel/reject with email and Google Calendar job assertions |
| `ReceiptUploadTest` | 11 | Reference lookup, upload, admin email, file type validation, terminal-status rejection |
| `BlockedDatesTest` | 9 | Block dates (single, multiple, facility-specific, global), calendar API, booking rejection |
| `PendingAlertCommandTest` | 8 | Alert fires for bookings due today/tomorrow/in 2 days, skips confirmed/cancelled, groups all at-risk in one email |

### Email verification

All mailables implement `ShouldQueue`. Tests use `Mail::fake()` + `Mail::assertQueued()` to verify the correct mailable was dispatched with the right data — no SMTP or outbox required.

---

## Project structure

```
app/
  Console/Commands/     AlertOverduePendingBookings.php
  Http/Controllers/
    Public/             HomeController, BookingController, ReceiptUploadController
    Admin/              DashboardController, BookingController, CalendarController,
                        BlockedDateController, ResourceController, ReportController,
                        AdminUserController
    Auth/               AuthenticatedSessionController
  Jobs/                 AddBookingToGoogleCalendar, RemoveBookingFromGoogleCalendar
  Mail/                 NewBookingReceived, BookingConfirmed, BookingCancelled,
                        BookingRejected, ReceiptUploaded, PendingBookingAlert
  Models/               Resource, Booking, BookingDate, BlockedDate, User
  Services/             BookingService, PricingService, GoogleCalendarService

database/
  migrations/           11 migrations covering all tables and schema changes
  seeders/              AdminUserSeeder, ResourceSeeder (Zahira Green + Azwar Hall)

resources/js/
  Pages/Public/         Home, ResourceShow, BookingConfirmation, ReceiptUpload
  Pages/Admin/          Dashboard, Bookings/Index, Bookings/Show, Calendar/Index,
                        BlockedDates/Index, Resources/Index, Reports/Index, Admins/Index
  Pages/Auth/           Login
  Components/           Calendar, StatusBadge, Modal
  Layouts/              PublicLayout, AdminLayout

public/images/          logo.png, logo-text.png (Zahira College branding)
scripts/                run-tests.ps1
docs/                   BusinessRequirements_ZzahiraBookings.pdf, TechnicalDocumentation.md
tests/Feature/          5 test suites, 70 tests
```
