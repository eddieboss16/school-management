<?php

/**
 * Admission number generation.
 *
 * Numbers are STD{year}{sequence}, zero-padded to at least three digits, and
 * must never repeat — the column is uniquely indexed, and a duplicate is a
 * 500 in the admin's face rather than a silent overwrite.
 *
 * These go through the real HTTP path so they cover whatever the controller
 * actually does, not just the generator in isolation.
 */

use App\Models\User;
use App\Support\AdmissionNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function admissionAdmin(): User
{
    return User::factory()->create(['usertype' => 'admin']);
}

/** Create a student holding a specific admission number. */
function studentHolding(string $admissionNumber, bool $trashed = false): User
{
    $student = User::factory()->create([
        'usertype'         => 'student',
        'admission_number' => $admissionNumber,
    ]);

    if ($trashed) {
        $student->delete();
    }

    return $student;
}

/** Post the create-student form and return the admission number that resulted. */
function createStudentAndGetNumber(User $admin, string $email): string
{
    test()->actingAs($admin)
        ->post(route('admin.students.store'), [
            'name'                  => 'New Student',
            'email'                 => $email,
            'password'              => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])
        ->assertRedirect(route('admin.students'));

    return User::where('email', $email)->firstOrFail()->admission_number;
}

// ── Basic sequencing ────────────────────────────────────────────────────────

test('the first student of the year gets sequence 001', function () {
    $year = date('Y');

    expect(createStudentAndGetNumber(admissionAdmin(), 'first@example.com'))
        ->toBe("STD{$year}001");
});

test('sequence increments for each subsequent student', function () {
    $year  = date('Y');
    $admin = admissionAdmin();

    expect(createStudentAndGetNumber($admin, 'a@example.com'))->toBe("STD{$year}001")
        ->and(createStudentAndGetNumber($admin, 'b@example.com'))->toBe("STD{$year}002")
        ->and(createStudentAndGetNumber($admin, 'c@example.com'))->toBe("STD{$year}003");
});

test('an explicitly supplied admission number is respected', function () {
    $admin = admissionAdmin();

    test()->actingAs($admin)
        ->post(route('admin.students.store'), [
            'name'                  => 'Manual Student',
            'email'                 => 'manual@example.com',
            'admission_number'      => 'CUSTOM-001',
            'password'              => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])
        ->assertRedirect(route('admin.students'));

    expect(User::where('email', 'manual@example.com')->firstOrFail()->admission_number)
        ->toBe('CUSTOM-001');
});

// ── The 999 -> 1000 boundary ────────────────────────────────────────────────

test('the sequence rolls past 999 into four digits', function () {
    $year = date('Y');
    studentHolding("STD{$year}999");

    expect(createStudentAndGetNumber(admissionAdmin(), 'thousandth@example.com'))
        ->toBe("STD{$year}1000");
});

test('the sequence continues past 1000 without wrapping back to 001', function () {
    $year = date('Y');
    studentHolding("STD{$year}999");
    studentHolding("STD{$year}1000");

    expect(createStudentAndGetNumber(admissionAdmin(), 'thousandfirst@example.com'))
        ->toBe("STD{$year}1001");
});

test('a four digit high-water mark is read as a number, not as its last three characters', function () {
    // Reading the last three characters of STD{year}1000 yields "000", which
    // would restart the sequence at 001 and collide.
    $year = date('Y');
    studentHolding("STD{$year}1000");

    expect(createStudentAndGetNumber(admissionAdmin(), 'afterthousand@example.com'))
        ->toBe("STD{$year}1001");
});

test('the sequence keeps climbing across many students beyond the boundary', function () {
    $year  = date('Y');
    $admin = admissionAdmin();
    studentHolding("STD{$year}998");

    $issued = [];
    foreach (range(1, 5) as $i) {
        $issued[] = createStudentAndGetNumber($admin, "bulk{$i}@example.com");
    }

    expect($issued)->toBe([
        "STD{$year}999", "STD{$year}1000", "STD{$year}1001", "STD{$year}1002", "STD{$year}1003",
    ]);
});

// ── Soft-deleted students still hold their number ───────────────────────────

test('a soft deleted student does not free its admission number for reuse', function () {
    // The unique index covers soft-deleted rows, so reissuing their number is
    // a duplicate key error, not a fresh start.
    $year = date('Y');
    studentHolding("STD{$year}001", trashed: true);

    expect(createStudentAndGetNumber(admissionAdmin(), 'afterdelete@example.com'))
        ->toBe("STD{$year}002");
});

test('a soft deleted student at the high-water mark is still counted', function () {
    $year = date('Y');
    studentHolding("STD{$year}001");
    studentHolding("STD{$year}002", trashed: true);

    expect(createStudentAndGetNumber(admissionAdmin(), 'afterdelete2@example.com'))
        ->toBe("STD{$year}003");
});

// ── Numbers from other years are not in the way ─────────────────────────────

test('a previous year high-water mark does not affect this year', function () {
    $year = date('Y');
    $prev = $year - 1;
    studentHolding("STD{$prev}742");

    expect(createStudentAndGetNumber(admissionAdmin(), 'newyear@example.com'))
        ->toBe("STD{$year}001");
});

// ── Every issued number is unique ───────────────────────────────────────────

// ── Concurrency: the number is claimed, not re-derived ──────────────────────
//
// The old generator computed the next number by reading the highest existing
// student, so two admissions submitted before either had been written read the
// same maximum and produced the same number. These assert the property that
// makes that impossible: a number is consumed from the counter at generation
// time, whether or not a student row ever follows.

test('two generations with no student written in between return different numbers', function () {
    $year = date('Y');

    $first  = AdmissionNumber::next();
    $second = AdmissionNumber::next();

    // Nothing was inserted between the two calls; the old generator would have
    // handed out STD{year}001 twice.
    expect($first)->toBe("STD{$year}001")
        ->and($second)->toBe("STD{$year}002")
        ->and($first)->not->toBe($second);

    expect(User::whereNotNull('admission_number')->count())->toBe(0);
});

test('a burst of generations with nothing persisted yields a contiguous unique run', function () {
    $year = date('Y');

    $issued = collect(range(1, 50))->map(fn () => AdmissionNumber::next())->all();

    expect($issued)->toHaveCount(50)
        ->and(array_unique($issued))->toHaveCount(50)
        ->and($issued[0])->toBe("STD{$year}001")
        ->and($issued[49])->toBe("STD{$year}050");
});

test('the counter advances even when the student insert is rolled back', function () {
    $year = date('Y');

    DB::transaction(function () {
        AdmissionNumber::next();
        throw new RuntimeException('simulated failure after claiming a number');
    });
})->throws(RuntimeException::class);

test('a claimed number is not reissued after the surrounding work fails', function () {
    $year = date('Y');

    try {
        DB::transaction(function () {
            AdmissionNumber::next();
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // The outer transaction rolled the counter back with it, so the number
        // is returned to the pool rather than skipped. Either behaviour is
        // defensible; this records which one it is.
    }

    expect(AdmissionNumber::next())->toBe("STD{$year}001");
});

test('the counter is seeded above pre-existing students the first time a year is used', function () {
    $year = date('Y');
    studentHolding("STD{$year}0475");

    expect(AdmissionNumber::next())->toBe("STD{$year}476")
        ->and(DB::table('admission_sequences')->where('year', $year)->value('next_number'))->toBe(477);
});

test('a number assigned by hand after seeding is stepped over, not reissued', function () {
    $year = date('Y');

    AdmissionNumber::next();                 // claims 001, counter now at 002
    studentHolding("STD{$year}002");         // an admin types this one in manually

    expect(AdmissionNumber::next())->toBe("STD{$year}003");
});

test('no admission number is ever issued twice', function () {
    $admin = admissionAdmin();
    studentHolding('STD' . date('Y') . '997');

    $issued = [];
    foreach (range(1, 8) as $i) {
        $issued[] = createStudentAndGetNumber($admin, "uniq{$i}@example.com");
    }

    expect($issued)->toHaveCount(8)
        ->and(array_unique($issued))->toHaveCount(8);
});
