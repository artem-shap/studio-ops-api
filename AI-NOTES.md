# AI notes

Where Claude Code's output was wrong, why it was wrong, and what replaced it.

Kept as the work happens rather than reconstructed afterwards, so the entries
are the real ones. Every entry links the commit that fixed it.

---

## 1. Nullsafe operator on a non-nullable property

**Generated:** `$this->command?->info(...)` in `DatabaseSeeder`, from the habit
that a seeder might run outside the console.

**Why it was wrong:** in this Laravel version `Seeder::$command` is typed
non-nullable, so `?->` is dead weight that also signals a nullability that does
not exist. Larastan flagged it as `nullsafe.neverNull` across four lines.

**Fixed:** replaced with `->`. The underlying assumption was wrong, so the fix
was to drop the guard rather than to silence the analyser. No baseline entry, no
`@phpstan-ignore`.

**Commit:** `7b1c0a7`

---

## 2. Artisan path argument duplicated the namespace

**Generated:** `php artisan make:enum Enums/ProjectStatus`, on the assumption
that the path needed spelling out.

**Why it was wrong:** `make:enum` already namespaces under `App\Enums`, so the
argument produced `app/Enums/Enums/`. Two of the three enums landed one
directory too deep, in a folder that would have been dead code shipped to
production.

**Fixed:** removed `app/Enums/Enums/`; the correct form is
`make:enum ProjectStatus --string`. Worth noting because nothing failed — no
error, no red test. It would simply have sat there.

**Commit:** `7b1c0a7`

---

## 3. Generated .gitignore silently excluded .env.example

**Generated:** `create-next-app` writes `.env*` into `.gitignore`.

**Why it was wrong:** that pattern also matches `.env.example`, which is the one
environment file that belongs in the repository. It is the contract that tells
the next person which variables exist. The failure mode is quiet: the file is
created, `git status` never mentions it, and it is missing from the clone.

**Fixed:** added `!.env.example` below the pattern. Caught because the file did
not appear in `git status` when it should have.

**Commit:** `e2e945d` in `studio-ops-web`


---

## 4. A Dockerfile whose build stage could not run the build

**Generated:** a conventional two-stage Dockerfile — `node:22-alpine` to build
the frontend, then FrankenPHP for the runtime. It is the right shape for most
PHP projects and it is what the pattern looks like everywhere.

**Why it was wrong:** Wayfinder generates its TypeScript route helpers by
invoking `artisan` during the Vite build. A Node-only stage has no PHP, so
`pnpm run build` would have died on the first deploy. Nothing local catches
this: the build works on a machine that has both.

**Fixed:** the build stage is now FrankenPHP with Node added to it, so PHP is
present when Vite calls artisan. Node and the dev dependencies are dropped
before the runtime stage, so the shipped image is no larger for it.

**Commit:** `ac0695c`

---

## 5. Caching configuration at image build time

**Generated:** `php artisan config:cache` as a build step in the Dockerfile,
next to `route:cache` and `view:cache`. This was also written into `CLAUDE.md`
as a standing instruction, so it would have been repeated.

**Why it was wrong:** `config:cache` resolves every `env()` call at the moment
the cache is written. The build runs before the host injects the environment,
so the cache would have frozen empty values — no database credentials, no app
key — and the failure would have appeared only after deploying, as a runtime
error with no obvious link to the Dockerfile.

**Fixed:** all three caches moved into the entrypoint, and the standing
instruction in `CLAUDE.md` corrected along with them. The cost is about a tenth
of a second on a cold start that already takes most of a minute.

**Commit:** `ac0695c`

---

## 6. The test suite ran on a different engine than production

**Generated:** nothing — this one came with the framework's default
`phpunit.xml`, which forces `DB_CONNECTION=sqlite` and `:memory:`.

**Why it was wrong:** the project had already committed, in writing, to running
tests on the engine production runs on, and a PostgreSQL service had just been
added to CI for that purpose. The default quietly contradicted both. It is
included here because the lesson is the same one: a generated or scaffolded
default is an assumption, and assumptions that contradict a written decision are
worth finding before they hide a bug rather than after.

**Fixed:** removed the SQLite override and pointed the suite at a separate
PostgreSQL database, so `RefreshDatabase` never touches development data. The
39 existing tests pass in 1.4s against PostgreSQL versus 1.2s against in-memory
SQLite, which is not a trade worth making.

**Commit:** `ac0695c`


---

## 7. findOrFail inside a transaction, and the union type it hides

**Generated:** `Inquiry::query()->lockForUpdate()->findOrFail($id)` at the top of
`ConvertInquiry`, to re-read the row under a lock before checking the guard.

**Why it was wrong:** `findOrFail` also accepts an array of keys, so its return
type is `Model|Collection`. Every property read after it — `$inquiry->email`,
`$inquiry->isConverted()` — was therefore untypeable, and Larastan produced nine
errors from one line. The code would have run correctly; the types were the
thing that was wrong, and the tempting fix is a `@var` annotation that asserts
what the developer already believes.

**Fixed:** `whereKey($id)->lockForUpdate()->firstOrFail()`, which returns a
single model and nothing else. The annotation would have silenced the analyser
while leaving the ambiguity in place. No baseline, no ignore, no cast.

**Commit:** `aa0afa0`

---

## 8. Sanctum, nearly installed for an API with no users

**Generated:** the standard advice for adding an API to Laravel 11 and later is
`php artisan install:api`, and that was the first move here.

**Why it was wrong:** that command installs Sanctum and a personal access token
migration. This API has no users at all — it is two servers authenticating to
each other with a shared secret header. Sanctum would have shipped an unused
dependency, an unused table, and an obvious question for whoever read the code
next: which token model is this project using, and why can I not find it.

**Fixed:** `routes/api.php` written by hand and registered in
`bootstrap/app.php`. The reasoning is a comment at the top of the routes file,
because the absence of a thing is harder to explain later than its presence.

**Commit:** `aa0afa0`


---

## 9. useForm, the idiom this project does not use

**Generated:** Vue pages built around `useForm` from `@inertiajs/vue3`, which is
what almost every Inertia example shows. It was also written into `CLAUDE.md` as
a standing rule, so every future page would have repeated it.

**Why it was wrong:** this project is on Inertia 3, where forms are the `<Form>`
component bound to a Wayfinder action — `v-bind="ClientController.store.form()"`.
The starter kit's own settings pages were sitting right there using it. `useForm`
still exists, so nothing would have failed; the code would simply have been
written against the previous major version, in a codebase whose other pages use
the current one.

**Fixed:** every admin page uses `<Form>`, and the standing rule in `CLAUDE.md`
was corrected along with them. This is the specific failure mode Laravel Boost
exists to prevent, and the reason its guidelines block is committed next to ours.

**Commit:** `05a15c5`

---

## 10. artisan wayfinder:generate quietly deleted the form helpers

**Generated:** `php artisan wayfinder:generate` to produce route helpers for the
new controllers. The command exists, it is the obvious one, and it reported
success.

**Why it was wrong:** the `.form()` variants are produced by the Vite plugin,
configured with `formVariants: true` in `vite.config.ts`. The bare artisan
command regenerates the same directory **without** them — so it removed the
helpers the starter kit's own profile page was already importing. Success
message, green exit code, broken build waiting to be discovered.

**Fixed:** regenerated through `pnpm build`, which runs the plugin. The lesson
generalises: when a tool is wired into a build pipeline with configuration,
running its underlying command directly discards that configuration.

**Commit:** `05a15c5`

---

## 11. update() silently dropped a guarded attribute

**Generated:** `$inquiry->update($validated)` in the inquiry status controller,
which is the ordinary way to apply validated input.

**Why it was wrong:** `status` is deliberately outside `#[Fillable]` on
`Inquiry`, precisely so no request body can set it. Mass assignment therefore
discarded it — without an error, without a log line, with a success redirect and
a flash message saying the inquiry had been updated. The endpoint reported doing
something it had not done.

**Fixed:** `forceFill($validated)->save()`, which is explicit about bypassing
the guard for a value the controller has already validated itself. Found by a
test asserting the status actually changed, rather than asserting the response
was a redirect.

**Commit:** `05a15c5`


---

## 12. Vendor onboarding that would have installed the wrong architecture

**Generated:** nothing — this one arrived as a command from Neon's console,
addressed to the agent: `neon init --agent --data '{"step":"getting-started",
"framework":"next","features":["database","auth"]}'`. It is the sanctioned path,
it comes from the vendor, and running it verbatim is the obvious move.

**Why it was wrong:** its payload describes a Next.js application that owns a
database and handles its own authentication. This project is the opposite on
both counts. Running the flow to completion would have written `DATABASE_URL`
into the Next.js environment, installed `@neondatabase/serverless` into
`studio-ops-web`, and then continued to a Neon Auth setup step — giving the
application a database client it must not have, next to a second authentication
system nobody would use, in the repository whose own `CLAUDE.md` says in the
first paragraph that it holds no database and no business logic.

Nothing would have errored. The result would have been a codebase quietly
contradicting its own documented architecture.

**Fixed:** ran the first three steps, which are architecture-neutral — pick the
organisation, pick the project, record the ids in `.neon`. Then stopped, and did
the rest by hand against the application that actually owns the database: parsed
the connection string into `DB_*` for Laravel, created the test database,
migrated and seeded, and verified from PHP over TLS. Skipped the driver install
and the Neon Auth step entirely.

The general shape is worth keeping: an instruction being official does not make
it correct for a given codebase. This one was well-made and still wrong here,
because it could not know what this project is.

**Commit:** `8c5441a`


---

## 13. Four deploy failures, three wrong diagnoses, one log

The first real build of the Dockerfile failed. Rather than read the log, the
next two changes were reasoned from the code: the base image was switched from
Alpine to Debian on the theory that the starter kit's glibc-only native
binaries could not load on musl, and Composer was copied in explicitly on the
theory that it might be missing.

Both changes were correct. Both explanations were wrong.

The build log said, in one line each time:

1. `composer install` exited **127** — Composer was not in the Alpine image at
   all, so the musl theory was never even reached
2. `The zip extension and unzip/7z commands are both missing` — Composer cannot
   unpack `--prefer-dist` archives without it
3. `exec: frankenphp: Operation not permitted` — the image gives the binary
   `cap_net_bind_service`, and `execve` on a file carrying capabilities fails
   with EPERM on a host that drops them
4. `No open ports detected on 0.0.0.0` — `frankenphp run` without `--config`
   starts Caddy with an empty configuration: the admin endpoint comes up on
   localhost and nothing ever serves the site

The fourth was self-inflicted. `--config` was in the first version of the
entrypoint and was dropped while tidying it.

**What this is actually about:** the model had access to the logs the whole
time and guessed twice before reading them. Guessing produced changes that
happened to be right, which is worse than being wrong — it looked like the
method was working. Reading the log took one API call and gave an exact answer
each time. That is the lesson, not the four individual fixes.

**Commit:** `b7e817c` and the three before it

---

## 14. Every pasted value carried a trailing newline

**Generated:** nothing. This came from the platform's own Blueprint form, whose
fields are textareas.

**Why it mattered:** all seven values entered by hand arrived with a trailing
`\n`. The ones declared in `render.yaml` were clean. The container reached
`config:cache` and died on `Invalid URI: A URI cannot contain CR/LF/TAB
characters`, which names the symptom but not the cause, and does not mention
which variable.

`APP_URL` was only the first casualty. `DB_HOST` would not have resolved and
`STUDIO_API_KEY` would not have matched the value on the other side — a
mismatch that presents as a plain 401 with nothing pointing at whitespace.

**Fixed:** read every variable back through the API, compared each against its
own `strip()`, and rewrote the seven that differed. Worth keeping as a habit:
after entering configuration by hand, read it back and diff it against what
was intended, rather than trusting that the form stored what was typed.

**Commit:** environment fix, no code change
