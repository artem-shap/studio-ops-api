# Architecture decisions

Why this project is shaped the way it is. Each entry states what was chosen,
what it was chosen over, and what it costs — because a decision with no cost
attached is usually a decision that was not made.

---

## 1. Two applications, one source of truth

**Decision.** `studio-ops-api` (Laravel) owns the database and every business
rule. `studio-ops-web` (Next.js) owns the public site and the client portal and
holds no state of its own — no database, no ORM, no business logic.

**Over.** One Laravel application serving everything through Inertia, which
would have been less work.

**Why.** The two halves have genuinely different requirements. The public site
wants static rendering, edge delivery and to keep working while the API is
asleep. The admin panel wants fast CRUD behind a login and has no SEO surface
at all. Splitting them lets each be built the way its job wants.

**Cost.** A network boundary that would not otherwise exist, and a contract
between two codebases that has to be kept honest by hand until the OpenAPI
generation lands. Deliberately paid.

---

## 2. Inertia inside, REST outside

**Decision.** The admin panel talks to Laravel through Inertia — the controller
passes props straight into Vue. The public application talks to it over REST.

**Over.** One JSON API serving both, for consistency.

**Why.** An internal tool behind a login does not need an API layer; adding one
means writing and versioning endpoints whose only consumer ships in the same
deploy. An external application does need one. These are different problems and
they get different answers.

**Cost.** Two integration styles in one product, which needs explaining. That
explanation is this entry.

---

## 3. The browser never talks to the API

**Decision.** Next.js server acts as a backend-for-frontend. Browser to Next.js
server, Next.js server to Laravel, authenticated with a shared secret header.

**Over.** The browser calling the Laravel API directly with CORS configured.

**Why.** Four things fall out of it at once. CORS disappears from both
codebases. There is no public write endpoint exposed to the internet. The portal
token never appears in a browser network request. Rate limiting exists at two
layers instead of one.

**Cost.** One extra hop on every request, and the Next.js server becomes a
required participant rather than an optimisation.

---

## 4. The portal token is a bearer credential in a URL

**Decision.** Clients open their project through `/portal/{token}` with no
account and no password.

**Over.** Client accounts with real authentication.

**Why.** The studio's clients will not create accounts. They will click a link
in an email. An auth system nobody uses is worse than no auth system, because it
looks like security while everyone routes around it.

This breaks a standing rule — sensitive values do not belong in URLs — so it is
mitigated rather than waved through:

- 32 bytes of cryptographic randomness
- only the SHA-256 hash is stored, behind a unique index
- the plain token is returned exactly once, at conversion, and cannot be recovered
- expiry at 90 days, plus explicit revocation
- comparison through `hash_equals`, lookup by hash
- `throttle:20,1` on the endpoint
- `noindex` and `Referrer-Policy: no-referrer` on the page
- invalid, expired and revoked all return an identical 404
- the full token never reaches a log line

**Cost.** Anyone holding the link holds the access. That is the same property an
email link always has, and the expiry and revocation exist because of it.

---

## 5. PostgreSQL, not SQLite, because the filesystem is ephemeral

**Decision.** PostgreSQL everywhere — local development, CI and production.

**Over.** SQLite, which would have removed the database service entirely and
cost nothing to run.

**Why.** The production host has an ephemeral filesystem: anything written to
disk is lost on restart and on every deploy. A SQLite database would be wiped
regularly and silently. The same reasoning removed the `file` drivers for
sessions and cache, and sends logs to `stderr`.

The test suite runs on PostgreSQL for a related reason: the framework default is
in-memory SQLite, and a suite that runs on a different engine than production
cannot see engine-specific bugs. It costs 0.2 seconds.

**Cost.** A managed database to provision and a connection string to keep in
sync across three environments.

---

## 6. Statuses are enums that carry their own presentation

**Decision.** Each status is a backed PHP enum exposing `label()` and `color()`,
and every payload ships `{ value, label, color }`.

**Over.** Sending the raw string and mapping it in each frontend.

**Why.** There are two frontends. A status-to-colour map written in Vue and
again in React is two copies of one decision, and they drift the first time a
status is added. Shipping the presentation from the one place that owns the
values makes drift impossible rather than unlikely.

**Cost.** Slightly larger payloads, and presentation concerns living in a
backend enum, which is unusual enough to be worth this entry.

---

## 7. Deploy on day one, not day five

**Decision.** Both applications were deployed empty before either had a feature.

**Over.** Building first and deploying at the end, which is what the original
plan said.

**Why.** The original plan named its deployment day the riskiest and attached a
contingency to it. A contingency does not reduce risk, it prepares for it. Moving
the deployment to the first day removes the risk instead: every day after it ends
with a push to a production environment that already works.

**Cost.** Configuration work before there is anything to configure it for.

---

## 8. Money is an integer

**Decision.** `budget_cents` as an unsigned big integer, plus a currency code.
Forms take whole units, storage keeps minor units, formatting happens at the
edge with `Intl.NumberFormat`.

**Over.** A decimal column, which reads more naturally in the schema.

**Why.** A decimal that crosses into JavaScript becomes a float, and a float is
not a number of cents. The conversion happens in exactly one place on each side,
and a test pins it in both directions, because a hundredfold error looks
plausible whichever way round it goes.

**Cost.** Two conversions to keep straight, which is what the tests are for.


---

## 9. The panel has no sign-up and no self-deletion

**Decision.** Staff accounts are created and removed from the console, with
`php artisan staff:create` and `php artisan staff:remove`. The second refuses
to remove the last account.

**Over.** The starter kit's registration screen and the account-deletion
control in settings, both of which arrived working.

**Why.** They are correct for a product people sign themselves up for and wrong
for a tool five people share. Anyone holding an account here reads every
client, every project and every inquiry, and can issue portal links — so an
open `/register` is not a convenience, it is the front door standing open. It
was open on the live deployment.

Self-deletion was the same mistake pointing the other way, and worse in
combination: the demo runs on a single account whose credentials are published
in the README, so signing in and deleting it would have made the panel
permanently unreachable once registration was closed. Each fix made the other
one necessary.

**Cost.** Onboarding a colleague needs shell access rather than an invite link.
At this size that is the correct amount of friction.

---

## 10. A Content-Security-Policy without nonces

**Decision.** The public site sends a CSP that allows `'unsafe-inline'` for
scripts and locks down everything else: `frame-ancestors 'none'`,
`object-src 'none'`, `base-uri 'self'`, `form-action 'self'`,
`upgrade-insecure-requests`.

**Over.** A nonce-based policy, which is stronger for script injection.

**Why.** A per-request nonce has to be injected by middleware, and that forces
every page to render dynamically. The landing page is static on purpose — it is
what keeps the site answering while the API is asleep — and trading that away
for a tighter script directive is the wrong side of the deal on a page with no
third-party scripts, no analytics and no external fonts.

**Cost.** An injected inline script would execute. The mitigations above are
what limit what it could then do.

---

## 11. Native elements over a component library

**Decision.** The public site's form controls, buttons, accordion and mobile
menu are native HTML with shared class strings. The registry stays configured
for anything that genuinely needs behaviour, but nothing is kept installed that
nothing uses.

**Over.** The component library the site was first built on.

**Why.** Measured, not assumed. The library cost 111 KB gzipped for an
accordion and a menu — behaviour `details` and `summary` already implement,
including keyboard support, the expanded state and the accessible name, and
which keep working with JavaScript disabled. The four form primitives were
styled native elements wearing a component runtime.

The same audit found a validation library in the browser: the form imported its
budget list from the module defining the Zod schema, and took Zod with it, on a
page that does no client-side validation. Application JavaScript went from 74.6
KB gzipped to 11.4.

**Cost.** A dialog, a listbox or a date picker will have to be added back when
one is genuinely needed, and the shared class strings are a weaker abstraction
than components. Worth it at this size; it would not be on a larger surface.
