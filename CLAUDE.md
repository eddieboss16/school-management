# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 12 / PHP 8.2+ school management system (Kenyan school context — KES fees, M-Pesa payments, admission numbers). Blade + Tailwind 3 + Alpine.js, no SPA layer. `DOCUMENTATION.md` (1000 lines) is the authoritative functional spec and explains most design decisions in depth — read the relevant section before changing fees, report cards, enums, or the queue.

## Commands

```bash
composer dev            # server + queue:listen + pail logs + vite, all concurrently
php artisan serve       # server only
npm run dev             # vite only

php artisan test                                  # full suite (Pest); 162 tests currently pass
php artisan test tests/Feature/FeeBalanceTest.php # single file
php artisan test --filter="teacher can enter grades for their own class"
composer test           # config:clear then artisan test

php artisan migrate && php artisan db:seed   # grades 1-9, streams A-C, subjects, admin@school.com / Admin@1234
./vendor/bin/pint       # formatter (laravel preset per .styleci.yml; no_unused_imports disabled)
npm run build
```

Tests run on in-memory SQLite (forced in `phpunit.xml`) regardless of the `.env` DB. Local `.env` points at MySQL `school_management`; `database/database.sqlite` also exists but is not what tests use. No CI is configured.

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
- Paginated lists append `withQueryString()` so term/grade filters survive page changes.

## Fees model

`FeeStructure` rows are per term; `grade_id = null` means it applies to every grade, otherwise grade-specific. A student owes `sum(global) + sum(their grade)` for the term, minus `FeePayment` totals. `payment_method` is a plain string (`cash|mpesa|bank`) constrained only by controller validation, intentionally not a DB enum.

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

## Known rough edges

- `computeStreamRank()` is duplicated in `ReportCardController` and `Parent\DashboardController`; keep them in sync.
- Admission-number generation (`Admin/StudentController::store`) parses the last 3 digits of the previous `STD{year}###`, so it breaks at 1000 students in one year.
