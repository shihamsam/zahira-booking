# Zahira Bookings — Technical Documentation

> Generated: 2026-07-16  
> Stack: Laravel 11 · Vue 3 · Inertia.js · Tailwind CSS · MySQL

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture](#2-architecture)
3. [Current Implementation Summary](#3-current-implementation-summary)
4. [Gap Analysis](#4-gap-analysis)
5. [Implementation Plan](#5-implementation-plan)
6. [Data Model Reference](#6-data-model-reference)
7. [API / Route Reference](#7-api--route-reference)
8. [Configuration Reference](#8-configuration-reference)

---

## 1. System Overview

Zahira Bookings is a facility reservation system for Zahira College, Colombo. It manages bookings for two venues:

| Facility | Description |
|---|---|
| **Zahira Green** | College cricket / football ground. Time-slot based bookings (daytime flat rate or hourly nighttime with lighting options). |
| **Azwar Hall** | Indoor event hall. Per-day rent plus optional chairs and sound system. |

Public users browse availability, reserve slots, and submit payment receipts. Admins verify payments and confirm or reject bookings.

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Public Browser                     │
│  Vue 3 (Inertia SPA) + Tailwind CSS                 │
└────────────────────┬────────────────────────────────┘
                     │ HTTP / Inertia requests
┌────────────────────▼────────────────────────────────┐
│               Laravel 11 Backend                    │
│  Controllers · Services · Form Requests             │
│  Laravel Mail (queued) · Laravel Auth               │
└──────────────┬──────────────────┬───────────────────┘
               │                  │
        ┌──────▼──────┐   ┌───────▼───────┐
        │   MySQL DB  │   │  File Storage │
        │ (migrations)│   │ (receipts/)   │
        └─────────────┘   └───────────────┘
```

**Key dependencies**

| Package | Purpose |
|---|---|
| `laravel/framework` ^11 | PHP backend framework |
| `inertiajs/inertia-laravel` | Server-driven SPA bridge |
| `vue` ^3 | Frontend reactivity |
| `@inertiajs/vue3` | Vue adapter for Inertia |
| `tailwindcss` ^3 | Utility-first CSS |
| `vite` ^5 | Frontend build tool |

---

## 3. Current Implementation Summary

### 3.1 What Is Implemented

| Area | Status | Notes |
|---|---|---|
| Public ground listing (home page) | ✅ Done | Shows active resources with price/description |
| Public availability calendar | ✅ Done | Month-view, marks unavailable dates |
| Multi-day booking form | ✅ Done | Name, mobile, purpose; up to 31 dates |
| Booking confirmation page | ✅ Done | Reference no., bank details, WhatsApp prompt |
| Booking reference number generation | ✅ Done | Format: `ZGR-YYYYMMDD-XXXX` |
| Concurrency-safe booking creation | ✅ Done | Pessimistic DB locking prevents double-booking |
| Admin authentication | ✅ Done | Email + password, session-based |
| Admin dashboard (stats) | ✅ Done | Pending count, monthly confirmed, monthly income, totals |
| Admin booking list with filters | ✅ Done | Filter by status, resource, date range, search |
| Admin booking detail view | ✅ Done | Full booking info with dates, amounts |
| Admin receipt upload | ✅ Done | Image upload (max 5 MB) stored in `public/receipts/` |
| Admin confirm booking | ✅ Done | Requires receipt present; records who confirmed |
| Admin cancel booking | ✅ Done | With reason; records who cancelled |
| Email notification to admin on new booking | ✅ Done | Queued mailable to all admins + extra emails |
| Financial reports (date range) | ✅ Done | Period breakdown, resource breakdown |
| CSV export of confirmed bookings | ✅ Done | Streaming CSV download |
| Admin user management | ✅ Done | Create, list, delete (with self-delete guard) |
| Responsive layout | ✅ Done | Tailwind responsive utilities |

### 3.2 Seeded Defaults

| Item | Value |
|---|---|
| Default facility | Zahira Green Ground |
| Default price | Rs. 5,000 / day (flat) |
| Default admin | `admin@zahirags.lk` / `change-this-password` |

---

## 4. Gap Analysis

The following gaps were identified by comparing the implemented system against the Business Requirements document (`BusinessRequirements_ZzahiraBookings.pdf`).

### Priority Legend
- 🔴 **Critical** — Core feature explicitly specified; blocking completeness
- 🟡 **Medium** — Specified but the system partially works without it
- 🟢 **Minor** — Enhancement or edge-case handling

---

### GAP-01 🔴 — User Uploads Receipt at Booking Time

**Requirement (§2.1.1, §4.1.3):** User selects date/time → fills form → **uploads payment receipt image (JPG/PNG/PDF)** → submits.

**Current behaviour:** Receipt upload is an admin-only action performed after the booking is created. The public confirmation page directs the user to send their receipt manually via WhatsApp.

**Impact:** The booking workflow does not match the specified UX. Admins receive bookings with no receipt attached, requiring a manual out-of-band receipt collection step.

---

### GAP-02 🔴 — Time Slot Booking for Zahira Green

**Requirement (§2.1.1, §2.2):** Users must be able to select a **time slot**, not just a date. Three slot types exist:

| Slot | Lighting | Rate |
|---|---|---|
| 8:30 AM – 6:30 PM | Daytime | Rs. 6,000 flat |
| 6:30 PM – 6:30 AM | 4 Lights | Rs. 3,500 / hour |
| 6:30 PM – 6:30 AM | 2 Lights | Rs. 2,000 / hour |

**Current behaviour:** System only supports whole-day bookings at a single flat rate (`price_per_day`). No time slots, no lighting options, no hourly calculation.

**Impact:** All nighttime bookings are mis-priced. A 5-hour 4-light session should cost Rs. 17,500 but the flat-rate system cannot represent this.

---

### GAP-03 🔴 — Azwar Hall Module

**Requirement (§3):** A complete second facility, **Azwar Hall**, must be supported with its own booking flow and pricing.

| Item | Rate |
|---|---|
| Hall Rent | Rs. 10,000 per event/day |
| Chairs | Rs. 10 per chair |
| Sound system | Arranged on request |

**Current behaviour:** The `resources` table supports multiple facilities generically, but:
- No Azwar Hall record is seeded.
- No chair quantity field exists on the booking form.
- No sound system request field exists.
- Pricing model does not support per-item add-ons.

---

### GAP-04 🔴 — NIC Field in Booking Form

**Requirement (§4.1.2):** Booking form must capture **Name, Contact, NIC, Event Type, Date, Time**.

**Current behaviour:** Form captures `full_name`, `mobile_number`, `purpose`. NIC (National Identity Card) number is absent.

---

### GAP-05 🔴 — Booker Notification After Confirmation

**Requirement (§4.1.4):** "Auto email/WhatsApp confirmation after Admin approval."

**Current behaviour:** Only admins are notified (on new booking). The **booker receives no email or message when their booking is confirmed or cancelled**.

**Impact:** Bookers have no automated way to know their booking status changed. They must contact admins manually.

---

### GAP-06 🔴 — Google Calendar API Integration

**Requirement (§2.1.6, §3.1.4, §4.3.3):** Confirmed bookings must be **automatically added to Google Calendar**.

**Current behaviour:** No Google Calendar integration exists.

---

### GAP-07 🟡 — Admin Weekly Calendar View

**Requirement (§2.1.5):** Admin panel must have a **"View Weekly calendar"** in addition to the list view.

**Current behaviour:** Only a list/table view exists for admin bookings. No calendar visualisation.

---

### GAP-08 🟡 — Block Dates / Holidays

**Requirement (§4.2.4):** Admin must be able to **manage pricing and block dates/holidays**.

**Current behaviour:** No blocked-dates mechanism. Any date can be booked unless another booking already occupies it.

---

### GAP-09 🟡 — Admin Pricing Management UI

**Requirement (§4.2.4):** Admin should be able to **manage pricing** through the panel.

**Current behaviour:** Prices are set at seeder time and stored as `price_per_day` on the `resources` table. There is no admin UI to change prices.

---

### GAP-10 🟡 — WhatsApp Notification to Admin

**Requirement (§2.1.6):** "Email / **WhatsApp** notification to Admin on every new booking."

**Current behaviour:** Email notification is implemented. WhatsApp is handled manually — the confirmation page shows a WhatsApp number for the user to message the admin, but no automated WhatsApp message is sent to the admin.

---

### GAP-11 🟡 — Report Export: Weekly Preset + PDF/Excel Format

**Requirement (§4.2.5):** "Export reports: **Weekly**, Monthly." Azwar Hall also requires **Excel/PDF** export (§3.1.3).

**Current behaviour:** Reports support monthly/quarterly/yearly presets and custom ranges. Export is CSV only. No weekly preset; no PDF or Excel format.

---

### GAP-12 🟡 — Dashboard: "Today's Bookings" Widget

**Requirement (§4.2.2):** Dashboard should show "Today's bookings, Pending payments, Revenue."

**Current behaviour:** Dashboard shows this-month stats and upcoming bookings. There is no explicit "today's bookings" section.

---

### GAP-13 🟢 — Explicit "Rejected" Booking Status

**Requirement (§2.1.5, §4.2.3):** Admin actions listed as "Approve, **Reject**, Cancel." Reject (failed payment verification) is semantically different from Cancel (admin-initiated removal).

**Current behaviour:** Only `pending`, `confirmed`, `cancelled` statuses exist. "Reject" is currently handled as a cancel.

---

### GAP-14 🟢 — 2-Day Cancellation Rule Enforcement

**Requirement (§2.1.3):** Admin should cancel pending slots if payment is not confirmed **2 days before** the booking date.

**Current behaviour:** Admin can cancel any time with no enforcement or automated alert for this rule.

---

### Gap Summary Table

| ID | Description | Priority | Area |
|---|---|---|---|
| GAP-01 | User uploads receipt at booking time | 🔴 Critical | Public booking flow |
| GAP-02 | Time slot + lighting options for Zahira Green | 🔴 Critical | Pricing / booking model |
| GAP-03 | Azwar Hall module (full) | 🔴 Critical | New module |
| GAP-04 | NIC field in booking form | 🔴 Critical | Booking form |
| GAP-05 | Booker confirmation/cancellation email | 🔴 Critical | Notifications |
| GAP-06 | Google Calendar API integration | 🔴 Critical | Integrations |
| GAP-07 | Admin weekly calendar view | 🟡 Medium | Admin UI |
| GAP-08 | Block dates / holidays | 🟡 Medium | Admin management |
| GAP-09 | Pricing management UI | 🟡 Medium | Admin management |
| GAP-10 | WhatsApp notification to admin | 🟡 Medium | Notifications |
| GAP-11 | Weekly report preset + PDF/Excel export | 🟡 Medium | Reports |
| GAP-12 | Dashboard "today's bookings" widget | 🟡 Medium | Admin UI |
| GAP-13 | Explicit "rejected" booking status | 🟢 Minor | Booking model |
| GAP-14 | 2-day cancellation rule enforcement | 🟢 Minor | Business rules |

---

## 5. Implementation Plan

The plan is organised into **5 phases** ordered by dependency and business priority.

---

### Phase 1 — Booking Form & Data Model Fixes
*Addresses: GAP-01, GAP-02, GAP-03, GAP-04, GAP-13*

**Estimated effort:** 3–4 days

#### Step 1.1 — Add NIC and booking type fields to the bookings table

Create migration:
```
php artisan make:migration add_nic_and_slot_to_bookings_table
```
Add columns:
- `nic` — `string(20)`, nullable initially (to be required going forward)
- `slot_type` — enum `('full_day', 'daytime', 'night_4lights', 'night_2lights')`, nullable
- `start_time` — `time`, nullable
- `end_time` — `time`, nullable
- `hours` — `unsignedSmallInteger`, nullable (for hourly slots)

Update `StoreBookingRequest` validation rules to require `nic` and `slot_type` for Zahira Green.

#### Step 1.2 — Implement time-slot pricing engine

Create `app/Services/PricingService.php`:
```php
class PricingService
{
    public function calculate(Resource $resource, string $slotType, int $hours = 0): float;
    public function slots(Resource $resource): array; // returns available slot options
}
```
Pricing rules stored in `config/booking.php` under a `pricing` key per facility slug:
```php
'pricing' => [
    'zahira-green' => [
        'daytime'       => ['type' => 'flat',   'rate' => 6000],
        'night_4lights' => ['type' => 'hourly',  'rate' => 3500],
        'night_2lights' => ['type' => 'hourly',  'rate' => 2000],
    ],
]
```

#### Step 1.3 — Update public booking form (ResourceShow.vue)

- Add slot type selector (radio buttons: Daytime / Night 4 Lights / Night 2 Lights).
- Show time range picker (start/end time) when a nighttime slot is selected.
- Show NIC input field.
- Compute total dynamically in Vue based on slot type + hours.

#### Step 1.4 — User uploads receipt at booking time

- Add `receipt_file` field to `StoreBookingRequest` (required, `mimes:jpg,jpeg,png,pdf`, `max:5120`).
- In `BookingService::createBooking()`, accept and store the uploaded file to `storage/app/public/receipts/`.
- Save path to `bookings.receipt_path` on creation (not on admin upload).
- Update `ResourceShow.vue` to include a file upload input on the booking form.
- Update `BookingConfirmation.vue` to confirm receipt was received rather than directing user to WhatsApp.
- Keep admin's ability to replace the receipt if the uploaded one is invalid.

#### Step 1.5 — Add "rejected" booking status

- Add `rejected` to the `status` enum on `bookings` table via migration.
- Add `rejected_by`, `rejected_at`, `rejection_reason` columns (same pattern as `cancelled_*`).
- Add `Admin/BookingController@reject` action with its own route.
- Update `StatusBadge.vue` with a red/orange colour for `rejected`.
- Update all filter dropdowns to include `rejected`.

#### Step 1.6 — Seed Azwar Hall resource

Create `AzwarHallSeeder`:
- Name: "Azwar Hall"
- Slug: "azwar-hall"
- Price: Rs. 10,000 / day (base)
- Description and location fields

Add Azwar Hall pricing to `config/booking.php`:
```php
'azwar-hall' => [
    'hall_rent' => 10000,  // per event/day
    'chair_rate' => 10,    // per chair
],
```

#### Step 1.7 — Add Azwar Hall add-ons to booking form

- Add `chair_count` — `unsignedSmallInteger`, nullable to `bookings` table migration.
- Add `sound_system_requested` — `boolean`, default false.
- In `ResourceShow.vue`, show chair count input and sound system checkbox conditionally when the resource is Azwar Hall (check `resource.slug === 'azwar-hall'`).
- In `PricingService`, include `chair_count * chair_rate` in the Azwar Hall total.

---

### Phase 2 — Notifications (Booker-side)
*Addresses: GAP-05, GAP-10*

**Estimated effort:** 1–2 days

#### Step 2.1 — Collect booker email address

- Add `email` — `string(255)`, nullable to `bookings` table.
- Add `email` field to the public booking form and `StoreBookingRequest` (optional but encouraged).

#### Step 2.2 — Send confirmation email to booker

Create `app/Mail/BookingConfirmed.php`:
- Triggered from `Admin/BookingController@confirm`.
- Recipient: `booking->email` (skip if null).
- Content: reference no., facility, dates, total amount, a "your booking is confirmed" message.

Create `app/Mail/BookingCancelled.php` / `BookingRejected.php`:
- Same trigger pattern from the cancel/reject actions.
- Content: reason provided by admin.

Blade templates in `resources/views/emails/`.

#### Step 2.3 — WhatsApp notification to admin via Twilio or Meta API (optional integration point)

- Add `WHATSAPP_PROVIDER` env variable (e.g., `twilio`).
- Create `app/Services/WhatsAppService.php` with a `sendToAdmin(Booking $booking): void` method.
- Call from `BookingService::notifyAdmins()` alongside the existing email.
- Implementation can be deferred; the service class should be stubbed and logged if credentials are absent.

---

### Phase 3 — Google Calendar Integration
*Addresses: GAP-06*

**Estimated effort:** 1–2 days

#### Step 3.1 — Install Google API PHP client

```
composer require google/apiclient
```

#### Step 3.2 — Add Google OAuth credentials to config

Add to `config/services.php`:
```php
'google_calendar' => [
    'calendar_id'   => env('GOOGLE_CALENDAR_ID'),
    'credentials'   => env('GOOGLE_SERVICE_ACCOUNT_JSON'), // path to service account JSON
],
```

#### Step 3.3 — Create GoogleCalendarService

Create `app/Services/GoogleCalendarService.php`:
```php
class GoogleCalendarService
{
    public function addEvent(Booking $booking): string; // returns Google event ID
    public function removeEvent(string $googleEventId): void;
}
```
- `addEvent` constructs a Google Calendar `Event` with title: `[FACILITY] — {reference_no}`, description: purpose, date(s) from `BookingDate` records.
- Store the returned Google event ID in a new `google_event_id` column on `bookings`.

#### Step 3.4 — Hook into booking confirm/cancel actions

- In `Admin/BookingController@confirm`: call `GoogleCalendarService::addEvent()` after status change.
- In `Admin/BookingController@cancel` and `@reject`: call `GoogleCalendarService::removeEvent()` if `google_event_id` is set.
- Wrap in try/catch — Google Calendar failure must not block booking confirmation.

---

### Phase 4 — Admin Panel Enhancements
*Addresses: GAP-07, GAP-08, GAP-09, GAP-11, GAP-12*

**Estimated effort:** 3–4 days

#### Step 4.1 — Admin weekly calendar view

Create `Pages/Admin/Calendar/Index.vue`:
- Week grid (Mon–Sun columns, time rows for slot-based facilities).
- Fetch bookings for the selected week via a new API endpoint:
  `GET /admin/calendar?from=YYYY-MM-DD&to=YYYY-MM-DD`
- Color-code by status: pending = yellow, confirmed = green, rejected/cancelled = grey.
- Clicking a booking cell navigates to the booking detail page.

Add route: `GET /admin/calendar` → `Admin/CalendarController@index`.

#### Step 4.2 — Block dates / holidays

Create migration for `blocked_dates` table:
```sql
id, resource_id (FK, nullable — null = all resources), date (date), reason (string), created_by (FK users), timestamps
```
Create `Admin/BlockedDateController` with `index`, `store`, `destroy`.
Add routes under `/admin/blocked-dates`.
Create `Pages/Admin/BlockedDates/Index.vue` — simple list + date picker form.
Update `BookingService::unavailableDates()` to include blocked dates.
Update `StoreBookingRequest` to reject dates that are blocked.

#### Step 4.3 — Pricing management UI

Add `pricing_overrides` — JSON column to `resources` table (stores slot-specific overrides).
Create `Admin/ResourceController` with:
- `edit(Resource)` — shows a form with current pricing.
- `update(Resource)` — saves new prices.
Add routes: `GET /admin/resources/{resource}/edit`, `PUT /admin/resources/{resource}`.
Create `Pages/Admin/Resources/Edit.vue`.
Update `PricingService` to read from `resource->pricing_overrides` with fallback to `config/booking.php`.

#### Step 4.4 — Dashboard "today's bookings" widget

Update `Admin/DashboardController@index` to additionally pass:
- `todayBookings` — bookings with at least one `BookingDate` equal to today.
- `todayRevenue` — sum of confirmed bookings with today's dates.

Update `Pages/Admin/Dashboard.vue` to render a "Today" section above the existing stats.

#### Step 4.5 — Weekly report preset + PDF/Excel export

Add weekly preset button to `Pages/Admin/Reports/Index.vue` (sets `from` = start of current week, `to` = end of current week).

Add PDF export using `barryvdh/laravel-dompdf`:
```
composer require barryvdh/laravel-dompdf
```
Add route: `GET /admin/reports/export/pdf` → streams a PDF invoice-style report.

Add Excel export using `maatwebsite/excel`:
```
composer require maatwebsite/excel
```
Add route: `GET /admin/reports/export/excel` → streams XLSX file.

---

### Phase 5 — Business Rule Enforcement
*Addresses: GAP-14*

**Estimated effort:** 0.5 day

#### Step 5.1 — Automated 2-day pending alert

Create Laravel scheduled command `app/Console/Commands/AlertOverduePendingBookings.php`:
- Finds bookings where `status = pending` AND the earliest `BookingDate.date` is exactly 2 days from now.
- Sends an email to all admins listing these bookings with a "confirm or cancel" reminder.

Register in `routes/console.php`:
```php
Schedule::command('bookings:alert-pending')->dailyAt('08:00');
```

---

### Phase Completion Checklist

| Phase | Key Deliverables | Gaps Closed |
|---|---|---|
| Phase 1 | NIC field, time slots, Azwar Hall, user receipt upload, rejected status | GAP-01, 02, 03, 04, 13 |
| Phase 2 | Booker confirmation emails, WhatsApp stub | GAP-05, 10 |
| Phase 3 | Google Calendar auto-sync | GAP-06 |
| Phase 4 | Weekly calendar view, blocked dates, pricing UI, today's widget, weekly/PDF/Excel exports | GAP-07, 08, 09, 11, 12 |
| Phase 5 | 2-day pending booking alert command | GAP-14 |

---

## 6. Data Model Reference

### Current Schema

```
users
  id, name, email, password, remember_token, timestamps

resources
  id, name, slug (unique), description, location, image_path,
  price_per_day (decimal 10,2), is_active (bool), timestamps

bookings
  id, reference_no (unique), resource_id (FK),
  full_name, mobile_number, purpose,
  total_amount (decimal 10,2),
  status (enum: pending|confirmed|cancelled),
  receipt_path,
  admin_notes,
  confirmed_by (FK users), confirmed_at,
  cancelled_by (FK users), cancelled_at, cancellation_reason,
  timestamps

booking_dates
  id, booking_id (FK), resource_id (FK), date (date),
  unit_price (decimal 10,2), timestamps
  INDEX (resource_id, date)
```

### Planned Schema Additions (Phase 1)

```
bookings — new columns:
  nic (string 20)
  email (string 255, nullable)
  slot_type (enum: full_day|daytime|night_4lights|night_2lights, nullable)
  start_time (time, nullable)
  end_time (time, nullable)
  hours (smallint unsigned, nullable)
  chair_count (smallint unsigned, nullable)
  sound_system_requested (bool, default false)
  rejected_by (FK users, nullable)
  rejected_at (timestamp, nullable)
  rejection_reason (text, nullable)
  google_event_id (string, nullable)

resources — new columns:
  pricing_overrides (json, nullable)

blocked_dates (new table)
  id, resource_id (FK, nullable), date (date),
  reason (string 255), created_by (FK users), timestamps
```

---

## 7. API / Route Reference

### Current Routes

| Method | URI | Controller | Notes |
|---|---|---|---|
| GET | `/` | `Public/HomeController@index` | Public |
| GET | `/grounds/{slug}` | `Public/BookingController@show` | Public |
| GET | `/grounds/{slug}/availability` | `Public/BookingController@availability` | JSON |
| POST | `/grounds/{slug}/bookings` | `Public/BookingController@store` | Public |
| GET | `/bookings/{ref}/confirmation` | `Public/BookingController@confirmation` | Public |
| GET | `/admin/login` | `Auth/AuthenticatedSessionController@create` | Guest |
| POST | `/admin/login` | `Auth/AuthenticatedSessionController@store` | Guest |
| POST | `/admin/logout` | `Auth/AuthenticatedSessionController@destroy` | Auth |
| GET | `/admin/dashboard` | `Admin/DashboardController@index` | Auth |
| GET | `/admin/bookings` | `Admin/BookingController@index` | Auth |
| GET | `/admin/bookings/{booking}` | `Admin/BookingController@show` | Auth |
| POST | `/admin/bookings/{booking}/receipt` | `Admin/BookingController@uploadReceipt` | Auth |
| POST | `/admin/bookings/{booking}/confirm` | `Admin/BookingController@confirm` | Auth |
| POST | `/admin/bookings/{booking}/cancel` | `Admin/BookingController@cancel` | Auth |
| GET | `/admin/reports` | `Admin/ReportController@index` | Auth |
| GET | `/admin/reports/export` | `Admin/ReportController@export` | Auth |
| GET | `/admin/admins` | `Admin/AdminUserController@index` | Auth |
| POST | `/admin/admins` | `Admin/AdminUserController@store` | Auth |
| DELETE | `/admin/admins/{user}` | `Admin/AdminUserController@destroy` | Auth |

### Planned New Routes (Phases 1–4)

| Method | URI | Controller | Phase |
|---|---|---|---|
| POST | `/admin/bookings/{booking}/reject` | `Admin/BookingController@reject` | 1 |
| GET | `/admin/calendar` | `Admin/CalendarController@index` | 4 |
| GET | `/admin/blocked-dates` | `Admin/BlockedDateController@index` | 4 |
| POST | `/admin/blocked-dates` | `Admin/BlockedDateController@store` | 4 |
| DELETE | `/admin/blocked-dates/{id}` | `Admin/BlockedDateController@destroy` | 4 |
| GET | `/admin/resources/{resource}/edit` | `Admin/ResourceController@edit` | 4 |
| PUT | `/admin/resources/{resource}` | `Admin/ResourceController@update` | 4 |
| GET | `/admin/reports/export/pdf` | `Admin/ReportController@exportPdf` | 4 |
| GET | `/admin/reports/export/excel` | `Admin/ReportController@exportExcel` | 4 |

---

## 8. Configuration Reference

### `config/booking.php`

| Key | Env Variable | Default | Description |
|---|---|---|---|
| `notify_extra_emails` | `BOOKING_NOTIFY_EXTRA_EMAILS` | `[]` | Comma-separated extra email recipients |
| `bank.bank_name` | — | Configured in file | Bank name shown on confirmation |
| `bank.account_name` | — | — | Account holder name |
| `bank.account_number` | — | — | Bank account number |
| `bank.branch` | — | — | Branch name |
| `whatsapp_number` | — | — | Admin WhatsApp for manual receipt follow-up |
| `booking_window_months` | — | `3` | How many months ahead users can book |

### Planned additions to `config/booking.php`

```php
'pricing' => [
    'zahira-green' => [
        'daytime'       => ['type' => 'flat',   'rate' => 6000],
        'night_4lights' => ['type' => 'hourly',  'rate' => 3500],
        'night_2lights' => ['type' => 'hourly',  'rate' => 2000],
    ],
    'azwar-hall' => [
        'hall_rent'  => 10000,
        'chair_rate' => 10,
    ],
],
```

### Required Environment Variables

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key |
| `DB_*` | MySQL connection settings |
| `MAIL_*` | SMTP credentials |
| `BOOKING_NOTIFY_EXTRA_EMAILS` | Comma-separated extra admin emails |
| `GOOGLE_CALENDAR_ID` | Google Calendar ID for sync (Phase 3) |
| `GOOGLE_SERVICE_ACCOUNT_JSON` | Path to Google service account credentials JSON (Phase 3) |
| `TWILIO_*` | Twilio credentials for WhatsApp (Phase 2, optional) |

---

*End of document.*
