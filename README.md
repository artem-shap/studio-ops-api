# StudioOps — API and admin panel

Intake and project tracking for a small design studio.

This repository is the **single source of truth**: the database, the business
logic, the internal admin panel the studio works in, and the REST API consumed
by [`studio-ops-web`](../studio-ops-web).

> **Live demo:** _fill in after the first deploy_
> **Demo login:** `demo@studioops.dev` / `studioops`
>
> Hosted on a free tier that suspends after fifteen idle minutes. The first
> request after a quiet spell takes about a minute while the container wakes.
> That is a cost decision, not a defect — see [DECISIONS.md](DECISIONS.md).

---

## The problem

A studio of five to ten people runs ten to twenty projects at once. Inquiries
arrive by email, project status lives in Notion, deadlines live in someone's
head. Clients keep asking "so where are we on this?", and answering them is a
few hours a week that nobody bills for.

StudioOps takes the inquiry, turns it into a client and a project in one click,
and gives that client a private link showing their own milestones — so the
question stops being asked.

## Architecture

```
┌──────────────────────────────┐                 ┌───────────────────────────────┐
│  studio-ops-web              │                 │  studio-ops-api               │
│  Next.js 16 (TS + Tailwind)  │  server-to-     │  Laravel 13 + Vue 3 + Inertia │
│                              │  server only    │                               │
│  • Public site               │  ──────────────►│  • Internal admin panel       │
│  • Inquiry form              │  shared secret  │  • Eloquent + PostgreSQL      │
│  • Client portal             │◄────────────────│  • REST API for the web app   │
│                              │  JSON           │                               │
│  Next.js server = BFF        │                 │  Single source of truth       │
└──────────────────────────────┘                 └───────────────────────────────┘
    Vercel (Hobby, free)                      Render (free) + Neon Postgres (free)
```

The browser never talks to this application. The Next.js server does, with an
`X-Studio-Key` header. Consequently there is no CORS configuration in either
repository, and no public write endpoint exposed to the internet.

Eight decisions, including that one, are written up in [DECISIONS.md](DECISIONS.md).

## Stack

Laravel 13 · PHP 8.5 · Vue 3.5 · Inertia 3 · Fortify · Wayfinder · Tailwind 4 ·
shadcn-vue on Reka UI · PostgreSQL · Pest 5 · Larastan · Pint · Laravel Boost

## Running it locally

Requires PHP 8.3+, Composer, Node 22, pnpm and a PostgreSQL database.

```bash
git clone <repo> && cd studio-ops-api
cp .env.example .env          # fill in the database credentials
composer setup                # install, key:generate, migrate, pnpm install, build
php artisan migrate:fresh --seed
composer dev                  # server, queue worker and Vite together
```

The seeder prints the demo staff login and a working portal path. It creates six
clients, eight projects covering every status, forty-eight milestones and seven
inquiries — realistic enough to read, which is the point of a demo.

`php artisan demo:reset` puts it all back after someone has clicked around.

## Tests

```bash
composer test        # Pint, PHPStan, Pest
composer ci:check    # the above plus ESLint, Prettier and vue-tsc
```

Tests run against PostgreSQL rather than the framework's in-memory SQLite
default, on a separate database so `RefreshDatabase` never touches development
data.

## Notable pieces

| | |
|---|---|
| `app/Actions/ConvertInquiry.php` | Idempotent and transactional. Re-reads under a row lock **before** the guard, so two concurrent requests cannot both pass it |
| `app/Actions/GrantPortalAccess.php` | Issues a portal token; only the SHA-256 hash is ever persisted |
| `app/Actions/MoveMilestone.php` | Reorders through a sentinel, because positions are unique per project and PostgreSQL checks that immediately |
| `app/Enums/` | Statuses that carry their own label and colour, so neither frontend keeps a second copy |
| `app/Http/Middleware/EnsureStudioApiKey.php` | The entire authentication story for the public API |
| `app/Providers/AppServiceProvider.php` | Refuses to boot in production with debug enabled |

## Deployment

`render.yaml` provisions the service from the repository rather than a form.
Secrets are marked `sync: false`; everything else is configuration and lives in
git. The application ships a Dockerfile because the host has no native PHP
runtime.

See [DEPLOY.md](../DEPLOY.md) for the full sequence.

## Working with AI

Built with Claude Code, deliberately. The process is described in
[CONTRIBUTING.md](CONTRIBUTING.md), and [AI-NOTES.md](AI-NOTES.md) records the
eleven places the generated output was wrong, why, and the commit that fixed it.
