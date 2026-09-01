<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Term;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeesController extends Controller
{
    // ─── FEE STRUCTURES ────────────────────────────────────────────

    public function structures()
    {
        $terms = Term::orderBy('start_date', 'desc')->get();
        $activeTerm = Term::activeTerm();
        $selectedTermId = request('term_id', $activeTerm?->id);

        $structures = FeeStructure::with(['term', 'grade'])
            ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        return view('admin.fees.structures', compact('structures', 'terms', 'selectedTermId', 'activeTerm'));
    }

    public function createStructure()
    {
        $terms = Term::orderBy('start_date', 'desc')->get();
        $grades = Grade::orderBy('order')->get();

        return view('admin.fees.structure-create', compact('terms', 'grades'));
    }

    public function storeStructure(Request $request)
    {
        $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $structure = FeeStructure::create($request->only('term_id', 'grade_id', 'name', 'amount', 'description'));

        ActivityLog::record('created', 'FeeStructure', $structure->id,
            "Created fee: {$structure->name} (KES {$structure->amount})");

        return redirect()->route('admin.fees.structures')->with('success', 'Fee structure created.');
    }

    public function editStructure($id)
    {
        $structure = FeeStructure::findOrFail($id);
        $terms = Term::orderBy('start_date', 'desc')->get();
        $grades = Grade::orderBy('order')->get();

        return view('admin.fees.structure-edit', compact('structure', 'terms', 'grades'));
    }

    public function updateStructure(Request $request, $id)
    {
        $structure = FeeStructure::findOrFail($id);

        $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $structure->update($request->only('term_id', 'grade_id', 'name', 'amount', 'description'));

        ActivityLog::record('updated', 'FeeStructure', $structure->id,
            "Updated fee: {$structure->name} (KES {$structure->amount})");

        return redirect()->route('admin.fees.structures')->with('success', 'Fee structure updated.');
    }

    public function destroyStructure($id)
    {
        $structure = FeeStructure::findOrFail($id);
        ActivityLog::record('deleted', 'FeeStructure', $structure->id,
            "Deleted fee structure: {$structure->name}");
        $structure->delete();

        return redirect()->route('admin.fees.structures')->with('success', 'Fee structure deleted.');
    }

    // ─── BALANCES (overview of all students) ──────────────────────

    public function balances()
    {
        $terms = Term::orderBy('start_date', 'desc')->get();
        $activeTerm = Term::activeTerm();
        $selectedTermId = request('term_id', $activeTerm?->id);
        $grades = Grade::orderBy('order')->get();
        $streams = Stream::with('grade')->orderBy('name')->get();

        $filterGradeId = request('grade_id');
        $filterStreamId = request('stream_id');
        $filterStatus = request('status'); // 'outstanding' | 'cleared'

        // ── Pre-load fee data for the selected term (2 queries total) ──
        $globalExpected = 0;
        $gradeExpectedMap = []; // grade_id => expected amount
        $paymentsMap = [];      // student_id => total paid

        if ($selectedTermId) {
            // Query 1: all fee structures for this term
            $allFeeStructures = FeeStructure::where('term_id', $selectedTermId)->get();
            $globalExpected = (float) $allFeeStructures->whereNull('grade_id')->sum('amount');
            $gradeExpectedMap = $allFeeStructures->whereNotNull('grade_id')
                ->groupBy('grade_id')
                ->map(fn ($fees) => (float) $fees->sum('amount'))
                ->toArray();

            // Query 2: aggregate payments per student for this term
            $paymentsMap = FeePayment::where('term_id', $selectedTermId)
                ->select('student_id', DB::raw('SUM(amount) as total'))
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
                ->map(fn ($v) => (float) $v)
                ->toArray();
        }

        // ── Build student query with filters ──────────────────────────
        $query = User::where('usertype', 'student')
            ->with('stream.grade')
            ->orderBy('name');

        if ($filterStreamId) {
            $query->where('stream_id', $filterStreamId);
        } elseif ($filterGradeId) {
            $query->whereHas('stream', fn ($q) => $q->where('grade_id', $filterGradeId));
        }

        $students = $query->paginate(50)->withQueryString();

        // ── Compute balances in PHP (no extra queries) ─────────────────
        $balances = [];
        foreach ($students as $student) {
            $gradeId = $student->stream?->grade_id;
            $expected = $globalExpected + (float) ($gradeId && isset($gradeExpectedMap[$gradeId]) ? $gradeExpectedMap[$gradeId] : 0);
            $paid = (float) ($paymentsMap[$student->id] ?? 0);
            $balance = $expected - $paid;
            $balances[$student->id] = compact('expected', 'paid', 'balance');
        }

        // ── Status filter applied in PHP after balance calculation ─────
        if ($filterStatus === 'outstanding') {
            $students->setCollection($students->getCollection()->filter(fn ($s) => $balances[$s->id]['balance'] > 0)->values());
        } elseif ($filterStatus === 'cleared') {
            $students->setCollection($students->getCollection()->filter(fn ($s) => $balances[$s->id]['balance'] <= 0)->values());
        }

        return view('admin.fees.balances', compact(
            'students', 'balances', 'terms', 'selectedTermId', 'activeTerm',
            'grades', 'streams', 'filterGradeId', 'filterStreamId', 'filterStatus'
        ));
    }

    // ─── STUDENT PAYMENT HISTORY + RECORD PAYMENT ─────────────────

    public function studentFees($studentId)
    {
        $student = User::where('usertype', 'student')->with('stream.grade')->findOrFail($studentId);
        $terms = Term::orderBy('start_date', 'desc')->get();
        $activeTerm = Term::activeTerm();
        $selectedTermId = request('term_id', $activeTerm?->id);

        $fees = $selectedTermId ? FeeStructure::forStudent($student, $selectedTermId) : collect();
        $payments = FeePayment::where('student_id', $studentId)
            ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
            ->with(['term', 'recordedBy'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $expected = $fees->sum('amount');
        $paid = $payments->sum('amount');
        $balance = $expected - $paid;

        return view('admin.fees.student-fees', compact(
            'student', 'fees', 'payments', 'terms',
            'selectedTermId', 'activeTerm', 'expected', 'paid', 'balance'
        ));
    }

    public function recordPayment(Request $request, $studentId)
    {
        $student = User::where('usertype', 'student')->findOrFail($studentId);

        $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = FeePayment::create([
            'student_id' => $student->id,
            'term_id' => $request->term_id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'recorded_by' => auth()->id(),
        ]);

        ActivityLog::record('created', 'FeePayment', $payment->id,
            "Recorded payment of KES {$payment->amount} for {$student->name} ({$request->payment_method})");

        return redirect()->route('admin.fees.student', $studentId)
            ->with('success', 'Payment of KES '.number_format($payment->amount, 2).' recorded.');
    }

    public function deletePayment($paymentId)
    {
        $payment = FeePayment::with('student')->findOrFail($paymentId);
        $studentId = $payment->student_id;

        ActivityLog::record('deleted', 'FeePayment', $payment->id,
            "Deleted payment of KES {$payment->amount} for {$payment->student->name}");

        $payment->delete();

        return redirect()->route('admin.fees.student', $studentId)
            ->with('success', 'Payment deleted.');
    }

    public function receiptPdf($paymentId)
    {
        $payment = FeePayment::with(['student.stream.grade', 'term', 'recordedBy'])->findOrFail($paymentId);

        $pdf = Pdf::loadView('admin.fees.receipt-pdf', compact('payment'))
            ->setPaper([0, 0, 226, 340], 'portrait'); // ~80mm receipt width

        $filename = 'receipt-'.$payment->id.'-'.str_replace(' ', '-', strtolower($payment->student->name)).'.pdf';

        return $pdf->download($filename);
    }

    // ─── CSV EXPORT ────────────────────────────────────────────────

    public function exportBalancesCsv(): StreamedResponse
    {
        $termId = request('term_id');
        $term = $termId ? Term::findOrFail($termId) : Term::activeTerm();

        // Pre-load all fee structures and payments in 2 queries
        $globalExpected = 0;
        $gradeExpectedMap = [];
        $paymentsMap = [];

        if ($term) {
            $allFeeStructures = FeeStructure::where('term_id', $term->id)->get();
            $globalExpected = (float) $allFeeStructures->whereNull('grade_id')->sum('amount');
            $gradeExpectedMap = $allFeeStructures->whereNotNull('grade_id')
                ->groupBy('grade_id')
                ->map(fn ($fees) => (float) $fees->sum('amount'))
                ->toArray();

            $paymentsMap = FeePayment::where('term_id', $term->id)
                ->select('student_id', DB::raw('SUM(amount) as total'))
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
                ->map(fn ($v) => (float) $v)
                ->toArray();
        }

        $students = User::where('usertype', 'student')->with('stream.grade')->orderBy('name')->get();
        $filename = 'fee-balances-'.($term ? str_replace(' ', '-', strtolower($term->name)) : 'all').'.csv';

        $response = new StreamedResponse(function () use ($students, $term, $globalExpected, $gradeExpectedMap, $paymentsMap) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Term', $term ? $term->name : 'All Terms']);
            fputcsv($handle, []);
            fputcsv($handle, ['Adm No.', 'Name', 'Class', 'Expected (KES)', 'Paid (KES)', 'Balance (KES)', 'Status']);

            foreach ($students as $student) {
                $gradeId = $student->stream?->grade_id;
                $expected = $globalExpected + (float) ($gradeId && isset($gradeExpectedMap[$gradeId]) ? $gradeExpectedMap[$gradeId] : 0);
                $paid = (float) ($paymentsMap[$student->id] ?? 0);
                $balance = $expected - $paid;

                fputcsv($handle, [
                    $student->admission_number ?? '',
                    $student->name,
                    $student->stream ? $student->stream->grade->name.' '.$student->stream->name : 'Unassigned',
                    number_format($expected, 2),
                    number_format($paid, 2),
                    number_format($balance, 2),
                    $balance <= 0 ? 'Cleared' : 'Outstanding',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
