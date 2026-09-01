<?php

use App\Enums\PaymentMethod;
use App\Models\FeePayment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// `payment_method` used to be an unconstrained string guarded only by the `in:`
// rule in Admin\FeesController. These tests go around the controller — and
// around the Eloquent cast — so a pass means the *database* is refusing the
// value, not a validation rule some future code path could skip.

/** Base row for a direct insert: valid everywhere except where a test overrides. */
function paymentRow(array $overrides = []): array
{
    $term = Term::factory()->create();
    $student = User::factory()->create(['usertype' => 'student']);

    return array_merge([
        'student_id' => $student->id,
        'term_id' => $term->id,
        'amount' => 1500,
        'payment_date' => '2026-09-01',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides);
}

// ── DB-level enforcement ──────────────────────────────────────────────────────

test('database rejects an invalid payment_method inserted through the query builder', function () {
    // No model, no cast, no validation — straight to SQL.
    expect(fn () => DB::table('fee_payments')->insert(paymentRow([
        'payment_method' => 'crypto',
    ])))->toThrow(QueryException::class);

    expect(DB::table('fee_payments')->count())->toBe(0);
});

test('database rejects an invalid payment_method written through the model, bypassing the cast', function () {
    // FeePayment::insert() is the model, but it skips casts and events, so the
    // bad value reaches the driver. If only the enum cast were guarding the
    // column, this row would land.
    expect(fn () => FeePayment::insert(paymentRow([
        'payment_method' => 'bitcoin',
    ])))->toThrow(QueryException::class);

    expect(FeePayment::count())->toBe(0);
});

test('database rejects a payment_method that differs only in casing', function () {
    expect(fn () => DB::table('fee_payments')->insert(paymentRow([
        'payment_method' => 'CASH',
    ])))->toThrow(QueryException::class);
})->skip(
    fn () => DB::connection()->getDriverName() === 'mysql',
    'MySQL ENUM comparison is case-insensitive under the default collation.'
);

test('database rejects an empty payment_method', function () {
    expect(fn () => DB::table('fee_payments')->insert(paymentRow([
        'payment_method' => '',
    ])))->toThrow(QueryException::class);

    expect(DB::table('fee_payments')->count())->toBe(0);
});

test('an existing row cannot be updated to an invalid payment_method', function () {
    $payment = FeePayment::factory()->create(['payment_method' => PaymentMethod::Cash]);

    expect(fn () => DB::table('fee_payments')
        ->where('id', $payment->id)
        ->update(['payment_method' => 'paypal']))->toThrow(QueryException::class);

    expect($payment->fresh()->payment_method)->toBe(PaymentMethod::Cash);
});

// ── The cast, on top of the constraint ────────────────────────────────────────

test('every enum case is accepted by the database', function (PaymentMethod $method) {
    $payment = FeePayment::factory()->create(['payment_method' => $method]);

    expect($payment->fresh()->payment_method)->toBe($method)
        ->and(DB::table('fee_payments')->value('payment_method'))->toBe($method->value);
})->with(PaymentMethod::cases());

test('payment_method hydrates as an enum instance', function () {
    $payment = FeePayment::factory()->create(['payment_method' => PaymentMethod::Mpesa]);

    expect($payment->fresh()->payment_method)
        ->toBeInstanceOf(PaymentMethod::class)
        ->and($payment->fresh()->payment_method->label())->toBe('M-Pesa');
});

test('the cast refuses an invalid value before it reaches the database', function () {
    expect(fn () => FeePayment::factory()->create(['payment_method' => 'cheque']))
        ->toThrow(ValueError::class);
});
