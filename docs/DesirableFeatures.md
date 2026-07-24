# Zahira Bookings — Desirable Future Features

> Last updated: 2026-07-24

This document lists features that would meaningfully improve the system for all stakeholders — bookers, admins, and the college. Items are grouped by stakeholder impact and ordered within each group by effort-to-value ratio.

---

## For Bookers (Public Users)

### DF-01 — Self-Service Booking Cancellation
Allow bookers to cancel their own bookings via a reference number + mobile number verification, within a configurable cancellation window (e.g. up to 48 hours before the event date). This removes the need to contact an admin for every cancellation.

**Effort:** Low | **Impact:** High

---

### DF-02 — Booking Confirmation Email with iCal Attachment
When a booking is confirmed, send the booker an email with a `.ics` calendar attachment so they can add the event to Google Calendar, Apple Calendar, or Outlook with a single click.

**Effort:** Low | **Impact:** High

---

### DF-03 — SMS Notifications
Sri Lankan users are highly reliant on mobile. Integrating a local SMS gateway (e.g. Dialog API, Mobitel API, or Twilio for LK) would allow confirmation and reminder texts to be sent directly to the booker's mobile number without requiring an email address.

**Effort:** Medium | **Impact:** High

---

### DF-04 — Booking Status Tracker Page
A simple public page where a booker enters their reference number to see the live status of their booking (pending → payment received → confirmed), eliminating "what is the status of my booking?" calls to admins.

**Effort:** Low | **Impact:** Medium

---

### DF-05 — Slot Availability Notification (Waitlist)
If a desired date and slot is fully booked, let users submit their email and the desired slot. When that slot opens up (due to cancellation or rejection), they are automatically notified by email.

**Effort:** Medium | **Impact:** Medium

---

### DF-06 — Downloadable Booking Confirmation PDF
After confirmation, generate a formatted PDF "booking letter" the booker can download, print, and show at the facility gate. Includes: reference number, facility, date, slot, booker name, authorised signature/stamp placeholder.

**Effort:** Medium | **Impact:** Medium

---

### DF-07 — Online Payment Integration
Integrate a Sri Lankan payment gateway (PayHere, iPay, or genie by Dialog) so bookers can pay online immediately after booking. This eliminates the manual bank-deposit → receipt-upload → admin-verification cycle entirely.

**Effort:** High | **Impact:** Very High (biggest UX improvement possible)

---

## For Admins

### DF-08 — Booking Activity Log (Audit Trail)
Log every admin action (confirm, reject, cancel, receipt upload, pricing change) with the acting admin's name, timestamp, and before/after values. Essential for accountability and dispute resolution.

**Effort:** Low | **Impact:** High

---

### DF-09 — Admin Mobile Push Notifications
Push a notification to admins' phones when a new booking is submitted or a receipt is uploaded. Could be implemented via a Progressive Web App (PWA) manifest + service worker push, or a simple integration with a Slack/Telegram channel via webhook.

**Effort:** Medium | **Impact:** High

---

### DF-10 — Admin Booking Edit
Allow admins to change a booking's date, slot, or total amount after submission. Necessary when bookers call to correct mistakes. Should log the change and, optionally, send the booker an updated confirmation email.

**Effort:** Medium | **Impact:** High

---

### DF-11 — Automatic Cancellation of Overdue Pending Bookings
Auto-cancel pending bookings that have not had a receipt uploaded within a configurable time window (e.g. 3 hours after submission). Run as a scheduled task every hour. Send the booker a "booking expired" email so they can re-book.

**Effort:** Low | **Impact:** High (frees slots automatically)

---

### DF-12 — Occupancy Rate & Utilisation Dashboard
Charts showing facility utilisation by day of week, month, and slot type. Helps the college understand peak demand, identify consistently underbooked slots, and set pricing dynamically.

**Effort:** Medium | **Impact:** Medium

---

### DF-13 — Recurring Booking Support
Allow admins to create a recurring booking (e.g. every Friday night for 4 weeks) for regular customers such as sports academies. Creates multiple linked bookings from a single form.

**Effort:** High | **Impact:** Medium

---

### DF-14 — Two-Factor Authentication for Admins
Require a TOTP code (Google Authenticator / Authy) in addition to password at admin login. Protects booking data and financial information from compromised credentials.

**Effort:** Low | **Impact:** High (security)

---

### DF-15 — Forgot Password Flow for Admins
Standard email-based password reset so admins can regain access without Super Admin or database intervention.

**Effort:** Low | **Impact:** High (operational resilience)

---

## For the College (Institution-Level)

### DF-16 — Multi-Facility Discount / Package Booking
Allow a single booking to span both facilities on the same date (e.g. ground + hall for a sports day). Apply a configurable package discount. Requires a UI that lets users select multiple facilities and a pricing engine that handles the bundle.

**Effort:** High | **Impact:** Medium

---

### DF-17 — Facility Maintenance Scheduling
A separate calendar layer for admin-entered maintenance windows (different from booking blocks) with a distinct visual — e.g. grey hatching. Maintenance windows automatically block all bookings for the affected facility and date range, and are visible to admins in the calendar alongside bookings.

**Effort:** Low | **Impact:** Medium

---

### DF-18 — Annual Income Summary Report (PDF)
Generate a formatted annual income report PDF that the college administration can use for official reporting. Includes total income by facility, by slot type, and by month for the financial year.

**Effort:** Medium | **Impact:** Medium

---

### DF-19 — Booker Loyalty / Repeat Customer Recognition
Track repeat bookers by mobile number. Flag "known bookers" in the admin booking list. Optionally apply a configurable discount or priority status for bookers who have made a certain number of confirmed bookings.

**Effort:** Medium | **Impact:** Low–Medium

---

### DF-20 — WhatsApp Business API Integration (Admin Notification)
Instead of relying on admins to check email, send automated WhatsApp messages to admin phones when a new booking is submitted or a receipt is uploaded. Requires Meta WhatsApp Business API or Twilio for WhatsApp access.

**Effort:** Medium | **Impact:** High (for admins who respond faster on WhatsApp)

---

## Priority Matrix

| ID | Feature | Effort | Stakeholder Impact |
|---|---|---|---|
| DF-07 | Online payment gateway | High | ⭐⭐⭐⭐⭐ Transformative |
| DF-01 | Self-service cancellation | Low | ⭐⭐⭐⭐ |
| DF-11 | Auto-cancel overdue pending | Low | ⭐⭐⭐⭐ |
| DF-14 | 2FA for admins | Low | ⭐⭐⭐⭐ (security) |
| DF-15 | Forgot password | Low | ⭐⭐⭐⭐ (ops) |
| DF-02 | iCal in confirmation email | Low | ⭐⭐⭐ |
| DF-08 | Audit log | Low | ⭐⭐⭐ |
| DF-03 | SMS notifications | Medium | ⭐⭐⭐⭐ |
| DF-09 | Admin push notifications | Medium | ⭐⭐⭐ |
| DF-10 | Admin booking edit | Medium | ⭐⭐⭐ |
| DF-20 | WhatsApp API for admins | Medium | ⭐⭐⭐ |
| DF-06 | PDF booking letter | Medium | ⭐⭐ |
| DF-12 | Occupancy dashboard | Medium | ⭐⭐ |
| DF-05 | Waitlist / slot notification | Medium | ⭐⭐ |
| DF-13 | Recurring bookings | High | ⭐⭐ |
| DF-16 | Multi-facility package | High | ⭐⭐ |

---

*End of document.*
