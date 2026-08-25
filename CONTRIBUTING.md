# Contributing — studio-ops-api

Engineering standards for this repository. These are enforced in review and in CI.

StudioOps is an intake-and-tracking system for a small design studio. This repository is the **single source of truth**: database, business logic, the internal admin panel, and the REST API consumed by `studio-ops-web`.

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13.26 |
| PHP | 8.5 |
| Admin frontend | Vue 3.5 + Inertia 3 + TypeScript |
| Authentication | Laravel Fortify |
| Typed routes | Laravel Wayfinder |
| Styling | Tailwind 4.1 + shadcn-vue on Reka UI |
| Build | Vite 8 |
| Database | PostgreSQL (Neon) |
| Tests | Pest 5 |
| Static analysis | Larastan / PHPStan |
| Formatting | Pint, Prettier, ESLint |
| Hosting | Render, via Docker |

Scaffolded with `laravel new --vue --pest --database=pgsql --pnpm --boost`, which is the official Vue starter kit. Laravel Breeze is not used: it stopped being an official starter kit in Laravel 12, and authentication here comes from Fortify.

---

## Getting started

```bash
git clone <repo> && cd studio-ops-api
cp .env.example .env          # then fill in DATABASE credentials
composer setup                # install, key:generate, migrate, pnpm install, build
composer dev                  # server, queue worker and Vite together
```

`php artisan migrate:fresh --seed` must always bring the database up from zero with realistic demo data. If a change breaks that, the change is not done.

Seeders use realistic names — actual-sounding studios, projects and milestones. Never `Test Client 1`.

PostgreSQL is used locally as well as in production, through a development branch of the same Neon database. Running a different engine locally than in production is an antipattern, and it hides exactly the bugs that matter.

---

## Deployment

Production runs on free-tier infrastructure. Three properties of that environment shape the code, and all three are deliberate.

**The filesystem is ephemeral.** Anything written to disk is lost on restart and on every deploy.

- `LOG_CHANNEL=stderr` in production. Nothing is written to `storage/logs` there
- `SESSION_DRIVER=database` and `CACHE_STORE=database`, which are already the framework defaults. The `file` driver is never used
- There are no file uploads, and none are in scope

**SQLite was considered and rejected** for the same reason. It would remove the database service entirely, but the database file would be wiped on every restart. The scaffold creates `database/database.sqlite` by default; it was deleted deliberately, and it is not reintroduced for tests either. Tests run against PostgreSQL, because tests that run on a different engine than production are tests that miss engine-specific bugs.

**The service sleeps after 15 minutes of inactivity** and takes roughly a minute to wake. Boot work is therefore kept minimal: no heavy work in service providers. Configuration, routes and views are cached by the entrypoint at container start rather than during the image build, because `config:cache` resolves `env()` when it runs and the environment does not exist until runtime. Consumers are expected to use a generous timeout and one retry, and `studio-ops-web` does.

**There is no native PHP runtime on the host**, so the application ships with a `Dockerfile` built on a FrankenPHP base image. It is kept minimal and readable, because it is part of what gets reviewed.

This trade-off is stated plainly in the README rather than hidden. A cold start a reader was warned about is a cost decision; one that surprises them is a defect.

---

## Architecture boundaries

The browser never talks to this application directly. `studio-ops-web` (Next.js) calls it **server to server** with an `X-Studio-Key` header.

Consequences that are easy to get wrong:

- **CORS is not configured, and must not be.** A pull request that adds CORS configuration is a pull request that misunderstands the boundary
- The admin panel is served through Inertia — the controller passes props straight into Vue. Do not build JSON endpoints for the admin panel
- The public API exists only for `studio-ops-web` and stays deliberately small

---

## Code conventions

**Controllers stay thin.** Business logic belongs in models or in Action classes under `app/Actions/`. A controller method past roughly fifteen lines is a signal to extract.

**Validation lives in Form Requests** (`app/Http/Requests/`), never inline in a controller. Inline validation cannot be reused, and it grows the controller for no benefit.

**Outbound responses go through API Resources** (`app/Http/Resources/`). Never `return $model`. Models leak fields that must not go over the wire — `portal_token_hash` first among them.

**Multi-table writes are Action classes wrapped in a transaction, and they are idempotent.** `ConvertInquiryAction` checks `converted_at` on entry: calling it twice creates one client and one project, not two. A double-clicked button is a normal user, not an edge case.

**No magic strings, on either side of the boundary.** Statuses are backed enums in `app/Enums/`. Routes go through `route()` in PHP and through Wayfinder-generated helpers in TypeScript — a hand-written URL string in a Vue component is a defect. Configuration goes through `config()`; `env()` is never called outside `config/`.

**Enums own their presentation.** Each status enum exposes `label()` and `color()`, and API Resources serialise a status as `{ value, label, color }`. This keeps the status-to-colour mapping in exactly one place instead of duplicating it across the Vue admin and the Next.js client.

**PHPStan stays green.** `composer types:check` runs Larastan. Findings are fixed, not silenced with a baseline or an ignore annotation.

---

## Database conventions

- Money is stored as `budget_cents` (unsigned big integer) plus a `currency` column. Never a float, and never a decimal that ends up in JavaScript
- `timestamps` on every table
- An index on every foreign key, and on every `status` column used for filtering
- `unique` on `clients.email`, and on `(project_id, position)` in `milestones`
- `milestones.position` uses steps of 100, so inserting between two milestones does not rewrite the table
- Schema changes go through migrations. Never edit a migration that has already run in production — write a new one

---

## Security

**The portal token is a bearer credential.** It is treated like a password:

- generated with `bin2hex(random_bytes(32))`
- stored only as `hash('sha256', $token)`, behind a unique index
- returned exactly once, at inquiry conversion
- looked up by hash, compared with `hash_equals`
- validated against `portal_token_expires_at` and `portal_token_revoked_at` on every request
- invalid, expired and revoked tokens all return an identical 404 — the response never reveals which case it was
- never written to logs in full; `substr($hash, 0, 8)` is logged instead

**Public endpoints:**

- `POST /api/inquiries` — `throttle:10,1`, honeypot field, hard maximum lengths
- `GET /api/portal/{token}` — `throttle:20,1`
- both require the `X-Studio-Key` header

**General:**

- Every admin route sits behind `auth` middleware
- Ownership is checked before returning any resource. Insecure direct object references do not close themselves
- Security headers are set in middleware: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`
- No raw SQL built by string concatenation. Eloquent or bindings, always
- `composer audit` runs clean before release

---

## Tests

Pest 5. Four feature tests are mandatory and must stay green:

1. `POST /api/inquiries` rejects an empty email and an over-long message
2. `ConvertInquiryAction` called twice produces exactly one client and one project
3. `GET /api/portal/{token}` returns 404 for invalid, expired and revoked tokens
4. An unauthenticated request to an admin route redirects to login

New business logic in an Action class ships with a test. Vue components are not unit-tested — the cost outweighs the signal at this size, and that is a deliberate choice rather than an omission.

---

## Continuous integration

`.github/workflows/tests.yml` ships with the starter kit and runs on every push to `main` and every pull request. It executes `composer ci:check`, which covers:

- `pnpm lint:check` — ESLint
- `pnpm format:check` — Prettier
- `pnpm types:check` — `vue-tsc`
- `composer test` — Pint in check mode, PHPStan, and Pest

A red pipeline blocks the merge. The workflow pins its actions by commit SHA and runs without persisted credentials; keep both properties when editing it.

---

## Commands

```bash
composer setup           # install, key:generate, migrate, pnpm install, build
composer dev             # serve, queue worker and Vite together
composer test            # Pint check, PHPStan, Pest
composer ci:check        # everything CI runs
composer lint            # Pint, writing changes
pnpm types:check         # vue-tsc

php artisan migrate:fresh --seed   # database from zero
php artisan demo:reset             # restore the demo to a clean state
```

---

## Branching, commits, pull requests

- One branch per feature: `feat/client-crud`, `fix/milestone-ordering`
- [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- Small, meaningful commits. One commit should be one reviewable idea
- Every change goes through a pull request into `main`, with green CI, even when working solo
- Never commit `.env`. `.env.example` is the contract and stays current

---

## AI-assisted development

This project is built with Claude Code, deliberately and with a documented process rather than ad hoc prompting.

**Laravel Boost is installed** (`laravel/boost`), which supplies version-accurate context for this exact Laravel, Inertia, Fortify, Pest and Tailwind release, along with skills for Fortify, Wayfinder, Pest, Inertia and Tailwind. This matters more than usual here: the project runs Laravel 13, Inertia 3 and Pest 5, all newer than most published examples, so an assistant working from memory rather than from Boost will confidently produce Inertia 2 code that no longer applies.

**The loop:**

1. The feature is specified in writing first — data model, endpoints, behaviour. Generation happens against a specification, never against "build me a CRM"
2. Repository conventions live in `CLAUDE.md`, alongside the Boost guidelines block, so generation starts from this project's standards rather than generic defaults
3. Every diff is read before it is committed. Without exception
4. A separate review pass looks specifically for duplication, unvalidated input, and logic that has drifted out of its layer
5. Commits stay small, so a bad generation is cheap to reverse

**`AI-NOTES.md` records where the generated output was wrong and why.** Each entry states what was generated, why it was corrected, how it was corrected, and links the commit that did it. It is a record of judgement applied to AI output, which is the part of AI-assisted development that actually matters.
