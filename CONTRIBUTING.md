# Contributing

## Prerequisites

- Node.js 20+
- npm 10+
- WordPress 6.0+ (for PHP/plugin testing)
- PHP 7.4+

## Frontend setup

```bash
git clone https://github.com/mikko-lab/wpsa-booking.git
cd wpsa-booking
npm install
npm run dev   # http://localhost:5173
```

## Build

```bash
npm run type-check   # TypeScript
npm run lint         # ESLint
npm run build        # production build → public/
```

CI runs all three automatically on every push and PR.

## WordPress plugin setup

Copy the plugin directory to your WordPress installation:

```bash
cp -r . /path/to/wordpress/wp-content/plugins/wpsa-booking
```

Activate from **Plugins → Installed Plugins**, then configure under **Settings → WPSA Booking**.

See [INSTALLATION.md](INSTALLATION.md) for full setup including Google Calendar and Microsoft Teams OAuth.

## Accessibility requirements

**All contributions must maintain WCAG 2.2 Level AA compliance:**

- Use native HTML elements (`<button>`, `<input>`, `<a>`) — no `<div onClick>`
- Keyboard navigation must work without a mouse
- All interactive elements need visible focus indicators
- Screen reader tested: VoiceOver, NVDA, JAWS
- IBM Equal Access Checker: 0 violations before submitting PR
- No `any` types in TypeScript (strict mode)

## Commit style

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add Stripe payment at booking time
fix: prevent double-booking when lock expires
a11y: improve focus management after modal closes
chore: bump vite to 5.2
```

Common prefixes: `feat`, `fix`, `a11y`, `chore`, `ci`, `docs`, `refactor`.

## Branching

- `main` — stable releases
- `feat/<name>` — new features
- `fix/<name>` — bug fixes

Open a pull request against `main`. CI must pass before merging.

## What won't be accepted

- Zoom integration (their API requires paid plans)
- Group/multi-person appointments
- Any change that introduces keyboard traps or breaks screen reader flow
