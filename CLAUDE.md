# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 12 / PHP 8.2+ school management system (Kenyan school context — KES fees, M-Pesa payments, admission numbers). Blade + Tailwind 3 + Alpine.js, no SPA layer. `DOCUMENTATION.md` (1000 lines) is the authoritative functional spec and explains most design decisions in depth — read the relevant section before changing fees, report cards, enums, or the queue.

## Commands

```bash
composer dev            # server + queue:listen + pail logs + vite, all concurrently
php artisan serve       # server only
npm run dev             # vite only

composer test           # ← USE THIS. config:clear, then artisan test. See the warning below.
php artisan test                                  # full suite (Pest); 251 tests currently pass
php artisan test tests/Feature/FeeBalanceTest.php # single file
php artisan test --filter="teacher can enter grades for their own class"

php artisan migrate && php artisan db:seed   # grades 1-9, streams A-C, subjects, admin@school.com / Admin@123
./vendor/bin/pint       # formatter (laravel preset per .styleci.yml; no_unused_imports disabled)
npm run build
```

> ## ⚠️ Never run `php artisan test` while the config is cached — it drops the dev database
>
> `phpunit.xml` forces the test DB to SQLite `:memory:` using **env vars** (`DB_CONNECTION`, `DB_DATABASE`). **A cached config never consults env.** So with `bootstrap/cache/config.php` present, the suite silently resolves to the real MySQL dev database — verified inside a running test:
>
> ```
> config cache present       : YES
> config('database.default') : mysql               ← not sqlite
> database name              : high_school_management   ← the real dev DB
> $_ENV['DB_CONNECTION']     : sqlite              ← set, and ignored
> ```
>
> Almost every Feature test uses `RefreshDatabase`, which **drops every table** before migrating. Running the suite in that state destroys the dev database with no warning and no error — it looks like a normal green run.
>
> **Rules:**
> 1. **Use `composer test` locally, never `php artisan test` directly** — `composer test` runs `config:clear` first, and that is a safety guard, not a style choice.
> 2. `php artisan test` is only safe if you have *just* run `config:clear` yourself.
> 3. **Config caching stays OFF for local dev.** Cache only for a production deploy or a deliberate perf-testing session, and `config:clear` immediately afterwards.
>
> Same root cause as the `.env` trap below: once config is cached, `.env` and env vars stop being read at all.

Tests run on in-memory SQLite (forced in `phpunit.xml`) regardless of the `.env` DB — **but only while config is not cached** (see the warning above). No CI is configured.

Two traps around the dev database:

- Local `.env` points at MySQL **`high_school_management`** — *not* `school_management`, which the repo name and this file previously implied. If `artisan migrate` reports "Nothing to migrate" against a database you can see is empty, you are looking at the wrong schema; `env('DB_DATABASE')` is the authority. (Laravel's dotenv is immutable, so a real OS env var of the same name silently wins over `.env` too.)
- `database/database.sqlite` exists but is a **stale stub**: 3 of 21 migrations applied, no `enrollments`/`student_grades` tables, zero rows. It is neither what tests use nor a working dev database — do not point anything at it expecting it to work.

## Config and route caching

```bash
php artisan config:cache && php artisan route:cache   # required before any production deploy
php artisan config:clear  && php artisan route:clear  # undo, and see the trap below
```

**Required before any production deploy.** Without it every request re-parses ~20 config files and re-registers every route. Measured on this project through `php artisan serve` (median of 9 warm requests, identical data):

| Page | uncached | cached | delta |
|---|---|---|---|
| `/login` (302) | 241 ms | 167 ms | **−31%** |
| `/admin/dashboard` | 232 ms | 195 ms | −16% |
| `/admin/reports/student/{id}` | 240 ms | 195 ms | −19% |
| `/admin/reports` | 217 ms | 194 ms | −11% |
| `/admin/fees/balances` | 283 ms | 254 ms | −10% |

Framework bootstrap alone drops from ~47 ms to ~10–18 ms. It does **not** fix everything: the Composer autoloader is ~60–100 ms of the remaining cost and is unaffected, and `php artisan serve` is single-threaded regardless (see below).

> **Trap 1: once config is cached, `.env` is no longer read.** Laravel loads `bootstrap/cache/config.php` and ignores the file entirely, so editing `.env` — DB name, `APP_DEBUG`, mail, queue — silently changes nothing until you run `php artisan config:clear`. A wrong-database or stale-debug symptom that survives an obviously-correct `.env` edit is this, every time.

> **Trap 2 — the destructive one:** a cached config also sends `php artisan test` at the MySQL dev database, where `RefreshDatabase` drops every table. This is the ⚠️ warning under [Commands](#commands) — read it there.

**Therefore: leave config caching OFF locally.** It is a deploy step. If you cache locally to measure something, clear it again immediately afterwards.

Verify the current state with `php artisan about --only=cache`, which reports `Config` and `Routes` as `CACHED` / `NOT CACHED`.

## Serving locally through Apache instead of `artisan serve`

`php artisan serve` is PHP's built-in server: **single-threaded, one request at a time.** Measured: 6 parallel requests to `/admin/dashboard` took 1.56 s wall versus 1.99 s sequentially — essentially no concurrency. Fine for ordinary work, useless for anything touching parallel requests, locking, or session contention.

XAMPP's Apache serves the same code properly. Appended to `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (already included by `httpd.conf`; a timestamped `.bak` of the original sits beside it):

```apache
Listen 8080

<VirtualHost *:8080>
    ServerName school.localhost
    DocumentRoot "c:/dev/school-management-system/public"

    <Directory "c:/dev/school-management-system/public">
        Options Indexes FollowSymLinks
        AllowOverride All          # Laravel's public/.htaccess owns the rewrite; without All every route 404s
        Require local              # dev only: loopback connections only
    </Directory>

    ErrorLog  "logs/school-management-error.log"
    CustomLog "logs/school-management-access.log" combined
</VirtualHost>
```

Port **8080**, so the default XAMPP site on `:80` is untouched and no hosts-file entry is needed — browse `http://localhost:8080/`. Validate with `C:\xampp\apache\bin\httpd.exe -t` before restarting.

### XAMPP shipped with OPcache completely unloaded — now enabled

Worth knowing because it made the vhost look like a bad idea. XAMPP's `php.ini` had **no `zend_extension=opcache` line at all** (not merely `opcache.enable=0` — the extension was never loaded), so Apache recompiled every PHP file on every request: ~3349 files for this project. `C:\php84`, which the CLI and `artisan serve` use, has OPcache on by default, so the two front doors were not comparable.

Measured before the fix: **Apache was 2.23× slower per request** than `artisan serve` (769 ms vs 345 ms), which made it the worse option despite handling concurrency better.

Fixed by appending an OPcache block to the **end** of `C:\xampp\php\php.ini` (later directives win, so the commented-out defaults higher up are left untouched; a timestamped `.bak` sits beside the file). `opcache.validate_timestamps=1` + `revalidate_freq=0` so edited files are picked up immediately — do not tighten those on a dev box. **Apache must be restarted for `php.ini` changes to load.**

After enabling, measured interleaved (requests alternated between the two servers so machine-wide drift hits both equally):

| Page | artisan serve | Apache | ratio |
|---|---|---|---|
| `/login` | 1974 ms | 1414 ms | **0.72×** |
| `/admin/dashboard` | 1486 ms | 1445 ms | **0.97×** |
| report card HTML | 1413 ms | 1596 ms | **1.13×** |

The 2.23× penalty is gone — roughly parity, and Apache now wins on concurrency outright:

| Server | 6 seq | 6 par | speedup | 12 seq | 12 par | speedup |
|---|---|---|---|---|---|---|
| `artisan serve` | 13889 ms | 11440 ms | 1.21× | 27340 ms | 27831 ms | **0.98× — none** |
| Apache :8080 | 11776 ms | 8088 ms | 1.46× | 22760 ms | 15527 ms | **1.47×** |

At 12 concurrent requests `artisan serve` gets *no* benefit from parallelism at all, while Apache holds ~1.47×.

> Those absolute numbers are inflated ~8× versus the earlier figures in this file: Windows Defender and SearchIndexer were saturating the CPU during that run. **Only the ratios from that table are meaningful** — always re-measure both servers in the same window rather than comparing against a number recorded earlier, and check `MsMpEng` CPU before trusting any timing on this box.

Two more things to know:

- **Apache here is not a Windows service** — it is started by `xampp-control.exe`, so `httpd -k restart` fails with `No installed service named "Apache2.4"`. Restart it from the XAMPP Control Panel.
- **Apache runs XAMPP's own PHP 8.2.12**, not the `C:\php84` (8.4.23) binary the CLI and `artisan serve` use. Both clear the project's 8.2+ floor, but a version-sensitive bug can appear under one and not the other — check which PHP served a page before chasing it.
- Restarting the XAMPP stack restarts **MySQL** too. Requests issued while it is still coming up fail with `No application encryption key has been specified` logged as `production.ERROR` — misleading, but transient. Give MySQL a few seconds before measuring anything.

## Domain vocabulary (easy to get wrong)

- **`Grade`** = a grade *level* (Grade 1–9), not a score. **`StudentGrade`** = one assessment score for one student.
- **`SchoolClass`** maps to the `classes` table and is a *(grade, stream, subject, teacher)* tuple — i.e. one "class" per subject, not a homeroom. Students join via the `enrollments` pivot (`class_id` + `student_id`, unique).
- **`Stream`** = a section within a grade (A/B/C). A student's grade level is reached through `stream.grade` — there is no `grade_id` on `users`.
- All four roles live in the single `users` table, distinguished by the `usertype` enum (`admin`/`teacher`/`student`/`parent`). Parent↔child is the self-referential `parent_id` on `users`.

## Access control

- Route protection is entirely `role:{name}` middleware (`CheckRole`, aliased in [bootstrap/app.php](bootstrap/app.php)). A mismatched role is redirected to *its own* dashboard, never shown a 403. `AdminMiddleware` is registered as `admin` but unused — use `role:admin`.
- **Record-level authorization lives in Policies** ([app/Policies/](app/Policies/)). New code in these areas calls `$this->authorize()` — do not reintroduce manual `where()` scoping:
  - `ClassPolicy` (`view` for reads, `update` for writes) — teacher owns class. Used by `GradeController` and `AttendanceController`: `$class = SchoolClass::findOrFail($id); $this->authorize('view', $class);`
  - `StudentPolicy` (`view`) — parent owns child. Backs `authorizeChild()` in [Parent/DashboardController.php](app/Http/Controllers/Parent/DashboardController.php).
  - Both are registered explicitly with `Gate::policy()` in [AppServiceProvider](app/Providers/AppServiceProvider.php); auto-discovery looks for `SchoolClassPolicy`/`UserPolicy`, so the binding must stay manual. There is no `AuthServiceProvider` (Laravel 12 dropped it). The base `Controller` had to be given `AuthorizesRequests` — Laravel 12 ships it bare.
- **Policy denials are 404, not 403.** Both policies deny via `Response::denyAsNotFound()`, preserving the response shape of the scoped `findOrFail()` they replaced so a caller cannot distinguish "not yours" from "does not exist". A plain `deny()` would flip every one of these to 403 and leak record existence; `PolicyTest.php` asserts the denial *status* for exactly this reason.
- **Admin controllers are the deliberate exception** — no record-level scoping, because admins reach every record and `role:admin` is the gate. This is confirmed not-applicable, not a gap. Their `usertype` guards (`if ($x->usertype !== 'student') return redirect()->…->with('error', …)`) are input validation returning 302 + flash; converting them to policies would change that response shape, which no test currently covers.
- **Teacher writes also check enrollment**, not just class ownership: `GradeController::store` and `AttendanceController::store` reject any submitted `student_id` absent from the class's `enrollments` (whole batch rejected, via a closure rule inside the existing `$request->validate()`). Owning the class is not sufficient — without this a teacher can write rows onto another class's student, and report cards select on `student_id` alone.
- Query scoping is still correct for *list* filters (e.g. `Teacher\DashboardController`'s `where('teacher_id', …)->get()`); policies apply to single-record access.
- The route named `dashboard` is the **student** dashboard (Breeze convention repurposed); admin/teacher/parent use `admin.dashboard`, `teacher.dashboard`, `parent.dashboard`. `AuthenticatedSessionController` `match()`es on `usertype` to pick the landing page.
- Public self-registration is removed from `routes/auth.php` (a test asserts the `register` route does not exist) even though `RegisteredUserController` and its view still exist. Only `/parent/register` is open, and it refuses a student who already has a `parent_id`.
- Validation is inline `$request->validate()` in controllers; Form Requests are only used by the Breeze scaffolding.

## Cross-cutting invariants

- **Term scoping**: grades, attendance, fee structures, and payments all carry `term_id`, defaulted from `Term::activeTerm()`. Any new academic query should filter by term or it will mix years together.
- **Soft deletes** on `User`, `StudentGrade`, `Attendance`. Assertions must check `->fresh()->deleted_at`, not `assertNull($model->fresh())`.
- **Audit log**: admin CRUD on students, teachers, fee structures, and payments calls `ActivityLog::record($action, $modelType, $id, $description)`. Attendance marking and grade entry are deliberately *not* logged (volume). Match this when adding admin CRUD.
- **Notifications** (`GradesPostedNotification`, `StudentAbsentNotification`) implement `ShouldQueue` and are sent to `$student->parent` only when a parent with an email exists — silently skipped otherwise. Only `absent`/`late` trigger the attendance email. `QUEUE_CONNECTION=sync` locally; production uses `database` + `supervisor.conf`.
- **No N+1 in list views**: controllers eager-load (`with('stream.grade')`) and the fee balance pages aggregate with one `SUM(amount) GROUP BY student_id` query plus an in-memory grade→amount map instead of calling `FeeStructure::forStudent()`/`FeePayment::totalPaid()` per student. Preserve that shape when touching [Admin/FeesController.php](app/Http/Controllers/Admin/FeesController.php) — the per-student helpers are for single-student pages and tests only.
- Two further N+1s were found by query-count scaling and fixed; both were in the *view*, not the controller, which is where they hide in this codebase:
  - [EnrollmentController](app/Http/Controllers/EnrollmentController.php) eager-loads `students.stream.grade`, not just `students` — [enrollments.blade.php](resources/views/admin/enrollments.blade.php) prints each student's grade and stream, so a bare `students` load fired a `streams` + `grades` lookup per enrolled student (273 queries at 16 students, now 63 and flat). This page is **not paginated**, so it grew without bound.
  - [Admin/ClassController](app/Http/Controllers/Admin/ClassController.php) uses `withCount('students')` and [classes.blade.php](resources/views/admin/classes.blade.php) reads `$class->students_count`; it previously hydrated every student model per row just to call `->count()` (54 queries at 12 classes, now 18 and flat).
  - The way to check this is to hold everything constant and scale one axis: if the query count moves with row count, it is an N+1. Counts that are *high but flat* (report card is 102) are a different problem — repeated identical lookups, not N+1.
- Paginated lists append `withQueryString()` so term/grade filters survive page changes.

## Fees model

`FeeStructure` rows are per term; `grade_id = null` means it applies to every grade, otherwise grade-specific. A student owes `sum(global) + sum(their grade)` for the term, minus `FeePayment` totals. `payment_method` is `App\Enums\PaymentMethod` (`cash|mpesa|bank`), cast on the model and **enforced by the database**, not by controller validation — a seeder, command, or job that writes `FeePayment` directly gets a `QueryException`, not a silent bad row. On MySQL/MariaDB the column is an `ENUM` *and* carries an explicit `fee_payments_payment_method_check`: an ENUM alone only errors under a strict `sql_mode` (set from `config/database.php`, i.e. app config), and coerces to `''` without it. Adding a method means editing the enum *and* writing a migration. The enum also owns `label()` and `badgeClass()`, so the admin/student/parent fee tables cannot drift — `$payment->payment_method` is an enum instance, so it never compares equal to a string and `ucfirst()` on it is a `TypeError`.

## PDFs

`barryvdh/laravel-dompdf`. PDF output uses dedicated templates ([reports/report-card-pdf.blade.php](resources/views/reports/report-card-pdf.blade.php), [admin/fees/receipt-pdf.blade.php](resources/views/admin/fees/receipt-pdf.blade.php)) separate from the on-screen views — update both when changing report content. CSV exports (grades, fee balances, attendance) are streamed via `StreamedResponse`.

## Views

`resources/views/{admin,teacher,student,parent,reports,auth}/` mirrors the controller namespaces; flat files named `students-create.blade.php` style rather than nested resource folders. Layouts come from Breeze (`layouts/app`, `layouts/guest`, `x-` components in `views/components/`).

## Grading scale

KCSE 12-point (`A` 80+, `A-` 75, `B+` 70, `B` 65, `B-` 60, `C+` 55, `C` 50, `C-` 45, `D+` 40, `D` 35, `D-` 30, `E` below). **There is no `F`** — `E` is the fail grade. Boundaries and points live in [config/grading.php](config/grading.php); nothing should compare a percentage against a literal threshold.

- Everything goes through [App\Support\Grading](app/Support/Grading.php): `letter()`, `points()`, `badgeClass()`, `textClass()`, `pdfBadgeClass()`. `GradesPostedNotification` uses it too — the emailed letter must equal the report-card letter, and a test asserts that at every boundary.
- **Colour is keyed to the base letter**, not to a separate percentage cutoff: A green, B blue, C yellow, D orange, E red. The same tiers exist as `.grade-a`…`.grade-e` in the PDF stylesheet, so a badge prints the colour it displays. `A-` is green because it is A-tier, not because it clears 80.
- Those Tailwind classes are returned from PHP at runtime, so `./app/**/*.php` is in `tailwind.config.js` `content`. Drop it and the utilities get purged from a production build while still looking fine in dev.
- The grade-entry form colours scores client-side from `Grading::scaleForJs()`, so the JS cannot drift from the server.

## Shared calculations

Logic that more than one surface reports lives in `app/Support/` — the two entries below were each duplicated or hand-rolled per controller before, and both had drifted or broken in ways nothing caught.

- **[StreamRank](app/Support/StreamRank.php)** — a student's position in their stream. `ReportCardController` and `Parent\DashboardController` both call `StreamRank::forStudent($student, $termId)`; neither keeps a private copy, and `StreamRankTest` asserts the admin, student, and parent report cards return the same position for the same student.
- **[AdmissionNumber](app/Support/AdmissionNumber.php)** — `STD{year}{sequence}`, claimed from the `admission_sequences` counter inside a locked transaction. Never derive one by reading the highest existing student: that broke at the 999→1000 boundary, reissued soft-deleted students' numbers, and gave two simultaneous admissions the same value. `users.admission_number` is uniquely indexed, so a mistake here is a duplicate-key 500.

## Known rough edges

- `email_verified_at` is **not** in `User::$fillable`, so mass assignment silently drops it. `Admin/StudentController::store` passes it to `User::create()` and it is discarded — every admin-created student has a null `email_verified_at`. This is currently inert: `User` does not implement `MustVerifyEmail` (the import is commented out), so the `verified` middleware on `/dashboard` waves everyone through. It turns into a lockout for every existing student the moment anyone implements that interface. Assign it on the model and `save()` if you actually need it set.
