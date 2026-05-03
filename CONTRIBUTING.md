# Contributing

Thanks for your interest in WPSA ZeroClick Sync!

## Development Setup

### Requirements

- Node.js 20+
- PHP 7.4+
- WordPress 6.0+ (local install recommended: [LocalWP](https://localwp.com))

### Installation

```bash
git clone https://github.com/mikko-lab/wpsa-booking.git
cd wpsa-booking
npm install
npm run build
```

Copy or symlink the plugin folder to your local WordPress installation:

```bash
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/wpsa-booking
```

Activate the plugin in WordPress Admin → Plugins.

### Development commands

```bash
npm run dev      # watch mode (Vite)
npm run build    # production build
npm run lint     # lint TypeScript
npx tsc --noEmit # TypeScript typecheck
```

## How to Contribute

### Bug reports

Open an [Issue](https://github.com/mikko-lab/wpsa-booking/issues) and include:

- What you did
- What you expected to happen
- What actually happened
- WordPress version, PHP version, browser

### Feature requests

Open an Issue with the title `[feat]: feature name`. If the feature affects accessibility, mention which WCAG criterion it relates to.

### Pull requests

1. Fork the repository
2. Create a branch: `git checkout -b feat/feature-name`
3. Make your changes
4. Verify TypeScript: `npx tsc --noEmit`
5. Verify lint: `npm run lint`
6. Verify PHP: `php -l *.php includes/*.php`
7. Commit with a clear message (see below)
8. Open a Pull Request against `main`

## Accessibility Requirements

All changes must maintain WCAG 2.2 Level AA compliance:

- Keyboard navigation (Tab, Arrow keys, Enter, Escape — no mouse required)
- Semantic HTML (real `<button>`, `<input>`, `<a>` — no `<div onClick>`)
- ARIA only where native HTML is insufficient
- IBM Equal Access Checker: 0 violations
- Color contrast ≥ 4.5:1 for normal text
- Touch targets ≥ 48×48px

## Commit Convention

| Prefix | When to use |
|--------|-------------|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `a11y:` | Accessibility fix |
| `ci:` | CI/CD changes |
| `docs:` | Documentation |
| `refactor:` | Structural change with no functional change |
| `chore:` | Maintenance (dependency updates etc.) |

## License

By contributing, you agree that your changes will be released under the [MIT License](LICENSE).
