# Zahira Bookings — Security Concerns & Workflow Gaps

> Last updated: 2026-07-24

This document catalogues known security concerns and workflow gaps in the current implementation. Items are ranked by severity.

---

## Security Concerns

### SEC-01 🔴 — Receipt Files Are Publicly Accessible

**Issue:** Uploaded receipts are stored in `storage/app/public/receipts/` and served via `/storage/receipts/...`. Any person who knows or guesses a file path can download another booker's payment receipt, which may contain bank account details.

**Risk:** Privacy breach — booker financial information exposed to anyone with the URL.

**Mitigation options:**
- Move receipts to a private disk (`local` storage, not `public`) and serve them through a controller that authenticates the request.
- Admin receipt access: `GET /admin/receipts/{filename}` with `auth` middleware.
- Public receipt access (booker uploading their own): require the booking reference number as an access token.

---

### SEC-02 🔴 — No Rate Limiting on Public Booking Endpoint

**Issue:** `POST /facilities/{slug}/bookings` has no rate limit. A malicious actor can automate rapid booking submissions, exhausting slot availability or flooding admin inboxes with notification emails.

**Risk:** Denial of service on slot availability; email flood.

**Mitigation:** Apply Laravel's `throttle` middleware to public booking and receipt upload routes:
```php
Route::post('/facilities/{slug}/bookings', ...)->middleware('throttle:10,1'); // 10 per minute
```

---

### SEC-03 🟡 — Booking Confirmation Page Is Publicly Accessible by Reference Number

**Issue:** `/bookings/{ref}/confirmation` is a public route. Anyone who knows or guesses a reference number can view another booker's name, mobile number, email, and booking details.

**Risk:** Privacy breach — personal information of other users exposed.

**Mitigation options:**
- Require the booker's mobile number as an additional verification parameter.
- Add a short-lived signed URL on the confirmation link.
- At minimum, do not display the booker's full mobile number on this public page.

---

### SEC-04 🟡 — No Admin Login Brute-Force Protection

**Issue:** `POST /admin/login` has no account lockout or rate limiting. An attacker can attempt unlimited password combinations.

**Risk:** Admin account compromise via brute-force.

**Mitigation:**
- Add `throttle:5,1` middleware to the login route (5 attempts per minute).
- Consider Laravel Fortify's lockout feature or a custom `LoginAttempts` table.

---

### SEC-05 🟡 — No Password Reset Flow for Admins

**Issue:** If an admin forgets their password, the only recovery path is a Super Admin manually resetting it through the user management page or directly in the database.

**Risk:** Admins locked out permanently if no Super Admin is available.

**Mitigation:** Implement a standard `forgot password` → email reset link flow using Laravel's built-in `Password::sendResetLink()`.

---

### SEC-06 🟡 — Google Service Account Credentials Path in `.env`

**Issue:** `GOOGLE_SERVICE_ACCOUNT_JSON` points to a file containing a Google service account private key. If this file is accidentally committed to git, or if the `.env` file is exposed, an attacker gains full write access to the linked Google Calendar.

**Risk:** Unauthorized Google Calendar modification.

**Mitigation:**
- Ensure the credentials file path is outside the web root and project directory (e.g. `/etc/zahira/google-credentials.json`).
- Add the credentials file path pattern to `.gitignore`.
- Rotate the service account key immediately if exposure is suspected.

---

### SEC-07 🟢 — NIC Field Is Optional (Nullable)

**Issue:** The National Identity Card field was made nullable to allow the booking flow to proceed without it. This means bookings can be submitted with no identity verification.

**Risk:** Anonymous bookings — difficult to hold bookers accountable for damage or no-shows.

**Mitigation:** Consider making NIC required at form validation level while keeping the DB column nullable for legacy/compatibility reasons. Alternatively, allow admin to mark a booking as "NIC verified" separately.

---

### SEC-08 🟢 — Reference Numbers Are Partially Predictable

**Issue:** Reference numbers follow the format `{SHORTCODE}-{YYYYMMDD}-{4 random chars}`. The date portion is the booking date, narrowing the brute-force space to ~1.6 million combinations for a known day.

**Risk:** With enough attempts, an attacker could enumerate valid reference numbers and access other bookings' confirmation pages (amplified by SEC-03).

**Mitigation:** Increase random portion to 6 characters, or combine with the mobile-number verification fix in SEC-03 to make enumeration practically useless even with a known reference.

---

### SEC-09 🟢 — No Two-Factor Authentication for Admins

**Issue:** Admin login relies solely on email + password. A compromised password grants full access to all booking data and admin actions.

**Risk:** Full admin account takeover with a single leaked credential.

**Mitigation:** Add TOTP-based 2FA (e.g. `pragmarx/google2fa-laravel`) as an optional or mandatory step for admin users.

---

### SEC-10 🟢 — File Upload Type Validation Could Be Strengthened

**Issue:** Receipt uploads validate `mimes:jpg,jpeg,png,pdf` (MIME type from extension sniffing). PHP's `mimes` validation reads the file extension, not the actual file magic bytes.

**Risk:** Crafted file with a `.jpg` extension but malicious content (e.g. a PHP script) could potentially bypass extension-based checks.

**Mitigation:** Use `mimetypes:image/jpeg,image/png,application/pdf` which reads the actual file content, plus ensure the storage driver is set to private so uploaded files are never executed as PHP.

---

## Workflow Gaps

### WF-01 🔴 — Payment Deadline Is Not Enforced Server-Side

**Issue:** The booking review modal shows a "pay by {now + 3 hours}" deadline to the user, but this is purely cosmetic. The system does not automatically cancel bookings after the deadline passes.

**Impact:** Slots remain held in "pending" state indefinitely, blocking other users from booking the same slot. Admins must manually cancel overdue bookings.

**Recommendation:** The existing `bookings:alert-pending` command alerts admins 2 days before the event date, but does not auto-cancel. Add an option to auto-cancel pending bookings older than a configurable number of hours (e.g. `BOOKING_PAYMENT_DEADLINE_HOURS=3`).

---

### WF-02 🔴 — Booker Cannot Cancel Their Own Booking

**Issue:** There is no public-facing cancellation mechanism. If a booker needs to cancel, they must contact an admin via phone or WhatsApp.

**Impact:** Admin time wasted on manual cancellations; poor booker experience.

**Recommendation:** Add a cancellation endpoint accessible via the booking reference number + mobile verification:
- `GET /bookings/{ref}/cancel` — confirm-cancel form.
- `POST /bookings/{ref}/cancel` — execute cancellation (within a configurable cutoff window, e.g. 48 hours before the event).

---

### WF-03 🟡 — No Booker Email Notification on Receipt Acknowledgement

**Issue:** When an admin confirms a booking, the booker receives a confirmation email. But there is no notification when an admin simply views or acknowledges the uploaded receipt without yet confirming.

**Impact:** Bookers have no signal that their receipt was received and is under review. They may call admins to check status.

**Recommendation:** Consider a lightweight "receipt received" automated reply to the booker's email when `ReceiptUploaded` mail is sent to admins.

---

### WF-04 🟡 — Google Calendar Sync Is One-Way

**Issue:** When bookings are confirmed or cancelled, the Google Calendar is updated. However, if someone modifies or deletes an event directly in Google Calendar, the booking system is unaware.

**Impact:** Calendar and booking system can fall out of sync. Admins may see different information in Google Calendar vs the booking panel.

**Recommendation:** This is an inherent limitation of the current architecture. Document for admins that Google Calendar events should not be edited directly — all changes must go through the booking panel.

---

### WF-05 🟡 — No Booker Calendar Invite

**Issue:** When a booking is confirmed, the admin's Google Calendar is updated, but the booker does not receive a calendar invite or iCal attachment.

**Impact:** Bookers may miss their booking date or have to manually add it to their own calendar.

**Recommendation:** Attach an iCal (`.ics`) file to the `BookingConfirmed` email so bookers can add the event to any calendar application with one click.

---

### WF-06 🟡 — Admin Cannot Modify a Booking

**Issue:** There is no edit booking functionality in the admin panel. If a booker calls to change their date or slot, an admin must cancel the existing booking and ask the booker to re-book.

**Impact:** Friction for both admins and bookers when minor corrections are needed.

**Recommendation:** Add a restricted edit form for admins covering: date(s), slot type, admin notes. Financial amounts should not be editable post-submission without explicit justification.

---

### WF-07 🟢 — No Waitlist or Availability Notification

**Issue:** If a desired slot is booked, users have no way to be notified if it becomes available (e.g. if a pending booking is cancelled).

**Impact:** Interested users must manually check availability periodically.

**Recommendation:** A simple "notify me" form — email + reference to desired date/slot — with an automated email sent when that slot opens up.

---

### WF-08 🟢 — Pricing Change Does Not Update Existing Pending Bookings

**Issue:** If an admin updates a facility's pricing via the Resources page, existing pending bookings retain the price calculated at submission time. This is usually correct behaviour, but can surprise admins who expect a price change to apply universally.

**Impact:** Price displayed on the booking detail may differ from the new configured price with no visual indicator.

**Recommendation:** Document this behaviour clearly in admin training materials. Optionally add a visual indicator on the booking detail when the booking's `total_amount` differs from the current configured rate.

---

### WF-09 🟢 — No Audit Log for Admin Actions

**Issue:** Confirm, cancel, and reject actions record `confirmed_by`, `cancelled_by`, and `rejected_by` user IDs and timestamps on the booking itself. But there is no general audit trail — no log of which admin viewed what, or of failed login attempts.

**Impact:** Difficult to investigate disputes or irregularities after the fact.

**Recommendation:** For immediate improvement, log all booking status changes to a dedicated `booking_activity_log` table (booking_id, admin_id, action, old_status, new_status, note, timestamp). Full audit logging of page views is optional and adds overhead.

---

*End of document.*
