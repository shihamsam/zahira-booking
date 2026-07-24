# Zahira Bookings — Feature Overview

> Last updated: 2026-07-24

This document describes what the system currently does from the perspective of the two primary audiences: **the public user** who wants to book a facility, and the **admin user** who manages bookings.

---

## Public User Experience

### 1. Home Page

The home page presents a clean two-column landing layout. On the left, the Zahira College logos and branding are displayed prominently. On the right, users complete a short form before proceeding to book.

**What users do here:**
- Enter their **full name** and **mobile number** (Sri Lankan format, 10 digits starting with 0).
- Optionally enter an **email address** (used to receive booking status emails).
- Select a **facility** — Zahira Green or Azwar Hall — shown as clickable cards.
- Tap "Choose Your Time Slot" to proceed to the slot selection page.

**Support contact:** Two phone numbers are displayed with click-to-call links ("Call us for support").

**Receipt upload shortcut:** A styled card links directly to the receipt upload page for returning users who already have a booking reference.

---

### 2. Time Slot Booking Page

After selecting a facility and entering their details, users land on a two-panel viewport-contained booking page.

**Left panel — Calendar:**
- Month-view calendar showing unavailable dates (fully booked or blocked by admin).
- Single-date selection only.

**Right panel — Slot grid:**

For **Zahira Green**:
- One "Day Time" tile (6:00 AM – 6:00 PM, flat rate).
- 12 individual hour tiles from 6:00 PM through to 6:00 AM the following day.
- Each hour tile shows its real-time availability (greyed out if already booked).
- Users can pick the daytime tile OR any combination of individual night-hour tiles.
- The pricing updates live as hours are selected.

For **Azwar Hall**:
- "Full Day" tile with flat pricing.

**Pre-submission review modal:**
- Shows a full summary of the booking (facility, date, slots, total amount).
- Displays a payment deadline (3 hours from the moment of review).
- Includes booking terms — payment, cancellation policy.
- Requires the user to tick "I agree to the terms" before submitting.

---

### 3. Booking Confirmation Page

After a successful submission, users see a two-panel confirmation page.

**Left panel — Booking details:**
- Facility name, booking date(s), selected slot(s).
- Night slots are listed individually (e.g. "8:00 PM – 9:00 PM").
- Booking status badge (Pending at this point).

**Right panel — Payment instructions:**
- **Reference number** displayed prominently with a one-click copy-to-clipboard button.
- Bank deposit details (bank name, account number, branch).
- Instructions to upload the receipt after payment.
- **WhatsApp button** — pre-fills a message to the admin WhatsApp number with the reference, facility, date, and slot details. Bold formatting preserved.

---

### 4. Receipt Upload Page

Users can return later with their reference number to upload their payment receipt.

- Lookup form: enter reference number to find the booking.
- Once found: left panel shows booking summary; right panel shows the upload card.
- Users upload a JPG, PNG, or PDF receipt (max 5 MB).
- On success, a centered modal overlay confirms the upload with a success message.

---

### 5. Facility Detail / Availability Page

A standalone page for each facility (`/facilities/{slug}`) shows:
- Facility description and an availability calendar.
- A legacy booking form (full slot selection, multi-date capable).

This page is accessible directly but is no longer the primary booking flow (replaced by the Home → Timeslot flow).

---

## Admin User Experience

Admins log in at `/admin/login` using email and password. The admin panel uses a fixed sidebar layout — the sidebar is always visible, and the content area scrolls independently.

---

### 1. Dashboard

The dashboard gives a real-time snapshot of the system at a glance.

| Metric | Description |
|---|---|
| Pending bookings | Count of bookings awaiting confirmation |
| Confirmed this month | Count of bookings confirmed in the current calendar month |
| Income this month | Sum of confirmed booking amounts in the current month (LKR) |
| Total bookings | All-time booking count |
| Today's bookings | Count of active bookings whose dates include today |
| Today's income | Sum of confirmed bookings touching today |

Below the stats:
- **Today's bookings** — list of all bookings with dates today.
- **Upcoming bookings** — most recent 8 upcoming active bookings.
- **Recent pending** — most recent 8 bookings awaiting action.

---

### 2. Bookings List

A searchable, filterable table of all bookings.

**Filters:** status (pending / confirmed / cancelled / rejected), facility, date range, free-text search.

**Columns:** Reference, Facility, Booker name, Phone, Date & Slot, Amount, Status.

The **Date & Slot** column shows each slot as a pill — daytime as "6:00 AM – 6:00 PM", night hours as individual "H:00 PM – H:00 PM" pills, and "Full Day" for Azwar Hall.

---

### 3. Booking Detail

Full detail view for a single booking.

**Information shown:**
- Booker name, mobile, NIC (if provided), email.
- Facility, reference number, slot type.
- Dates with individual slot labels.
- Total amount.
- Status with timestamps (confirmed/cancelled/rejected with who and when).
- Receipt image/file preview.
- Admin notes field.

**Admin actions:**
- **Confirm** — marks booking as confirmed; triggers booker email + Google Calendar event creation.
- **Reject** — marks as rejected with a mandatory reason; triggers booker email + removes Google Calendar event.
- **Cancel** — marks as cancelled with a mandatory reason; triggers booker email + removes Google Calendar event.
- **Upload receipt** — admin can attach/replace a receipt file.

---

### 4. Calendar View

A time-grid calendar showing all active bookings for a selected month.

- **"Day Time" row** — shows daytime and full_day bookings as tiles.
- **12 hourly rows** (6 PM → 6 AM) — each row represents one hour; shows which facility is booked.
- Tiles display the facility shortcode (ZGG or AZW) for quick scanning.
- Midnight-and-after rows (12 AM – 6 AM) are visually tinted to indicate the next-day hours.
- Color-coded by booking status: pending, confirmed, etc.

---

### 5. Blocked Dates

Admins can prevent bookings from being made on specific dates.

- Select one or more dates using a calendar picker.
- Optionally scope to a specific facility (or apply to all facilities).
- Optionally add a reason (e.g. "College event", "Maintenance").
- **Guard:** dates with active (pending or confirmed) bookings cannot be blocked — the system rejects the request with the conflicting dates listed.
- Existing blocks can be removed individually.

---

### 6. Facilities (Resources)

Admins can view and update pricing overrides for each facility.

- Current pricing displayed alongside the config defaults.
- Admins can set custom rates per slot type without changing code or config files.
- Price overrides are stored as a JSON column on the resource record and take precedence over the config file defaults.

---

### 7. Reports

Financial reporting for confirmed bookings.

**Filters:**
- Date range (custom from/to).
- Preset: Monthly, Quarterly, Yearly, Weekly.
- Facility filter.

**Output:**
- Summary — total income and total bookings for the period.
- Period breakdown table — income and bookings per month/quarter/year/week.
- Per-facility breakdown — income and bookings per facility.

**Export:**
- CSV — opens correctly in Excel (UTF-8 BOM included).
- Excel — SpreadsheetML format, no extra package required.

---

### 8. Admin User Management (Super Admin only)

Only accessible to users with the `super_admin` role.

- **List** all admin users with their role badges.
- **Add** a new admin user (name, email, password).
- **Remove** an admin user (cannot remove a `super_admin`; cannot remove yourself).

---

### 9. My Account / Profile

Every admin user can:
- View their name, email address, and role.
- Change their password (requires current password verification).
- Success and error messages are displayed inline.

The sidebar shows a "Hello, {FirstName}" greeting link (in gold/amber color) above the navigation items, linking to this page.

---

## Role Hierarchy

| Role | Capabilities |
|---|---|
| `admin` | Full access to bookings, calendar, blocked dates, facilities, reports, profile |
| `super_admin` | All admin capabilities + user management (add/remove admins) |

The first user created in the database is automatically promoted to `super_admin` via the migration seed.

---

*End of document.*
