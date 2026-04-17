# School Management System — Full Documentation

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Technology Stack](#2-technology-stack)
3. [System Architecture](#3-system-architecture)
4. [User Roles](#4-user-roles)
5. [Authentication & Access Control](#5-authentication--access-control)
6. [Core Features](#6-core-features)
   - [Admin Features](#61-admin-features)
   - [Teacher Features](#62-teacher-features)
   - [Student Features](#63-student-features)
   - [Parent Features](#64-parent-features)
7. [Database Models & Relationships](#7-database-models--relationships)
8. [Enums — What They Are, Why We Use Them](#8-enums--what-they-are-why-we-use-them)
9. [The Fee System Explained](#9-the-fee-system-explained)
10. [The Report Card System Explained](#10-the-report-card-system-explained)
11. [Notifications & Queue System](#11-notifications--queue-system)
12. [Audit / Activity Log](#12-audit--activity-log)
13. [Soft Deletes — What They Are and Why](#13-soft-deletes--what-they-are-and-why)
14. [Performance Design Decisions](#14-performance-design-decisions)
15. [Testing](#15-testing)
16. [Deployment & Environment Configuration](#16-deployment--environment-configuration)
17. [How to Use the System — Role by Role](#17-how-to-use-the-system--role-by-role)

---

## 1. System Overview

The School Management System is a web-based application built to digitise the day-to-day academic and administrative operations of a school. It replaces paper registers, manual report cards, and disconnected spreadsheets with a single platform that all stakeholders — administrators, teachers, students, and parents — can access from any browser.

**The system handles:**

- Student and teacher account management
- Class organisation (grades, streams, subjects)
- Daily attendance tracking per class
- Academic grading and assessment recording
- Automated report card generation (HTML + PDF)
- School fee management — structure definition, payment recording, balance tracking
- Parent access to their child's records
- Email notifications to parents when a child is absent or grades are posted
- A full audit trail of every administrative action

---

## 2. Technology Stack

| Layer | Technology | Version | Why |
|---|---|---|---|
| Backend Framework | Laravel | 12.x | Industry-standard PHP framework with built-in auth, ORM, queues, and mail |
| Language | PHP | 8.2+ | Required by Laravel 12; supports enums, readonly properties, nullsafe operator |
| Database | MySQL | 8.x (production) / SQLite (tests) | MySQL for production reliability; SQLite for fast, zero-config testing |
| Frontend Styling | Tailwind CSS | 3.x | Utility-first CSS — fast to write, consistent output |
| Frontend Interactivity | Alpine.js | 3.x | Lightweight JS for dropdowns and live calculations without a full framework |
| Auth Scaffolding | Laravel Breeze | — | Provides login, logout, password reset out of the box |
| PDF Generation | barryvdh/laravel-dompdf | — | Converts Blade views to PDF using Dompdf; no external service needed |
| Testing | Pest | 3.x | Modern PHP test framework built on top of PHPUnit; cleaner syntax |
| Queue Driver | database (production) / sync (local) | — | Offloads email sending from the request cycle |

---

## 3. System Architecture

The system follows the standard **MVC (Model-View-Controller)** pattern that Laravel enforces.

```
Request → Route → Middleware → Controller → Model → Database
                                    ↓
                                  View → Response
```

**Directory layout of significance:**

```
app/
  Http/
    Controllers/
      Admin/        ← All admin-facing logic
      Teacher/      ← Teacher dashboard + attendance/grades
      Student/      ← Student dashboard + self-service views
      Parent/       ← Parent child-monitoring views
      Auth/         ← Login, registration, password reset
    Middleware/
      CheckRole.php ← Enforces role-based access on every protected route
  Models/           ← One class per database table
  Notifications/    ← Email notification classes (absence + grades)

database/
  migrations/       ← Every table definition, in order
  factories/        ← Test data generators for every model
  seeders/          ← Initial data (grades, admin user)

resources/views/
  admin/            ← All admin Blade templates
  teacher/          ← Teacher templates
  student/          ← Student templates
  parent/           ← Parent templates
  reports/          ← Report card HTML + PDF templates
  auth/             ← Login, parent registration

tests/
  Feature/          ← HTTP-level tests covering all critical flows
```

---

## 4. User Roles

The system has exactly **four roles**. Every user account belongs to one of them.

| Role | Who | What they can do |
|---|---|---|
| `admin` | School administrator | Full access — manage all users, classes, fees, reports, terms, activity log |
| `teacher` | Class teacher | Mark attendance, enter/edit grades for their assigned classes only |
| `student` | Enrolled student | View own dashboard, attendance, grades, report card, fees |
| `parent` | Parent or guardian | View their linked child's grades, attendance, report card, fees |

**Why only four?** A role system only earns its complexity if each role actually needs different data access. More roles would mean more middleware rules, more view conditionals, and more maintenance surface for no real benefit at this school scale.

---

## 5. Authentication & Access Control

### Login Flow

Every user logs in through the same `/login` form. After authentication, the system reads their `usertype` field and redirects accordingly:

```
admin   → /admin/dashboard
teacher → /teacher/dashboard
student → /dashboard
parent  → /parent/dashboard
```

This is handled in `AuthenticatedSessionController` using a `match()` expression on `Auth::user()->usertype`.

### The CheckRole Middleware

Every protected route is wrapped with `role:{rolename}` middleware. Example from routes:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Only admins reach here
});
```

The `CheckRole` middleware (`app/Http/Middleware/CheckRole.php`) does two things:

1. Checks if the user is logged in at all — if not, redirects to `/login`
2. Checks if their `usertype` matches the required role — if not, redirects them to **their own** dashboard with an "Access denied" message

This means a student who tries to manually type `/admin/dashboard` in their browser gets silently redirected to `/dashboard`. There is no error page exposed, no system information leaked.

### Parent Registration

Parents register through a separate route: `/parent/register`. They must provide:
- Their name, email, and password
- Their child's **admission number**

The system looks up the student by admission number and links the `parent_id` on the student record to the newly created parent account. If the student already has a parent linked, registration is blocked — preventing account hijacking.

---

## 6. Core Features

### 6.1 Admin Features

**Dashboard**
Displays live counts of total users, students, teachers, streams, subjects, and classes. Quick-navigation cards link to every major section.

**Manage Students** (`/admin/students`)
- Create students with auto-generated admission numbers (`STD{YEAR}001`, `STD{YEAR}002`, etc.) or manual entry
- Edit name, email, stream assignment, admission number, password, and parent link
- Soft-delete students (record is preserved, not permanently removed)
- Paginated list with stream/grade display

**Manage Teachers** (`/admin/teachers`)
- Create teacher accounts
- Edit and soft-delete teachers

**Manage Streams & Grades** (`/admin/streams`)
- Streams are subdivisions of a grade (e.g., Grade 7 North, Grade 7 South)
- Each stream has a capacity
- Students are assigned to a stream on creation or via edit

**Manage Subjects** (`/admin/subjects`)
- Define all subjects taught at the school
- Subjects are then attached to classes

**Manage Classes** (`/admin/classes`)
- A class is the intersection of: Grade + Stream + Subject + Teacher
- Example: "Grade 8 East — Mathematics — Mr. Kamau"
- Has a unique class code (e.g., `G8-EAST-MATH`)
- Enrollment management: add/remove individual students, or bulk-enroll an entire stream

**Manage Terms** (`/admin/terms`)
- Define school terms/semesters (e.g., "Term 1 2026")
- Set start and end dates
- Mark one term as **active** — the active term is used as the default when recording attendance, grades, and fees
- Only one term can be active at a time (enforced at the controller level)
- Cannot delete an active term

**Fee Structures** (`/admin/fees/structures`)
- Define fees applicable for a term
- A fee can be **global** (applies to all grades) or **grade-specific**
- Example: "Tuition Fee — KES 15,000 — all grades" and "Lab Fee — KES 2,000 — Grade 8 only"
- Edit and delete structures

**Fee Balances** (`/admin/fees/balances`)
- View all students' expected fees, amount paid, and outstanding balance for any term
- Filter by grade, stream, and status (outstanding / cleared)
- Paginated (50 per page)
- Export full list as CSV
- Summary cards show total expected, total collected, and total outstanding for the visible set

**Individual Student Fees** (`/admin/fees/student/{id}`)
- Full fee breakdown for one student
- Record a payment (amount, date, method: cash/M-Pesa/bank, reference number, notes)
- View payment history
- Download a PDF receipt for any payment
- Delete a payment record

**Report Cards** (`/admin/reports`)
- List all students with links to view or download their report card
- Admin can view any student's report card filtered by term
- Download as PDF with school branding

**Activity Log** (`/admin/activity-log`)
- Paginated log of every admin action: who did what, to which record, and when
- Actions include: created, updated, deleted for students, teachers, fee structures, payments

---

### 6.2 Teacher Features

**Dashboard** (`/teacher/dashboard`)
- Welcome with the teacher's name
- Count of assigned classes and total students across all classes
- List of all assigned classes with quick-action buttons

**Mark Attendance** (`/teacher/classes/{id}/attendance/mark`)
- One student per row with a status selector: Present / Absent / Late / Excused
- Pre-fills today's date (can be changed for re-marking)
- If attendance was already marked for today, the existing records are deleted and replaced
- On submit, parents of absent/late students receive an email notification automatically
- Stamped with the active term's ID

**Attendance History** (`/teacher/classes/{id}/attendance/history`)
- All attendance records for the class, grouped by date
- Full list — no arbitrary 30-day cutoff

**Enter Grades** (`/teacher/classes/{id}/grades/enter`)
- Assessment type (free text: "Midterm Exam", "CAT 1", etc.)
- Assessment date and max score
- Score input for every enrolled student
- Live percentage calculation per student (JavaScript, no page reload)
- "Apply to All" bulk fill — enter one score and distribute to all students
- Running class average displayed at the top
- On submit, parents of each student receive an email notification with the score and letter grade

**View Grades** (`/teacher/classes/{id}/grade`)
- All assessments grouped by assessment type
- Edit or delete an entire assessment batch
- Export grades as CSV (student name, admission number, assessment, score, percentage)

---

### 6.3 Student Features

**Dashboard** (`/dashboard`)
- Welcome with name, admission number, and class
- Attendance rate (present count, absent count, late count, percentage)
- List of enrolled classes with subject, teacher, and classmate count
- Quick links to attendance, grades, report card, fees

**Attendance History** (`/student/attendance`)
- Full paginated history of all attendance records across all classes
- Shows subject, date, teacher, status

**Grades** (`/student/grades`)
- All assessments grouped by class
- Score, max score, percentage, and date for each

**Report Card** (`/student/report-card?term_id={id}`)
- Term selector — defaults to the active term
- Subject averages table with letter grades
- Overall average
- Attendance summary for the selected term
- Download as PDF (includes selected term in the filename)

**Fees** (`/student/fees`)
- Term-filtered view of applicable fees and total expected
- Payment history
- Current balance

---

### 6.4 Parent Features

**Registration** (`/parent/register`)
- Provide name, email, password, and child's admission number
- System links parent to child automatically

**Dashboard** (`/parent/dashboard`)
- Lists all linked children
- Quick links for each child: View Grades, View Attendance, View Report Card, View Fees

**Child Grades** (`/parent/child/{id}/grades`)
- All grades grouped by class — identical data to what the student sees

**Child Attendance** (`/parent/child/{id}/attendance`)
- Paginated attendance history

**Child Report Card** (`/parent/child/{id}/report-card?term_id={id}`)
- Full term-filtered report card with print button
- Term selector included

**Child Fees** (`/parent/child/{id}/fees`)
- Term-filtered fee breakdown, payment history, and balance

---

## 7. Database Models & Relationships

### User
The central model. One table holds all four role types.

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint | Primary key |
| `usertype` | enum | Role: `admin`, `teacher`, `student`, `parent` |
| `name` | string | Full name |
| `email` | string (unique) | Login credential |
| `password` | string (hashed) | bcrypt hash |
| `stream_id` | FK (nullable) | Which stream the student belongs to (null for admin/teacher/parent) |
| `admission_number` | string (nullable, unique) | Auto-generated or manual, students only |
| `parent_id` | FK → users (nullable) | Links a student to their parent account |
| `deleted_at` | timestamp (nullable) | Soft delete marker |

**Relationships:**
- `belongsTo(Stream)` — student's stream
- `hasMany(User, 'parent_id')` — parent's children
- `belongsTo(User, 'parent_id')` — student's parent
- `belongsToMany(SchoolClass, 'enrollments')` — student's enrolled classes
- `hasMany(SchoolClass, 'teacher_id')` — teacher's assigned classes
- `hasMany(FeePayment, 'student_id')` — student's fee payments

---

### Grade
Represents a year level (e.g., Grade 7, Grade 8).

| Column | Purpose |
|---|---|
| `name` | Display name: "Grade 7" |
| `level` | Category: "primary", "secondary" |
| `order` | Sort order for display |

**Relationships:** `hasMany(Stream)`, `hasMany(SchoolClass)`

---

### Stream
A subdivision of a grade (e.g., "North", "South", "A", "B").

| Column | Purpose |
|---|---|
| `grade_id` | FK → Grade |
| `name` | "North", "A", etc. |
| `capacity` | Max students |

**Relationships:** `belongsTo(Grade)`, `hasMany(User)` (students)

---

### Subject
An academic subject (e.g., Mathematics, English).

| Column | Purpose |
|---|---|
| `name` | Subject name |
| `code` | Short code (e.g., MATH101) |

---

### SchoolClass
The teaching unit — a specific subject taught to a specific stream by a specific teacher.

| Column | Purpose |
|---|---|
| `grade_id` | FK → Grade |
| `stream_id` | FK → Stream |
| `subject_id` | FK → Subject |
| `teacher_id` | FK → User (teacher) |
| `class_code` | Unique identifier (e.g., G8-NORTH-MATH) |
| `capacity` | Max students |

**Relationships:** `belongsTo` Grade, Stream, Subject, User(teacher). `belongsToMany(User, 'enrollments')` for students.

---

### Term
A school term or semester.

| Column | Purpose |
|---|---|
| `name` | "Term 1 2026" |
| `start_date` | Term start |
| `end_date` | Term end |
| `is_active` | boolean — only one term is active at a time |

**Key method:** `Term::activeTerm()` — static method that returns the currently active term. Used throughout the system wherever a term needs to be automatically applied (attendance, grades, fees).

---

### Attendance
One record per student per class per day.

| Column | Purpose |
|---|---|
| `class_id` | FK → SchoolClass |
| `student_id` | FK → User |
| `term_id` | FK → Term (nullable) |
| `date` | The date of attendance |
| `status` | enum: `present`, `absent`, `late`, `excused` |
| `notes` | Optional teacher note |
| `marked_by` | FK → User (teacher who submitted) |

**Unique constraint:** `(class_id, student_id, date)` — prevents duplicate records for the same student in the same class on the same day.

---

### StudentGrade
One record per student per assessment.

| Column | Purpose |
|---|---|
| `class_id` | FK → SchoolClass |
| `student_id` | FK → User |
| `term_id` | FK → Term (nullable) |
| `assessment_type` | Free text: "Midterm Exam", "CAT 1" |
| `score` | Decimal score achieved |
| `max_score` | Maximum possible score |
| `assessment_date` | When the assessment was given |
| `remarks` | Optional teacher comment |
| `entered_by` | FK → User (teacher) |

**Computed accessor:** `getPercentageAttribute()` — calculates `(score / max_score) * 100` on the fly. Not stored in the database, computed every time it is accessed. Guards against division by zero.

---

### FeeStructure
Defines what fees apply for a given term.

| Column | Purpose |
|---|---|
| `term_id` | FK → Term |
| `grade_id` | FK → Grade (nullable) — null means applies to ALL grades |
| `name` | "Tuition Fee", "Lab Fee" |
| `amount` | Decimal amount (KES) |
| `description` | Optional explanation |

**Key method:** `FeeStructure::forStudent(User $student, int $termId)` — returns all fee structures applicable to a specific student for a given term. Includes global fees (grade_id is null) plus any grade-specific fees matching the student's grade.

---

### FeePayment
One record per payment made.

| Column | Purpose |
|---|---|
| `student_id` | FK → User |
| `term_id` | FK → Term |
| `amount` | Amount paid |
| `payment_date` | Date of payment |
| `payment_method` | enum: `cash`, `mpesa`, `bank` |
| `reference_number` | Receipt or M-Pesa transaction code |
| `notes` | Optional admin note |
| `recorded_by` | FK → User (admin who recorded it, nullable) |

**Key method:** `FeePayment::totalPaid(int $studentId, int $termId)` — returns the sum of all payments made by a student for a term. Used in balance calculations.

---

### ActivityLog
Records every significant admin action for audit purposes.

| Column | Purpose |
|---|---|
| `user_id` | FK → User (who performed the action) |
| `action` | `created`, `updated`, `deleted` |
| `model_type` | What was affected: "Student", "FeePayment", etc. |
| `model_id` | The ID of the affected record |
| `description` | Human-readable summary |
| `changes` | JSON: before/after values for updates |

**Key method:** `ActivityLog::record(...)` — static method called throughout controllers whenever a significant action occurs.

---

## 8. Enums — What They Are, Why We Use Them

### What is an Enum?

An **enum** (enumeration) is a database column type that restricts the value to a fixed list of allowed options. Instead of a plain string column that could hold anything, an enum column enforces valid values at the database level.

In MySQL:
```sql
ENUM('option1', 'option2', 'option3')
```

In Laravel migrations:
```php
$table->enum('status', ['present', 'absent', 'late', 'excused']);
```

### Why Not Just Use a String?

If `status` were a plain `varchar`:
- A teacher could submit `"Presents"` (typo) — stored without error
- A query filtering `where status = 'present'` would silently miss that record
- Every piece of code that reads the status would need to handle unexpected values defensively

With an enum:
- The database **rejects** any value not in the list at insert time
- Every piece of code that reads the field can trust it's one of the four known values
- Reports, statistics, and `match()` expressions are safe to write without defensive fallbacks

### Enums Used in This System

---

#### `users.usertype`

```php
$table->enum('usertype', ['admin', 'teacher', 'student', 'parent'])->default('student');
```

**Why:** Every user must have exactly one role. This enum is the single source of truth for what a user can do. It is read by:
- `CheckRole` middleware — to allow or block route access
- `AuthenticatedSessionController` — to redirect after login
- Controllers — to query only students (`where usertype = 'student'`)
- Views — to show role-appropriate content

Without this enum, a rogue value like `"superuser"` or an empty string would silently bypass all role checks or cause the system to redirect the user to `/login` in an infinite loop.

**Default of `'student'`:** Chosen because if a user is ever created without an explicit type (e.g., by a direct database insert), it fails safe as the most restricted role rather than as an admin.

---

#### `attendances.status`

```php
$table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
```

**Why:** Attendance status is categorical — there is no value between "present" and "absent". These four states cover every possible scenario:

| Value | Meaning |
|---|---|
| `present` | Student was in class |
| `absent` | Student did not attend |
| `late` | Student attended but arrived after the class started |
| `excused` | Student was absent with prior authorisation (e.g., medical) |

This enum drives:
- **Statistics** on the student dashboard (`presentCount`, `absentCount`, `lateCount`)
- **Parent notifications** — only `absent` and `late` trigger an email, never `present` or `excused`
- **Report card attendance summary** — each status is counted separately
- **Attendance percentage** — calculated as `(present / total) * 100`; `late` and `excused` are excluded from "present" but included in "total"

**Why `excused` is separate from `absent`:** A student with 5 `absent` records and 5 `excused` records has a very different attendance story than one with 10 `absent` records. The school treats them differently for disciplinary and academic purposes.

---

#### `fee_payments.payment_method`

```php
$table->string('payment_method')->default('cash'); // cash, mpesa, bank
```

**Note:** This is stored as a string with three known values rather than a strict database enum. The reason is pragmatic — M-Pesa is the dominant payment method in Kenya and "bank" covers bank transfers. If the school adds a new method later (e.g., "cheque"), a string column can be updated without a migration that modifies an enum column on a large production table (which requires an ALTER TABLE in MySQL and can lock the table).

The three valid values are enforced at the **validation layer** in the controller:
```php
'payment_method' => ['required', 'in:cash,mpesa,bank'],
```

This gives the same protection as a database enum for new inserts, with easier extensibility.

---

### The Trade-off: Enum vs. Lookup Table

An alternative to enums is a **lookup table** — a separate `statuses` table that holds the valid options, with a foreign key reference. Lookup tables are better when:
- The list of options changes frequently
- Options need extra attributes (a description, a color code, a sort order)
- You need to let an administrator add new options through the UI

For this system, attendance statuses and user roles are **fixed by design** — the school isn't going to invent a fifth attendance state. Enums are simpler, faster to query, and self-documenting. A lookup table for four static values would be over-engineering.

---

## 9. The Fee System Explained

### The Problem It Solves

Fee management is one of the most error-prone tasks in a school. Manually tracking who has paid, how much, and what they owe across hundreds of students and multiple terms is where money goes missing and disputes happen. The system replaces that with a clear, auditable record.

### How Fees Are Structured

Fees are defined per term. An admin creates fee line items (called "structures") before a term begins:

```
Term: Term 1 2026
├── Tuition Fee        — KES 15,000  (all grades)
├── Activity Fee       — KES 2,000   (all grades)
├── Lab Fee            — KES 1,500   (Grade 8 only)
└── Technology Fee     — KES 1,000   (Grade 7 only)
```

A student in **Grade 8** for Term 1 2026 owes: 15,000 + 2,000 + 1,500 = **KES 18,500**
A student in **Grade 7** for Term 1 2026 owes: 15,000 + 2,000 + 1,000 = **KES 18,000**

The `FeeStructure::forStudent()` method handles this logic — it fetches all structures for the term where `grade_id IS NULL` (global) plus any where `grade_id = student's grade`.

### The N+1 Problem and How It Was Solved

**The wrong approach (what was there before):**
```php
foreach ($students as $student) {
    $fees = FeeStructure::forStudent($student, $termId); // 1 query per student
    $paid = FeePayment::totalPaid($student->id, $termId); // 1 query per student
}
// 300 students = 601 database queries on a single page load
```

**The right approach (what is there now):**
```php
// Query 1: all fee structures for the term
$allFeeStructures = FeeStructure::where('term_id', $termId)->get();
$globalExpected   = $allFeeStructures->whereNull('grade_id')->sum('amount');
$gradeExpectedMap = $allFeeStructures->whereNotNull('grade_id')
    ->groupBy('grade_id')
    ->map(fn($fees) => $fees->sum('amount'));

// Query 2: all payments aggregated by student
$paymentsMap = FeePayment::where('term_id', $termId)
    ->select('student_id', DB::raw('SUM(amount) as total'))
    ->groupBy('student_id')
    ->pluck('total', 'student_id');

// Zero queries inside the loop — pure PHP arithmetic
foreach ($students as $student) {
    $expected = $globalExpected + ($gradeExpectedMap[$student->stream->grade_id] ?? 0);
    $paid     = $paymentsMap[$student->id] ?? 0;
    $balance  = $expected - $paid;
}
// 300 students = 4 queries total
```

This is the difference between a page that loads in 8 seconds and one that loads in 200ms.

### Payment Receipt PDF

Every payment generates a printable receipt. The PDF is built using dompdf with a compact receipt layout showing:
- Student name and admission number
- Term and class
- Payment method (with a badge)
- Reference number (for M-Pesa transaction codes)
- Amount paid in large font
- Who recorded the payment and when

---

## 10. The Report Card System Explained

### What It Shows

A report card aggregates a student's performance for a **specific term**:

1. **Subject averages** — all assessments for that subject in that term are averaged
2. **Letter grade** — derived from the average (A: 70%+, B: 60%+, C: 50%+, D: 40%+, F: below 40%)
3. **Overall average** — mean of all subject averages
4. **Attendance summary** — present, absent, late counts and attendance rate, filtered to the same term

### Term Filtering

Without term filtering, a student who has been at the school for three terms would see all their grades mixed together — an average of averages across three different term's work. That's meaningless as a report card.

With the term filter:
- Defaults to the active term
- Can be changed to any historical term
- "All Terms" option available for a cumulative view
- The selected term appears on the report card itself
- The PDF filename includes the term name

### Output Formats

**HTML view** — accessible in the browser, printable via the browser's print function. Uses Tailwind CSS classes and CDN.

**PDF download** — generated by dompdf from a separate Blade template (`reports/report-card-pdf.blade.php`) that uses only inline CSS. CDN links don't work in dompdf because it cannot make external HTTP requests during PDF generation. This is why there are two templates.

---

## 11. Notifications & Queue System

### What Triggers a Notification

| Event | Who gets notified | What the email says |
|---|---|---|
| Student marked absent or late | Student's parent | Student's name, subject, class, date, and status |
| Teacher posts grades for an assessment | Student's parent | Student's name, subject, score, percentage, and letter grade |

Notifications are only sent if the student has a linked parent account **with a valid email address**. No error is thrown if no parent exists — it fails silently.

### How the Queue Works

Sending an email synchronously inside a web request means the teacher waits for every email to be dispatched before the page redirects. In a class of 40 students, that could mean 40 simultaneous SMTP calls.

The solution is a **queue**. The notification is serialised and written to the `jobs` table. The web request finishes immediately (fast redirect). A background worker process reads the `jobs` table and sends the emails one by one.

```
Teacher submits form
       ↓
Controller creates Attendance records
       ↓
Notification pushed to jobs table  ← fast
       ↓
HTTP response returned to teacher  ← page reloads immediately

(background, separately)
Queue worker reads jobs table
       ↓
Sends email to parent via SMTP
       ↓
Deletes job from table
```

### Environment Configuration

| Setting | Local Dev | Production |
|---|---|---|
| `QUEUE_CONNECTION` | `sync` (fires immediately, no worker) | `database` (queued, needs worker) |
| `MAIL_MAILER` | `log` (written to `storage/logs/laravel.log`) | `smtp` (real email delivery) |

In local development with `sync`, emails are processed immediately in the same request — useful for testing without running a queue worker.

In production with `database`, run the queue worker using the provided `supervisor.conf`:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start school-worker:*
```

Or manually for testing:
```bash
php artisan queue:work
```

---

## 12. Audit / Activity Log

Every significant admin action is recorded to the `activity_logs` table using:

```php
ActivityLog::record('created', 'Student', $student->id, "Created student: John Doe (STD2026001)");
```

**What is logged:**
- Student created, updated, deleted
- Teacher created, updated, deleted
- Fee structure created, updated, deleted
- Fee payment recorded, deleted

**What is NOT logged:**
- Teacher attendance marking (too high volume, low audit value)
- Grade entry (same reason)
- Read-only views

The log is viewable by admins at `/admin/activity-log`, paginated at 30 records per page, ordered newest first, with the acting user's name displayed.

---

## 13. Soft Deletes — What They Are and Why

### The Problem with Hard Deletes

When you `DELETE FROM users WHERE id = 5`, the row is gone permanently. But that user may have:
- Attendance records linked to them
- Grade records linked to them
- Fee payment records linked to them
- Activity log entries about them

If the row is gone, all those records become orphaned (foreign key pointing to nothing) or cascade-deleted (data loss).

### What Soft Deletes Do

Instead of deleting the row, Laravel sets a `deleted_at` timestamp:

```sql
UPDATE users SET deleted_at = '2026-04-12 09:00:00' WHERE id = 5;
```

The record is still in the database. But Laravel's ORM automatically adds `WHERE deleted_at IS NULL` to every query — so the deleted user never appears in any listing, search, or relationship load. To anyone using the system, the user is gone. The data is preserved.

### Which Models Use Soft Deletes

| Model | Reason |
|---|---|
| `User` | Students and teachers may be re-enrolled or reinstated; their history must survive |
| `StudentGrade` | Grade records are financial and academic history; permanent deletion is a liability |
| `Attendance` | Attendance is a legal record in many jurisdictions; must not be permanently deleted |

### Impact on Testing

The default `ProfileTest` asserts `$this->assertNull($user->fresh())` after account deletion. With soft deletes, `$user->fresh()` returns the record with `deleted_at` set, not null. The test was updated to:

```php
$this->assertNotNull($user->fresh()->deleted_at);
```

This correctly verifies the user is soft-deleted rather than hard-deleted.

---

## 14. Performance Design Decisions

### Eager Loading

Every controller that renders a list loads relationships upfront using `with()`:

```php
User::where('usertype', 'student')->with('stream.grade')->get();
```

Without `with('stream.grade')`, accessing `$student->stream->grade->name` in a loop fires one query per student — the classic N+1 problem.

### Pagination

Lists that can grow large are paginated:
- Students list: 10 per page
- Fee balances: 50 per page
- Attendance history: 20 per page
- Activity log: 30 per page
- Report card student list: 20 per page

`withQueryString()` is appended to all paginators so filter parameters (term_id, grade_id, etc.) survive pagination clicks.

### SQL Aggregation for Fee Balances

The fee balance page uses SQL `GROUP BY` and `SUM()` to aggregate payments in one query, rather than querying per student. See [Section 9](#9-the-fee-system-explained) for the full explanation.

### No Lazy Loading in Production

Controllers explicitly load all needed relationships. This prevents accidental lazy-loading (silent N+1 queries) in views.

---

## 15. Testing

The test suite uses **Pest** with `RefreshDatabase` — each test runs on a clean database, migrations are applied fresh, and the database is wiped after each test. This guarantees tests don't interfere with each other.

### Test Files

| File | Tests | What is covered |
|---|---|---|
| `RoleAccessTest.php` | 11 | Login redirects per role, cross-role blocking, register route removed |
| `FeeBalanceTest.php` | 8 | `forStudent()` logic, `totalPaid()`, admin payment recording |
| `ParentAuthorizationTest.php` | 6 | Parent registration, child hijack prevention, child isolation |
| `GradeEntryTest.php` | 4 | Teacher grade entry, cross-teacher blocking, percentage accessor |
| `Auth/*` | 16 | Breeze auth: login, logout, password reset, email verification |
| `ProfileTest.php` | 3 | Profile update, password change, account deletion (soft delete) |

**Total: 52 tests, 103 assertions — all passing.**

### Running Tests

```bash
php artisan test
```

Tests run against SQLite (in-memory) — no MySQL connection required. The test database is configured in `phpunit.xml`.

### Factories

Every model has a factory for generating test data:

| Factory | Key decisions |
|---|---|
| `UserFactory` | Default usertype is `student`; password is always `"password"` (hashed) |
| `GradeFactory` | Auto-increments `order` to satisfy the NOT NULL constraint |
| `TermFactory` | `is_active` defaults to `false` — tests opt in explicitly |
| `FeePaymentFactory` | `recorded_by` is nullable — not every payment has a recorder |

---

## 16. Deployment & Environment Configuration

### Environment Variables

Copy `.env.example` to `.env` and update:

```bash
cp .env.example .env
php artisan key:generate
```

Key settings to change for production:

| Variable | Local Dev Value | Production Value |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost` | Your actual domain |
| `DB_CONNECTION` | `sqlite` | `mysql` |
| `DB_HOST` | — | Your MySQL host |
| `DB_DATABASE` | — | Your database name |
| `DB_USERNAME` | — | Your DB user |
| `DB_PASSWORD` | — | Your DB password |
| `QUEUE_CONNECTION` | `sync` | `database` |
| `MAIL_MAILER` | `log` | `smtp` |
| `MAIL_HOST` | — | Your SMTP host |
| `MAIL_USERNAME` | — | Your SMTP username |
| `MAIL_PASSWORD` | — | Your SMTP password |
| `MAIL_FROM_ADDRESS` | — | `noreply@yourschool.com` |

### Fresh Installation

```bash
composer install
php artisan migrate
php artisan db:seed
```

The seeder creates:
- All 9 grades (Grade 1–9) in order
- 3 streams per grade (A, B, C)
- Core subjects (Mathematics, English, Kiswahili, Science, Social Studies, CRE)
- One admin account: `admin@school.com` / `Admin@1234`

### Production Queue Worker (Linux with Supervisor)

Copy `supervisor.conf` to your server:

```bash
sudo cp supervisor.conf /etc/supervisor/conf.d/school-worker.conf
# Update the command path to match your server
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start school-worker:*
```

Supervisor will keep the queue worker running permanently. If the server reboots, it restarts automatically.

---

## 17. How to Use the System — Role by Role

### Admin: Getting Started

1. Log in at `/login` with `admin@school.com`
2. **Set up terms first** — go to Manage Terms, create Term 1 with a start and end date, mark it active
3. **Create grades** — already seeded if you ran `db:seed`
4. **Create streams** — e.g., "North", "South" under Grade 7
5. **Create subjects** — Mathematics, English, etc.
6. **Create classes** — link a grade, stream, subject, and teacher
7. **Create teachers** — they'll receive credentials to log in
8. **Create students** — assign them to a stream; the system auto-generates their admission number
9. **Enroll students into classes** — go to the class, add students individually or bulk-enroll the whole stream
10. **Define fee structures** — go to Fee Structures, add fees for the active term
11. **Record payments as they come in** — go to Fee Balances, click a student's Details, use the payment form

### Teacher: Daily Workflow

1. Log in and see your assigned classes on the dashboard
2. **Mark attendance** every day — click "Mark Attendance" on the relevant class
3. **Enter grades** after each assessment — click "Enter Grades", fill in the assessment name, date, max score, and each student's score
4. Review **Grade History** to see all past assessments and edit if needed
5. Export grades as CSV for your own records if needed

### Student: Checking Your Progress

1. Log in to see your dashboard — admission number, class, and attendance rate are shown immediately
2. Click **View My Attendance History** to see every attendance record
3. Click **View My Grades** to see all assessment scores
4. Click **View My Report Card** — select the term you want to view
5. Click **Download PDF** to save your report card
6. Click **My Fees** to see what you owe for the current term

### Parent: Monitoring Your Child

1. Register at `/parent/register` — you need your child's admission number
2. Your dashboard shows your child (or children if you have multiple enrolled)
3. Click **View Grades** to see all assessments
4. Click **View Report Card** to see the term-filtered academic summary
5. Click **View Fees** to see what is owed and what has been paid
6. You will receive emails automatically when your child is marked absent or late, and when new grades are posted

---

*Documentation generated: April 2026*
*System version: Laravel 12.x | PHP 8.2*
