<?php

use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── FeeStructure::forStudent() ────────────────────────────────────────────────

test('forStudent returns global fees for any student', function () {
    $term  = Term::factory()->create(['is_active' => true]);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    $globalFee = FeeStructure::factory()->create(['term_id' => $term->id, 'grade_id' => null, 'amount' => 5000]);

    $fees = FeeStructure::forStudent($student, $term->id);

    expect($fees)->toHaveCount(1)
        ->and($fees->first()->id)->toBe($globalFee->id);
});

test('forStudent includes grade-specific fees matching student grade', function () {
    $term  = Term::factory()->create(['is_active' => true]);
    $grade = Grade::factory()->create();
    $otherGrade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    $globalFee       = FeeStructure::factory()->create(['term_id' => $term->id, 'grade_id' => null, 'amount' => 5000]);
    $gradeFee        = FeeStructure::factory()->create(['term_id' => $term->id, 'grade_id' => $grade->id, 'amount' => 2000]);
    $otherGradeFee   = FeeStructure::factory()->create(['term_id' => $term->id, 'grade_id' => $otherGrade->id, 'amount' => 3000]);

    $fees = FeeStructure::forStudent($student, $term->id);

    expect($fees)->toHaveCount(2)
        ->and($fees->pluck('id')->sort()->values()->toArray())
        ->toBe(collect([$globalFee->id, $gradeFee->id])->sort()->values()->toArray());
});

test('forStudent does not include fees from a different term', function () {
    $term      = Term::factory()->create();
    $otherTerm = Term::factory()->create();
    $grade     = Grade::factory()->create();
    $stream    = Stream::factory()->create(['grade_id' => $grade->id]);
    $student   = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    FeeStructure::factory()->create(['term_id' => $otherTerm->id, 'grade_id' => null, 'amount' => 5000]);

    $fees = FeeStructure::forStudent($student, $term->id);

    expect($fees)->toHaveCount(0);
});

// ── FeePayment::totalPaid() ───────────────────────────────────────────────────

test('totalPaid returns sum of all payments for a student in a term', function () {
    $term    = Term::factory()->create();
    $student = User::factory()->create(['usertype' => 'student']);

    FeePayment::factory()->create(['student_id' => $student->id, 'term_id' => $term->id, 'amount' => 3000]);
    FeePayment::factory()->create(['student_id' => $student->id, 'term_id' => $term->id, 'amount' => 2000]);

    expect(FeePayment::totalPaid($student->id, $term->id))->toBe(5000.0);
});

test('totalPaid excludes payments from a different term', function () {
    $term      = Term::factory()->create();
    $otherTerm = Term::factory()->create();
    $student   = User::factory()->create(['usertype' => 'student']);

    FeePayment::factory()->create(['student_id' => $student->id, 'term_id' => $otherTerm->id, 'amount' => 9999]);
    FeePayment::factory()->create(['student_id' => $student->id, 'term_id' => $term->id, 'amount' => 1000]);

    expect(FeePayment::totalPaid($student->id, $term->id))->toBe(1000.0);
});

test('totalPaid returns zero when no payments exist', function () {
    $term    = Term::factory()->create();
    $student = User::factory()->create(['usertype' => 'student']);

    expect(FeePayment::totalPaid($student->id, $term->id))->toBe(0.0);
});

// ── Admin fee balances page ───────────────────────────────────────────────────

test('admin can view fee balances page', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $term  = Term::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.fees.balances', ['term_id' => $term->id]))
        ->assertOk();
});

test('admin can record a payment for a student', function () {
    $admin   = User::factory()->create(['usertype' => 'admin']);
    $term    = Term::factory()->create(['is_active' => true]);
    $student = User::factory()->create(['usertype' => 'student']);

    $this->actingAs($admin)
        ->post(route('admin.fees.student.payment', $student->id), [
            'term_id'        => $term->id,
            'amount'         => 5000,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.fees.student', $student->id));

    $this->assertDatabaseHas('fee_payments', [
        'student_id' => $student->id,
        'term_id'    => $term->id,
        'amount'     => 5000,
    ]);
});
