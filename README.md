# WPSA ZeroClick Sync

**A WordPress booking plugin that actually respects your time — and everyone's ability to use it.**

When someone books a consultation, the meeting link appears in their email. The calendar invite works. Screen readers understand what's happening. Nobody has to copy-paste anything, click through three screens, or wonder if the appointment actually exists.

It just works.

---

## The Problem

**Booking plugins are usually one of three things:**

1. **Feature-complete but inaccessible** — works great if you can see, use a mouse, and don't mind 18 clicks to book a 30-minute call
2. **Accessible but manual** — someone still has to create the video link, send the calendar invite, and hope nothing breaks
3. **Automated but locked to one vendor** — works beautifully until you need Microsoft Teams instead of Zoom, or want to own your data

**The worst part?** Most "accessible" plugins just slap ARIA labels on inaccessible patterns and call it done. A `<div onClick>` with `role="button"` is still a `<div>` that doesn't work with a keyboard.

**And the calendar chaos?** Every booking system I tested had the same problem: you book an appointment, get a confirmation email, then manually create a Google Meet link, copy it into three different places, send a second email, add it to your calendar, realize you forgot to add the client to the invite, send a third email apologizing, and finally mark it as "handled."

Zero-click was supposed to mean zero effort. Instead it meant zero automation and three manual steps per booking.

---

## The Solution

**This plugin does three things differently:**

### 1. Native HTML, not clever workarounds

Every interactive element is a real `<button>`, `<input>`, or `<a>`. Keyboard navigation works because browsers handle it, not because we wrote 200 lines of JavaScript to fake it.

The calendar uses `role="grid"` because it *is* a grid — with proper `row` and `gridcell` structure that screen readers actually understand.

Forms use `<dl>` for key-value pairs (like "Booking code: HQX6") because that's what description lists are *for*.

WCAG 2.2 Level AA isn't a nice-to-have. It's the baseline.

### 2. Zero-click calendar sync that actually syncs

When someone books a 30-minute consultation:

* Google Meet link (or Microsoft Teams) is created **automatically**
* Calendar invite lands in their inbox **with the video link already inside**
* Your calendar updates **without you touching it**
* If they cancel, both calendars update **automatically**

No copy-pasting meeting links. No "oh wait, let me send you the Zoom URL." No double-booking because you forgot to check two calendars.

The first time I tested it, I thought it had broken — because I'd never seen a booking system where nothing required my intervention afterward.

### 3. Timeslot locking instead of race conditions

Most booking systems have a gap between "user selects time" and "booking confirmed" where two people can try to book the same slot. The usual "solution" is to just let both go through, then send an apology email to whoever was second.

We lock the timeslot for 5 minutes when someone clicks it. If they don't complete the booking, it unlocks automatically. If someone else tries to book it, they see it's unavailable — not a generic error after filling out a form.

No polling. No WebSockets. Just a simple database lock with a timeout.

---

## Architecture

### The Three Layers

**Frontend (React + TypeScript)**

* Accessible calendar navigation (Arrow keys, Tab, Enter, Escape — all work)
* Real-time slot locking (5-minute timeout)
* Semantic HTML (buttons are `<button>`, not `<div role="button">`)
* Screen reader announcements (`aria-live="polite"` for status changes)

**Backend (WordPress REST API + PHP)**

* Timeslot locking system (prevents double-bookings)
* OAuth 2.0 integration (Google Calendar + Microsoft Teams)
* Token management with auto-refresh (meetings don't break when tokens expire)
* AES-256 encryption for stored credentials

**Video Providers (Factory Pattern)**

* Pluggable architecture: Google Meet, Microsoft Teams, or manual .ics files
* Each provider handles its own OAuth flow and API calls
* New providers can be added without touching existing code

### How Booking Works

```
User selects time
  ↓
Frontend locks timeslot for 5 minutes (REST API call)
  ↓
User fills out form
  ↓
User submits
  ↓
Backend verifies lock still exists
  ↓
Backend creates booking in database
  ↓
Backend triggers video provider (Google/Teams)
  ↓
Provider creates meeting link via OAuth
  ↓
Backend sends email with .ics calendar invite (link embedded)
  ↓
Backend unlocks timeslot
  ↓
Both calendars sync automatically
```

If any step fails, the lock expires and the slot becomes available again. No orphaned bookings.

---

## Features

### For Customers

* Screen reader friendly — VoiceOver/NVDA/JAWS announce everything correctly
* Keyboard navigation — never need a mouse (Arrow keys in calendar, Tab between fields, Enter to submit)
* Locked slots visible — see what's unavailable before filling out a form
* Instant confirmation — video link arrives in email within seconds
* Calendar integration — click .ics attachment → opens in Apple Calendar/Outlook/Google Calendar

### For You

* Zero manual work — meeting link, calendar invite, and email happen automatically
* No double-bookings — timeslot locking prevents race conditions
* Dual calendar sync — your calendar and theirs update simultaneously
* Auto-refresh tokens — OAuth tokens renew themselves
* Encrypted credentials — client secrets stored with AES-256-CBC

### For Developers

* Factory pattern — add new video providers without touching existing code
* REST API — all booking logic accessible via `/wp-json/wpsa-zeroclick/v1/`
* TypeScript — catch errors at compile time, not production
* Semantic HTML — real `<button>`, `<dl>`, `<grid>` elements (no ARIA hacks)
* Automated testing — IBM Equal Access Checker: 0 violations

---

## Tech Stack

**Frontend:** React 18 + TypeScript, Tailwind CSS, date-fns, Vite

**Backend:** WordPress REST API, OAuth 2.0 (Google Calendar API, Microsoft Graph API), AES-256-CBC encryption, Factory pattern

**Standards:** WCAG 2.2 Level AA, Semantic HTML5, ARIA best practices

---

## Installation

```bash
# Download latest release
wget https://github.com/mikko-lab/wpsa-booking/releases/latest/download/wpsa-booking.tar.gz

# Upload to WordPress
# Plugins → Add New → Upload Plugin → Activate

# Configure
# Settings → WPSA ZeroClick → Connect Google Calendar or Microsoft Teams
```

**Requires:** WordPress 6.0+, PHP 7.4+

See [INSTALLATION.md](INSTALLATION.md) for full setup instructions including Google Calendar API and Microsoft Teams configuration.

---

## WCAG 2.2 Level AA Compliance

Tested with:

* IBM Equal Access Accessibility Checker (0 violations)
* axe DevTools (0 violations)
* WAVE (0 errors)
* VoiceOver (macOS)
* NVDA (Windows)
* JAWS (Windows)

Key accessibility features: semantic HTML, keyboard navigation (Tab, Arrow keys, Enter, Escape), screen reader announcements (`aria-live`), focus indicators (3px outline + shadow), color contrast ≥ 4.5:1, touch targets 48×48px minimum.

---

## What's Next

* Stripe integration (take payment at booking time)
* Recurring appointments (weekly, biweekly, monthly)
* Waitlist (if slot is locked/booked, join waitlist)
* SMS reminders (Twilio integration)
* Multi-language support (currently Finnish/English)

---

## Contributing

Found a bug? Have an idea? Open an issue.

Want to add a feature? Fork it, build it, open a PR.

**Guidelines:**

* Must maintain WCAG 2.2 Level AA compliance
* Must work with keyboard only (no mouse required)
* Must pass IBM Equal Access Checker (0 violations)
* TypeScript strict mode (no `any` types)
* Semantic HTML (prefer native elements over ARIA)

---

## Why This Exists

I run a web accessibility consulting business in Finland ([wpsaavutettavuus.fi](https://wpsaavutettavuus.fi)). Every booking plugin I tried either worked great but was completely inaccessible, was accessible but required 10 manual steps per booking, or cost €50/month and locked me into someone else's ecosystem.

After the 12th time manually creating a Google Meet link and forgetting to attach it to the calendar invite, I built this instead.

---

## License

MIT — use it, fork it, sell it, whatever.

Built by [Mikko Tarkiainen](https://wpsaavutettavuus.fi).
