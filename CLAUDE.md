# CLAUDE.md — studio-ops-api

Instructions for Claude Code in this repository.

---

## 0. This file overrides the global configuration

The global `~/.claude/CLAUDE.md` mandates Astro, Supabase and Vercel for every new website. **That does not apply to this project.** The stack here is fixed by the project's requirements and listed below. Do not propose Astro, do not propose Supabase, do not propose migrating to either.

**What does still apply from the global configuration:**
- No emoji anywhere — not in the interface, not in code, not in comments, not in commit messages, not in your replies. Icons come from Lucide, which is already installed as `@lucide/vue`
- Security principles: validate on the server, check authorisation on every endpoint, check ownership to prevent insecure direct object references, secrets only in environment variables, security headers, rate limiting
- TypeScript strict, no `any`
- Dark mode from the first commit, not retrofitted
- Accessibility: semantic HTML, `aria-label` on icon-only buttons, visible focus states, 4.5:1 contrast

---

## 1. Stack — as actually installed

| Layer | Technology |
|---|---|
| Framework | Laravel 13.26 |
| PHP | 8.5 (`composer.json` requires ^8.3) |
| Admin frontend | Vue 3.5 + **Inertia 3** + TypeScript |
| Auth | Laravel **Fortify** (passkeys and two-factor available) |
| Typed routes | Laravel **Wayfinder** |
| Styling | Tailwind 4.1 + shadcn-vue on **Reka UI** |
| Build | Vite 8 |
| Database | PostgreSQL (Neon) |
| Tests | **Pest 5** |
| Static analysis | **Larastan / PHPStan** |
| Formatting | Pint (PHP), Prettier + ESLint (TS/Vue) |
| AI context | **Laravel Boost** with skills |
| Hosting | Render free tier, via Docker |

Scaffolded with `laravel new --vue --pest --database=pgsql --pnpm --boost`.

**Do not use Laravel Breeze and do not suggest it.** Breeze stopped being an official starter kit in Laravel 12. Authentication here is Fortify, which the Vue starter kit installs.

**Inertia is version 3, not 2.** Check `node_modules/@inertiajs/vue3` before relying on anything you remember about Inertia 2.

---

## 2. Laravel Boost is installed — use it

`laravel/boost` provides version-accurate context for this exact Laravel, Inertia, Fortify, Pest and Tailwind release, plus skills:

`infer-conventions`, `fortify-development`, `laravel-best-practices`, `wayfinder-development`, `pest-testing`, `inertia-vue-development`, `tailwindcss-development`.

**Consult the relevant Boost skill before writing code in an area you are unsure about**, rather than relying on training data. This project is built on Laravel 13, Inertia 3 and Pest 5, all of which are newer than most published examples. Guessing from memory here is the single most likely source of wrong output.

---

## 3. What this product is

StudioOps is an intake and project-tracking system for a small design studio.

This repository is the **single source of truth**: the database, the business logic, the admin panel for studio staff, and the REST API for the public application.

The second repository, `studio-ops-web` (Next.js 16), is the public site and client portal. It calls this application **server to server only**, with an `X-Studio-Key` header. A client's browser never reaches this application directly.

**Consequence: CORS is not needed in this project.** If you are about to add `config/cors.php` or `HandleCors`, stop — that indicates a misunderstanding of the boundary.

---

## 4. Deployment constraints — read before writing config

Production runs on a free tier with an **ephemeral filesystem**. Anything written to disk is lost on restart and on every deploy.

- `LOG_CHANNEL=stderr` in production. Never write to `storage/logs` there
- `SESSION_DRIVER=database` and `CACHE_STORE=database`. These are already the defaults — never change them to `file`
- No file uploads, and none are in scope

**SQLite is deliberately not used.** The scaffold created `database/database.sqlite`; it was deleted on purpose. The filesystem is ephemeral, so a SQLite database would be wiped on every restart. The database is external PostgreSQL, on Neon, both locally and in production. Do not reintroduce a SQLite file, including for tests.

The service sleeps after 15 minutes of inactivity and takes roughly a minute to wake. Keep boot work minimal: no heavy work in service providers, configuration and routes cached at build time.

Render has no native PHP runtime, so the application ships with a `Dockerfile` on a FrankenPHP base image. Keep it minimal and readable — it is part of what gets reviewed.

---

## 5. Data model

```
users        studio staff
clients      + portal_token_hash, portal_token_expires_at, portal_token_revoked_at
projects     client_id, status (enum), budget_cents, currency, start_date, due_date
milestones   project_id, status (enum), position
inquiries    status (enum), converted_client_id, converted_project_id, converted_at
```

Statuses are backed enums in `app/Enums/`, each exposing `label(): string` and `color(): string`.
API Resources serialise a status as `{ value, label, color }`, so the status-to-colour mapping is not duplicated across the Vue admin and the Next.js client.

**Hard rules:**
- Money is `budget_cents` (unsigned big integer) plus `currency` (char 3). Never a float, never a decimal that ends up in JavaScript
- `timestamps` on every table
- An index on every foreign key and on every `status` column used for filtering
- `unique` on `clients.email` and on `(project_id, position)` in `milestones`
- `milestones.position` uses steps of 100, so inserting between two milestones does not rewrite the table

---

## 6. Code conventions

**Controllers stay thin.** Logic lives in models or in Action classes under `app/Actions/`. A controller method past roughly fifteen lines is a signal to extract.

**Validation lives only in Form Requests** (`app/Http/Requests/`). No `$request->validate()` inside a controller. If you generate validation in a controller, that is a mistake — fix it, and it gets recorded in `AI-NOTES.md`.

**Outbound responses go through API Resources** (`app/Http/Resources/`). Never `return $model` and never `return $model->toArray()` — models leak fields that must not go over the wire, `portal_token_hash` first among them.

**Multi-table writes are Action classes wrapped in a transaction, and they are idempotent.** `ConvertInquiryAction` checks `converted_at` on entry: calling it twice creates one client and one project, not two. A double-clicked button is a normal user, not an edge case.

**No magic strings, on either side of the boundary.** Statuses through enums. Routes through `route()` in PHP, and through **Wayfinder**-generated helpers in TypeScript — never a hand-written URL string in a Vue component. Configuration through `config()`; never call `env()` outside `config/`.

**Inertia:** the controller passes props straight into the Vue page. Do not build JSON endpoints for the admin panel — that is the wrong layer.

**PHPStan must stay green.** `composer types:check` runs Larastan. Do not silence it with baselines or `@phpstan-ignore` to make a change land.

---

## 7. Vue in the admin panel

- Composition API, `<script setup>`, TypeScript
- `defineProps<T>()` with real types, never an array of strings
- Inertia's `useForm` for every form — not `fetch`, not `axios`
- Wayfinder helpers for route URLs
- shadcn-vue components via its CLI into `resources/js/components/ui/`; edit those files directly rather than wrapping them
- Small components. The page composes, the components render
- Designed empty states everywhere: not "nothing found", but an action — "Add your first client" with a button
- A flash message after every mutation

---

## 8. Security

**The portal token is a bearer credential. Treat it like a password:**
- generated with `bin2hex(random_bytes(32))`
- stored only as `hash('sha256', $token)`, behind a unique index
- returned exactly once, at inquiry conversion
- looked up by hash, compared with `hash_equals`
- `portal_token_expires_at` and `portal_token_revoked_at` checked on every request
- invalid, expired and revoked tokens all return an **identical** 404, with no detail about which case it was
- the full token never reaches the logs; log `substr($hash, 0, 8)` instead

**Public endpoints:**
- `POST /api/inquiries` — `throttle:10,1`, honeypot field, hard maximum lengths
- `GET /api/portal/{token}` — `throttle:20,1`
- both require the `X-Studio-Key` header, known only to `studio-ops-web`

**General:**
- Every admin route sits behind `auth` middleware. Ownership is checked before returning any resource — insecure direct object references do not close themselves
- Secrets only in environment variables. `.env.example` is committed, `.env` never is
- Security headers in middleware: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`
- The portal endpoint additionally sends `X-Robots-Tag: noindex, nofollow` and `Referrer-Policy: no-referrer`
- No raw SQL built by string concatenation. Eloquent or bindings, always

---

## 9. Tests

Pest 5. Four mandatory feature tests:

1. `POST /api/inquiries` rejects an empty email and an over-long message
2. `ConvertInquiryAction` called twice produces exactly one client and one project
3. `GET /api/portal/{token}` returns 404 for invalid, expired and revoked tokens
4. An unauthenticated request to an admin route redirects to login

New business logic in an Action class ships with a test. Vue components are not unit-tested — at this size the cost outweighs the signal, and that is a deliberate choice.

Tests run against PostgreSQL, not SQLite. Do not add an in-memory SQLite test connection.

---

## 10. Continuous integration

`.github/workflows/tests.yml` came with the scaffold and already runs `composer ci:check` on every push to `main` and every pull request. That covers ESLint, Prettier, `vue-tsc`, Pint, PHPStan and Pest.

Do not replace it with a hand-written workflow. Extend it only where needed — the one thing it lacks is a database for the test run.

---

## 11. Working process

- Conventional Commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- One branch per feature, pull request into `main`, CI must be green
- Small, meaningful commits, not one large one
- Every diff is read before it is committed. Always

**When your output gets corrected, it gets written down.** If you generate something that has to be reworked — validation in a controller, `return $model`, a status as a string, a token stored in plain text, a hand-written URL where Wayfinder has a helper, an assumption from Inertia 2 — it goes into `AI-NOTES.md` as: what was generated, why it was corrected, how it was corrected, and the commit hash.

---

## 12. Commands

```bash
composer setup           # install, key, migrate, pnpm install, build
composer dev             # serve + queue + vite together
composer test            # pint --test, phpstan, pest
composer ci:check        # everything CI runs
composer lint            # pint, writes changes
pnpm types:check         # vue-tsc

php artisan migrate:fresh --seed   # database from zero
php artisan demo:reset             # restore the demo to a clean state
```

Seeders contain realistic data — real-sounding studio and project names, never "Test Client 1".

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
