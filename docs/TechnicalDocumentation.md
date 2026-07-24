# Zahira Bookings — Technical Documentation

> Last updated: 2026-07-24
> Stack: Laravel 12 · Vue 3 · Inertia.js · Tailwind CSS · MySQL

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture](#2-architecture)
3. [Technology Stack](#3-technology-stack)
4. [Data Model](#4-data-model)
5. [Route Reference](#5-route-reference)
6. [Controller & Service Reference](#6-controller--service-reference)
7. [Frontend Pages & Layouts](#7-frontend-pages--layouts)
8. [Configuration Reference](#8-configuration-reference)
9. [Background Jobs & Scheduled Commands](#9-background-jobs--scheduled-commands)
10. [Email Notifications](#10-email-notifications)
11. [Deployment](#11-deployment)

---

## 1. System Overview

Zahira Bookings is a facility reservation system for Zahira College, Puttalam, Sri Lanka. It manages bookings for two venues:

| Facility | Shortcode | Slug | Booking Model |
|---|---|---|---|
| **Zahira Green** | ZGG | `zahira-green-ground` | Daytime flat rate OR per-hour night slots with 2 or 4 lighting options |
| **Azwar Hall** | AZW | `azwar-hall` | Full-day flat rate + optional chair add-on |

Public users browse available dates, choose a time slot, submit their details, and upload a payment receipt. Admins review bookings, confirm or reject them, and manage dates and reports.

---

## 2. Architecture

```
┌──────────────────────────────────────────────────────┐
│                  Public Browser                      │
│  Vue 3 (Inertia SPA) + Tailwind CSS                  │
└─────────────────────┬────────────────────────────────┘
                      │ HTTP / Inertia requests
┌─────────────────────▼────────────────────────────────┐
│               Laravel 12 Backend                     │
│  Controllers · Services · Form Requests              │
│  Laravel Mail (queued) · Laravel Auth (session)      │
└──────────┬───────────────────────┬───────────────────┘
           │                       │
    ┌──────▼──────┐        ┌───────▼──────────┐
    │   MySQL DB  │        │  File Storage    │
    │ (migrations)│        │ storage/public/  │
    └─────────────┘        │  receipts/       │
                           └──────────────────┘
                                   │
                           ┌───────▼──────────┐
                           │  Google Calendar │
                           │  (service acct)  │
                           └──────────────────┘
```

**Key design decisions**

- **Inertia.js** replaces a REST/GraphQL API. All server responses are full Inertia page renders or redirects — no separate JSON API layer.
- **Pessimistic locking** (`lockForUpdate`) in `BookingService::createBooking()` prevents double-bookings under concurrent requests.
- **Per-hour booking_dates rows** for night slots: one row per booked hour with `slot_hour` (18–23 = 6 PM–11 PM, 0–5 = midnight–5 AM mapped as 24–29 in the UI scale). This allows partial-hour availability tracking.
- **Role column on users table**: `admin` (default) vs `super_admin`. Super Admin gates user management; no external RBAC package required.
- **Global Inertia shared props** (via `HandleInertiaRequests::share()`): `auth.user`, `auth.user.isSuperAdmin`, `flash.success`, `flash.error`, `supportPhone`, `supportPhone2`.

---

## 3. Technology Stack

| Package | Version | Purpose |
|---|---|---|
| `laravel/framework` | ^12 | PHP backend framework |
| `inertiajs/inertia-laravel` | — | Server-driven SPA bridge |
| `vue` | ^3 | Frontend reactivity (Composition API) |
| `@inertiajs/vue3` | — | Vue adapter for Inertia |
| `tailwindcss` | ^3 | Utility-first CSS |
| `vite` | ^5 | Frontend build tool |
| `google/apiclient` | — | Google Calendar API integration |

**Custom Tailwind color palette** (defined in `tailwind.config.js`):

| Token | Semantic Role |
|---|---|
| `pitch` | Dark green — primary brand color (sidebar, headings) |
| `chalk` | Off-white — light backgrounds, text on dark |
| `floodlight` | Amber/gold — accents, greeting text, active states |
| `ink` | Dark grey — body text |
| `clay` | Red/orange — errors, danger states |

---

## 4. Data Model

### Schema

```
users
  id, name, email, password, role (varchar 20, default 'admin'),
  remember_token, timestamps

resources
  id, name, slug (unique), shortcode (varchar 10),
  description, location, image_path,
  price_per_day (decimal 10,2), pricing_overrides (json),
  is_active (bool), timestamps

bookings
  id, reference_no (unique), resource_id (FK),
  full_name, mobile_number,
  nic (nullable), email (nullable), purpose (nullable),
  slot_type (enum: full_day|daytime|night_4lights|night_2lights),
  start_time, end_time, hours (smallint),
  chair_count (smallint), sound_system_requested (bool),
  total_amount (decimal 10,2),
  status (enum: pending|confirmed|cancelled|rejected),
  receipt_path,
  admin_notes,
  google_event_id,
  confirmed_by (FK users), confirmed_at,
  cancelled_by (FK users), cancelled_at, cancellation_reason,
  rejected_by (FK users), rejected_at, rejection_reason,
  timestamps

booking_dates
  id, booking_id (FK), resource_id (FK),
  date (date), slot_type (varchar),
  slot_hour (smallint, nullable),   ← per-hour tracking for night slots
  unit_price (decimal 10,2), timestamps
  INDEX (resource_id, date)

blocked_dates
  id, resource_id (FK, nullable — null = all facilities),
  date (date), reason (varchar 255),
  created_by (FK users), timestamps
```

### Booking status flow

```
pending  →  confirmed  (admin verifies receipt, dispatches Google Calendar job)
         →  rejected   (admin rejects booking with reason)
         →  cancelled  (admin or auto-alert cancels booking)
confirmed →  cancelled  (admin can still cancel a confirmed booking)
```

### Night-slot hour encoding

The UI represents the 12-hour night window (6 PM → 6 AM next day) as hours 18–29 on a virtual 30-hour clock. `booking_dates.slot_hour` stores this value:

| slot_hour | Clock time |
|---|---|
| 18 | 6:00 PM |
| 19 | 7:00 PM |
| … | … |
| 23 | 11:00 PM |
| 0 | Midnight (00:00) |
| 1 | 1:00 AM |
| … | … |
| 5 | 5:00 AM |

Daytime and full_day bookings have `slot_hour = null`.

### Reference number format

`{SHORTCODE}-{YYYYMMDD}-{4 random uppercase chars}`
Example: `ZGG-20260724-AB3X`

---

## 5. Route Reference

### Public routes (no auth required)

| Method | URI | Handler | Description |
|---|---|---|---|
| GET | `/` | `Public/HomeController@index` | Home page — facility picker + user details form |
| GET | `/facilities/{slug}` | `Public/BookingController@show` | Facility detail / availability calendar |
| GET | `/facilities/{slug}/book` | `Public/BookingController@booking` | Time-slot booking page |
| GET | `/facilities/{slug}/timeslots` | `Public/BookingController@timeslots` | JSON — booked night hours for a date |
| GET | `/facilities/{slug}/availability` | `Public/BookingController@availability` | JSON — unavailable dates |
| POST | `/facilities/{slug}/bookings` | `Public/BookingController@store` | Submit a booking |
| GET | `/bookings/{ref}/confirmation` | `Public/BookingController@confirmation` | Booking confirmation page |
| GET | `/upload-receipt` | `Public/ReceiptUploadController@show` | Receipt lookup form |
| GET | `/upload-receipt/{ref}` | `Public/ReceiptUploadController@booking` | Receipt upload page |
| POST | `/upload-receipt/{ref}` | `Public/ReceiptUploadController@upload` | Submit receipt file |
| GET | `/grounds/{slug}` | redirect | 301 redirect from old `/grounds/` URLs |

### Admin auth routes

| Method | URI | Handler |
|---|---|---|
| GET | `/admin/login` | `Auth/AuthenticatedSessionController@create` |
| POST | `/admin/login` | `Auth/AuthenticatedSessionController@store` |
| POST | `/admin/logout` | `Auth/AuthenticatedSessionController@destroy` |

### Admin panel routes (auth required)

| Method | URI | Handler | Super Admin only |
|---|---|---|---|
| GET | `/admin/dashboard` | `Admin/DashboardController@index` | |
| GET | `/admin/bookings` | `Admin/BookingController@index` | |
| GET | `/admin/bookings/{id}` | `Admin/BookingController@show` | |
| POST | `/admin/bookings/{id}/receipt` | `Admin/BookingController@uploadReceipt` | |
| POST | `/admin/bookings/{id}/confirm` | `Admin/BookingController@confirm` | |
| POST | `/admin/bookings/{id}/cancel` | `Admin/BookingController@cancel` | |
| POST | `/admin/bookings/{id}/reject` | `Admin/BookingController@reject` | |
| GET | `/admin/calendar` | `Admin/CalendarController@index` | |
| GET | `/admin/blocked-dates` | `Admin/BlockedDateController@index` | |
| POST | `/admin/blocked-dates` | `Admin/BlockedDateController@store` | |
| DELETE | `/admin/blocked-dates/{id}` | `Admin/BlockedDateController@destroy` | |
| GET | `/admin/resources` | `Admin/ResourceController@index` | |
| PUT | `/admin/resources/{id}` | `Admin/ResourceController@update` | |
| GET | `/admin/reports` | `Admin/ReportController@index` | |
| GET | `/admin/reports/export` | `Admin/ReportController@export` | |
| GET | `/admin/admins` | `Admin/AdminUserController@index` | ✅ |
| POST | `/admin/admins` | `Admin/AdminUserController@store` | ✅ |
| DELETE | `/admin/admins/{id}` | `Admin/AdminUserController@destroy` | ✅ |
| GET | `/admin/profile` | `Admin/ProfileController@index` | |
| PUT | `/admin/profile/password` | `Admin/ProfileController@updatePassword` | |

---

## 6. Controller & Service Reference

### Services

**`BookingService`** — Core booking logic.
- `unavailableDates(resource, from, to, slotType?)` — Returns booked + blocked dates for the calendar.
- `unavailableDatesBySlot(resource, from, to)` — Returns unavailable dates keyed by slot type.
- `createBooking(resource, dates, …, slotHours[])` — DB transaction: conflict check → blocked date check → price calculation → create `Booking` + `BookingDate` rows → notify admins.
- `generateReferenceNo(resource)` — Generates collision-safe reference numbers using the resource `shortcode`.

**`PricingService`** — Pricing calculations.
- `slots(resource)` — Returns slot config array from `config/booking.php` for the resource slug.
- `unitPrice(resource, slotType, hours)` — Price for a single date/hour. Falls back to `resource.price_per_day` if no config entry.
- `totalAmount(resource, slotType, dates, hours, chairCount)` — Total including chair add-on (Azwar Hall).
- Reads `resource.pricing_overrides` (JSON column) first; falls back to config defaults.

**`GoogleCalendarService`** — Google Calendar sync.
- `addEvent(booking)` — Creates a Calendar event and returns the Google event ID.
- `removeEvent(googleEventId)` — Deletes the event.

### Key middleware

**`HandleInertiaRequests`** — Shares global props with every page:
```php
'auth' => ['user' => [..., 'isSuperAdmin' => $user->isSuperAdmin()]],
'flash' => ['success' => session('success'), 'error' => session('error')],
'supportPhone' => config('booking.support_phone'),
'supportPhone2' => config('booking.support_phone_2'),
```

### Form Requests

**`StoreBookingRequest`** — Validates the public booking form submission:
- `full_name`, `mobile_number` (required, `^0\d{9}$`)
- `nic` (nullable), `email` (nullable email)
- `slot_type` (enum), `dates` (array of Y-m-d strings)
- `slot_hours` (required array min:1 for night slots; nullable for daytime/full_day)
- Items within `slot_hours`: integer 0–23 or 18–29 (night hour range)

---

## 7. Frontend Pages & Layouts

### Layouts

| File | Usage |
|---|---|
| `LandingLayout.vue` | Home page only. No header/footer. Full-viewport two-column shell. |
| `PublicLayout.vue` | All other public pages. Dark green header with logos, dual support phone numbers, sticky footer. `max-w-7xl` container. |
| `AdminLayout.vue` | All admin pages. `h-screen overflow-hidden` outer. Fixed sidebar (`h-full`). Scrollable `<main>` (`overflow-y-auto`). Sidebar shows Hello greeting → Dashboard → nav items → Admins (super_admin only). |

### Public pages

| Page | Route | Key features |
|---|---|---|
| `Home.vue` | `/` | Two-column viewport layout. Facility selector cards. Name + phone + email form. "Choose Your Time Slot" button. Receipt upload shortcut. Support phone links. |
| `ResourceShow.vue` | `/facilities/{slug}` | Availability calendar. Legacy booking form (single-date, full slot). |
| `BookingTimeslot.vue` | `/facilities/{slug}/book` | Viewport-constrained two-panel. Calendar on left (single-select). Slot grid on right (1 daytime tile + 12 hourly night tiles). Per-hour availability from `/timeslots` API. Pre-submit review modal with payment deadline (now + 3 hours). |
| `BookingConfirmation.vue` | `/bookings/{ref}/confirmation` | Two-panel viewport layout. Left: booking details with deduplicated dates and individual night slot labels. Right: prominent reference number with copy button, bank details, WhatsApp send button. |
| `ReceiptUpload.vue` | `/upload-receipt/{ref}` | Reference lookup → upload card. Success shown as centered modal overlay (not inline banner). |

### Admin pages

| Page | Route | Key features |
|---|---|---|
| `Dashboard.vue` | `/admin/dashboard` | Stats: pending count, monthly confirmed/income, total, today count/income. Today's bookings list. Upcoming bookings. Recent pending. |
| `Bookings/Index.vue` | `/admin/bookings` | Searchable, filterable table. "Date & Slot" column shows actual slots as pills (night hours formatted as "H:00 AM – H:00 AM"). |
| `Bookings/Show.vue` | `/admin/bookings/{id}` | Full booking detail. Deduplicated date list, individual night slot labels. Confirm / Reject / Cancel actions. Receipt preview. |
| `Calendar/Index.vue` | `/admin/calendar` | Time-grid calendar. "Day Time" all-day row. 12 hourly night rows (6 PM → 6 AM). Tiles show facility shortcode. Midnight+ rows tinted. |
| `BlockedDates/Index.vue` | `/admin/blocked-dates` | Calendar date picker. Lists active blocked dates. Guards against blocking dates that have confirmed bookings. |
| `Resources/Index.vue` | `/admin/resources` | Pricing override editor per facility. |
| `Reports/Index.vue` | `/admin/reports` | Date range + preset selector. Period breakdown table. Per-facility breakdown. CSV / Excel export. |
| `Admins/Index.vue` | `/admin/admins` | Super Admin only. List admins with role badges. Add new admin. Remove admins (cannot remove super_admin). |
| `Profile/Index.vue` | `/admin/profile` | Account details panel. Change password form. |

---

## 8. Configuration Reference

### `config/booking.php`

| Key | Env Variable | Description |
|---|---|---|
| `notify_extra_emails` | `BOOKING_NOTIFY_EXTRA_EMAILS` | Comma-separated extra email recipients for new booking alerts |
| `bank.bank_name` | `BOOKING_BANK_NAME` | Bank name on confirmation page |
| `bank.account_name` | `BOOKING_BANK_ACCOUNT_NAME` | Account holder name |
| `bank.account_number` | `BOOKING_BANK_ACCOUNT_NUMBER` | Bank account number |
| `bank.branch` | `BOOKING_BANK_BRANCH` | Branch name |
| `whatsapp_number` | `BOOKING_WHATSAPP_NUMBER` | WhatsApp number for booker → admin receipt messages |
| `support_phone` | `BOOKING_SUPPORT_PHONE` | Primary support phone (displayed in header + home page) |
| `support_phone_2` | `BOOKING_SUPPORT_PHONE_2` | Secondary support phone |
| `booking_window_months` | — | How many months ahead the public calendar allows booking (default: 3) |
| `azwar_hall_chair_rate` | — | Per-chair fee in LKR for Azwar Hall (default: 10) |
| `pricing.{slug}.{slot_type}` | — | Slot definitions: `type` (flat/hourly), `rate` (LKR), `label` |

### Required `.env` variables

```
APP_KEY=
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

BOOKING_BANK_NAME=
BOOKING_BANK_ACCOUNT_NAME=
BOOKING_BANK_ACCOUNT_NUMBER=
BOOKING_BANK_BRANCH=
BOOKING_WHATSAPP_NUMBER=
BOOKING_SUPPORT_PHONE=
BOOKING_SUPPORT_PHONE_2=
BOOKING_NOTIFY_EXTRA_EMAILS=

GOOGLE_CALENDAR_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=
```

---

## 9. Background Jobs & Scheduled Commands

### Queue jobs

| Job | Trigger | Action |
|---|---|---|
| `AddBookingToGoogleCalendar` | Booking confirmed | Creates Google Calendar event; stores `google_event_id` on booking |
| `RemoveBookingFromGoogleCalendar` | Booking cancelled or rejected | Removes the Calendar event using `google_event_id` |

Both jobs have `$tries = 3`. Google Calendar failures are caught — they do not block booking confirmation.

### Artisan commands

| Command | Schedule | Description |
|---|---|---|
| `bookings:alert-pending` | Daily at 08:00 | Emails admins about pending bookings whose earliest date is within 2 days |

Register in `routes/console.php`:
```php
Schedule::command('bookings:alert-pending')->dailyAt('08:00');
```

---

## 10. Email Notifications

| Mailable | Recipients | Trigger |
|---|---|---|
| `NewBookingReceived` | All admin users + `notify_extra_emails` | New booking submitted |
| `BookingConfirmed` | Booker (if email provided) | Admin confirms booking |
| `BookingCancelled` | Booker (if email provided) | Admin cancels booking |
| `BookingRejected` | Booker (if email provided) | Admin rejects booking |
| `ReceiptUploaded` | All admin users | Booker uploads receipt via public upload page |
| `PendingBookingAlert` | All admin users | Scheduled: bookings within 2 days still pending |

---

## 11. Deployment

Deployment targets the production server at `/home/zahirabo/zahira-booking` via SSH.

Steps run in a single SSH session:
1. `git pull` — fetch latest commits
2. `composer install --optimize-autoloader --no-dev` — install PHP dependencies
3. `php artisan migrate --force` — run any new migrations
4. `php artisan config:cache` — cache configuration
5. `php artisan route:cache` — cache routes
6. `php artisan view:cache` — cache Blade views
7. `npm run build` — compile frontend assets

The `/deploy` skill in Claude Code automates this via PuTTY `plink`.

---

*End of document.*
