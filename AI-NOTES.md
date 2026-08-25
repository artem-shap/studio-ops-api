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
